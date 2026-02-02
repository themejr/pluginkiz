<?php
/**
 * Parent Snapshot (MVP).
 *
 * Shows last N days:
 * - sessions count
 * - best 2 skills / weakest 2 skills (based on attempts accuracy per skill taxonomy)
 * - simple recommendation
 *
 * Depends on:
 * - Tables: wp_kqas_sessions, wp_kqas_attempts
 * - Taxonomy: kqas_skill
 * - Option: kqas_snapshot_days
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Parent_Snapshot', false ) ) :

	final class KQAS_Parent_Snapshot {

		const TABLE_SESSIONS = 'kqas_sessions';
		const TABLE_ATTEMPTS = 'kqas_attempts';

		private static function sessions_table() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_SESSIONS;
		}

		private static function attempts_table() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_ATTEMPTS;
		}

		private static function opt( $option, $default ) {
			if ( class_exists( 'KQAS_Settings' ) && is_callable( array( 'KQAS_Settings', 'get' ) ) ) {
				return KQAS_Settings::get( $option, $default );
			}
			return get_option( $option, $default );
		}

		/**
		 * Build snapshot for a kid code.
		 *
		 * @param string $kid_code Kid code.
		 * @param array  $args Optional:
		 *                     - days (int)
		 *                     - min_attempts_per_skill (int)
		 * @return array|WP_Error
		 */
		public static function get_snapshot( $kid_code, $args = array() ) {
			global $wpdb;

			if ( ! class_exists( 'KQAS_Kid_Codes' ) ) {
				return new WP_Error( 'kqas_missing_dependency', esc_html__( 'Kid Codes module is missing.', 'kidquiz-age-smart' ) );
			}

			$kid_code = KQAS_Kid_Codes::normalize_code( (string) $kid_code );
			if ( ! KQAS_Kid_Codes::is_valid_active_code( $kid_code ) ) {
				return new WP_Error( 'kqas_invalid_kid_code', esc_html__( 'Invalid Kid Code.', 'kidquiz-age-smart' ) );
			}

			$days = isset( $args['days'] ) ? (int) $args['days'] : (int) self::opt( 'kqas_snapshot_days', 7 );
			$days = max( 1, min( 30, $days ) );

			$min_attempts_per_skill = isset( $args['min_attempts_per_skill'] ) ? (int) $args['min_attempts_per_skill'] : 2;
			$min_attempts_per_skill = max( 1, min( 50, $min_attempts_per_skill ) );

			// Use WP local time for comparisons (consistent with current_time('mysql')).
			$since_mysql = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) );

			$sessions_table = self::sessions_table();

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$session_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, quiz_id, age_group, started_at, ended_at, questions_count, correct_count, status
					 FROM {$sessions_table}
					 WHERE kid_code = %s
					   AND started_at >= %s
					   AND status = %s
					 ORDER BY started_at DESC",
					$kid_code,
					$since_mysql,
					'finished'
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! is_array( $session_rows ) ) {
				$session_rows = array();
			}

			$sessions_count   = count( $session_rows );
			$total_questions  = 0;
			$total_correct    = 0;
			$age_group_latest = '';

			$session_ids = array();
			foreach ( $session_rows as $r ) {
				$session_ids[]     = (int) $r['id'];
				$total_questions  += (int) $r['questions_count'];
				$total_correct    += (int) $r['correct_count'];

				if ( '' === $age_group_latest && ! empty( $r['age_group'] ) ) {
					$age_group_latest = (string) $r['age_group'];
				}
			}

			$skills_summary = array(
				'best'    => array(),
				'weakest' => array(),
			);

			if ( ! empty( $session_ids ) && class_exists( 'KQAS_Taxonomies' ) ) {
				$skills_summary = self::compute_skills_summary( $session_ids, $min_attempts_per_skill );
			}

			return array(
				'days'            => $days,
				'kid_code'        => $kid_code,
				'sessions_count'  => $sessions_count,
				'total_questions' => $total_questions,
				'total_correct'   => $total_correct,
				'best_skills'     => $skills_summary['best'],
				'weakest_skills'  => $skills_summary['weakest'],
				'recommendation'  => self::build_recommendation( $age_group_latest, $skills_summary ),
			);
		}

		/**
		 * Compute best/weakest skills based on attempts across multiple sessions.
		 *
		 * @param int[] $session_ids Session IDs.
		 * @param int   $min_attempts_per_skill Minimum attempts required to rank a skill.
		 * @return array{best:array,weakest:array}
		 */
		private static function compute_skills_summary( $session_ids, $min_attempts_per_skill ) {
			global $wpdb;

			$session_ids = array_values( array_filter( array_map( 'intval', (array) $session_ids ) ) );
			if ( empty( $session_ids ) ) {
				return array( 'best' => array(), 'weakest' => array() );
			}

			$attempts_table = self::attempts_table();
			$placeholders   = implode( ',', array_fill( 0, count( $session_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$prepared = $wpdb->prepare(
				"SELECT question_id, is_correct
				 FROM {$attempts_table}
				 WHERE session_id IN ({$placeholders})",
				$session_ids
			);
			$rows = $wpdb->get_results( $prepared, ARRAY_A );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! is_array( $rows ) || empty( $rows ) ) {
				return array( 'best' => array(), 'weakest' => array() );
			}

			$taxonomy = KQAS_Taxonomies::TAX_SKILL;

			$q_skill_cache = array(); // question_id => skill_ids[]
			$agg           = array(); // skill_id => ['attempts'=>int,'correct'=>int]

			foreach ( $rows as $row ) {
				$qid = (int) $row['question_id'];
				if ( $qid < 1 ) {
					continue;
				}

				if ( ! isset( $q_skill_cache[ $qid ] ) ) {
					$skill_ids = wp_get_post_terms(
						$qid,
						$taxonomy,
						array( 'fields' => 'ids' )
					);
					if ( is_wp_error( $skill_ids ) || ! is_array( $skill_ids ) ) {
						$skill_ids = array();
					}
					$q_skill_cache[ $qid ] = array_values( array_filter( array_map( 'intval', $skill_ids ) ) );
				}

				$skill_ids = $q_skill_cache[ $qid ];
				if ( empty( $skill_ids ) ) {
					continue;
				}

				$is_correct = ! empty( $row['is_correct'] ) ? 1 : 0;

				foreach ( $skill_ids as $sid ) {
					if ( ! isset( $agg[ $sid ] ) ) {
						$agg[ $sid ] = array( 'attempts' => 0, 'correct' => 0 );
					}
					$agg[ $sid ]['attempts']++;
					$agg[ $sid ]['correct'] += $is_correct;
				}
			}

			$items = array();
			foreach ( $agg as $sid => $stats ) {
				$attempts = (int) $stats['attempts'];
				$correct  = (int) $stats['correct'];

				if ( $attempts < $min_attempts_per_skill ) {
					continue;
				}

				$term = get_term( (int) $sid );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}

				$accuracy = ( $attempts > 0 ) ? ( $correct / $attempts ) : 0;

				$items[] = array(
					'term_id'  => (int) $sid,
					'name'     => (string) $term->name,
					'attempts' => $attempts,
					'correct'  => $correct,
					'accuracy' => $accuracy, // 0..1
				);
			}

			if ( empty( $items ) ) {
				return array( 'best' => array(), 'weakest' => array() );
			}

			$best = $items;
			usort(
				$best,
				function ( $a, $b ) {
					if ( $a['accuracy'] === $b['accuracy'] ) {
						return $b['attempts'] <=> $a['attempts'];
					}
					return $b['accuracy'] <=> $a['accuracy'];
				}
			);

			$weakest = $items;
			usort(
				$weakest,
				function ( $a, $b ) {
					if ( $a['accuracy'] === $b['accuracy'] ) {
						return $b['attempts'] <=> $a['attempts'];
					}
					return $a['accuracy'] <=> $b['accuracy'];
				}
			);

			return array(
				'best'    => array_slice( $best, 0, 2 ),
				'weakest' => array_slice( $weakest, 0, 2 ),
			);
		}

		private static function build_recommendation( $age_group, $skills_summary ) {
			$age_group = sanitize_text_field( (string) $age_group );
			$weakest   = ( isset( $skills_summary['weakest'] ) && is_array( $skills_summary['weakest'] ) ) ? $skills_summary['weakest'] : array();

			if ( ! empty( $weakest ) && ! empty( $weakest[0]['name'] ) ) {
				return sprintf(
					/* translators: %s: skill name */
					esc_html__( 'Try a quiz to practice: %s', 'kidquiz-age-smart' ),
					(string) $weakest[0]['name']
				);
			}

			if ( '4-6' === $age_group ) {
				return esc_html__( 'Try a short letters quiz (4–6).', 'kidquiz-age-smart' );
			}
			if ( '7-9' === $age_group ) {
				return esc_html__( 'Try a short reading & comprehension quiz (7–9).', 'kidquiz-age-smart' );
			}
			if ( '10-12' === $age_group ) {
				return esc_html__( 'Try a focused practice quiz (10–12).', 'kidquiz-age-smart' );
			}

			return esc_html__( 'Try another short quiz today.', 'kidquiz-age-smart' );
		}
	}

endif;
