<?php
/**
 * Age Rules Engine (core differentiator).
 *
 * مسؤول عن:
 * - تفسير Target Age Group (4-6 / 7-9 / 10-12)
 * - تحديد حدود القراءة الافتراضية
 * - فلترة/اختيار الأسئلة من بنك الأسئلة تلقائياً
 *
 * يعتمد على مفاتيح الميتا التي تم تثبيتها في Metaboxes:
 * - _kqas_age_min, _kqas_age_max
 * - _kqas_reading_level
 * - _kqas_difficulty
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Age_Rules', false ) ) :

	final class KQAS_Age_Rules {

		/**
		 * Normalize and validate age group.
		 *
		 * @param string $age_group Raw value (e.g., '4-6').
		 * @return string
		 */
		public static function normalize_age_group( $age_group ) {
			$age_group = sanitize_text_field( (string) $age_group );
			$allowed   = array( '4-6', '7-9', '10-12' );

			return in_array( $age_group, $allowed, true ) ? $age_group : '4-6';
		}

		/**
		 * Get target numeric age range for an age group.
		 *
		 * @param string $age_group '4-6' | '7-9' | '10-12'
		 * @return array{min:int,max:int}
		 */
		public static function get_target_age_range( $age_group ) {
			$age_group = self::normalize_age_group( $age_group );

			switch ( $age_group ) {
				case '7-9':
					return array( 'min' => 7, 'max' => 9 );
				case '10-12':
					return array( 'min' => 10, 'max' => 12 );
				case '4-6':
				default:
					return array( 'min' => 4, 'max' => 6 );
			}
		}

		/**
		 * Reading level cap per age group.
		 * - 4-6: 1
		 * - 7-9: 2
		 * - 10-12: 3
		 *
		 * @param string $age_group Age group.
		 * @return int
		 */
		public static function get_reading_level_cap( $age_group ) {
			$age_group = self::normalize_age_group( $age_group );

			switch ( $age_group ) {
				case '7-9':
					return 2;
				case '10-12':
					return 3;
				case '4-6':
				default:
					return 1;
			}
		}

		/**
		 * Get recommended session defaults (questions count + seconds) for an age group.
		 * Reads from settings/options set by Activator/Settings.
		 *
		 * @param string $age_group Age group.
		 * @return array{questions:int,seconds:int}
		 */
		public static function get_session_defaults( $age_group ) {
			$age_group = self::normalize_age_group( $age_group );

			// Fall back gracefully if settings class is not loaded.
			$get = function( $option, $default ) {
				if ( class_exists( 'KQAS_Settings' ) && is_callable( array( 'KQAS_Settings', 'get' ) ) ) {
					return KQAS_Settings::get( $option, $default );
				}
				return get_option( $option, $default );
			};

			if ( '7-9' === $age_group ) {
				return array(
					'questions' => (int) $get( 'kqas_session_7_9_questions', 8 ),
					'seconds'   => (int) $get( 'kqas_session_7_9_seconds', 360 ),
				);
			}

			if ( '10-12' === $age_group ) {
				return array(
					'questions' => (int) $get( 'kqas_session_10_12_questions', 12 ),
					'seconds'   => (int) $get( 'kqas_session_10_12_seconds', 600 ),
				);
			}

			return array(
				'questions' => (int) $get( 'kqas_session_4_6_questions', 5 ),
				'seconds'   => (int) $get( 'kqas_session_4_6_seconds', 180 ),
			);
		}

		/**
		 * Select question IDs suitable for the given age group.
		 *
		 * Rules:
		 * - Age overlap: question range overlaps target range.
		 * - Reading level: <= cap (missing reading level treated as 1).
		 * - Difficulty: optional filter (easy|medium|hard) or empty for any.
		 *
		 * Notes:
		 * - If a question has no age range saved, we INCLUDE it (so legacy/quick content still works).
		 *
		 * @param string $age_group         Age group.
		 * @param int    $limit             How many questions.
		 * @param array  $args              Extra filters:
		 *                                 - difficulty (string)
		 *                                 - skill_ids (int[]) taxonomy term IDs (kqas_skill)
		 *                                 - exclude_ids (int[])
		 * @return int[] Question post IDs.
		 */
		public static function select_question_ids( $age_group, $limit, $args = array() ) {
			if ( ! class_exists( 'KQAS_Post_Types' ) ) {
				return array();
			}

			$age_group = self::normalize_age_group( $age_group );
			$limit     = max( 1, (int) $limit );

			$target = self::get_target_age_range( $age_group );
			$cap    = self::get_reading_level_cap( $age_group );

			$difficulty = '';
			if ( isset( $args['difficulty'] ) ) {
				$difficulty = sanitize_text_field( (string) $args['difficulty'] );
			}
			$allowed_difficulty = array( '', 'easy', 'medium', 'hard' );
			if ( ! in_array( $difficulty, $allowed_difficulty, true ) ) {
				$difficulty = '';
			}

			$exclude_ids = array();
			if ( ! empty( $args['exclude_ids'] ) && is_array( $args['exclude_ids'] ) ) {
				$exclude_ids = array_map( 'intval', $args['exclude_ids'] );
				$exclude_ids = array_filter( $exclude_ids );
			}

			$meta_query = array(
				'relation' => 'AND',
				// Age overlap OR missing age meta (include legacy questions).
				array(
					'relation' => 'OR',
					// Proper overlap: age_min <= target_max AND age_max >= target_min.
					array(
						'relation' => 'AND',
						array(
							'key'     => '_kqas_age_min',
							'compare' => '<=',
							'value'   => (int) $target['max'],
							'type'    => 'NUMERIC',
						),
						array(
							'key'     => '_kqas_age_max',
							'compare' => '>=',
							'value'   => (int) $target['min'],
							'type'    => 'NUMERIC',
						),
					),
					// If age meta is missing, allow the question (MVP-friendly).
					array(
						'key'     => '_kqas_age_min',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_kqas_age_max',
						'compare' => 'NOT EXISTS',
					),
				),
				// Reading level <= cap OR missing (treat as 1).
				array(
					'relation' => 'OR',
					array(
						'key'     => '_kqas_reading_level',
						'compare' => '<=',
						'value'   => (int) $cap,
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_kqas_reading_level',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			// Optional difficulty.
			if ( '' !== $difficulty ) {
				$meta_query[] = array(
					'key'     => '_kqas_difficulty',
					'compare' => '=',
					'value'   => $difficulty,
				);
			}

			$tax_query = array();
			if ( ! empty( $args['skill_ids'] ) && is_array( $args['skill_ids'] ) && class_exists( 'KQAS_Taxonomies' ) ) {
				$skill_ids = array_map( 'intval', $args['skill_ids'] );
				$skill_ids = array_filter( $skill_ids );

				if ( ! empty( $skill_ids ) ) {
					$tax_query[] = array(
						'taxonomy' => KQAS_Taxonomies::TAX_SKILL,
						'field'    => 'term_id',
						'terms'    => $skill_ids,
					);
				}
			}

			$query_args = array(
				'post_type'           => KQAS_Post_Types::CPT_QUESTION,
				'post_status'         => 'publish',
				'fields'              => 'ids',
				'posts_per_page'      => $limit,
				'orderby'             => 'rand',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'meta_query'          => $meta_query,
				'post__not_in'        => $exclude_ids,
			);

			if ( ! empty( $tax_query ) ) {
				$query_args['tax_query'] = $tax_query;
			}

			/**
			 * Filter question selection query args before WP_Query.
			 *
			 * @param array  $query_args
			 * @param string $age_group
			 * @param int    $limit
			 * @param array  $args
			 */
			$query_args = apply_filters( 'kqas_age_rules_question_query_args', $query_args, $age_group, $limit, $args );

			$q = new WP_Query( $query_args );

			$ids = array();
			if ( ! empty( $q->posts ) && is_array( $q->posts ) ) {
				$ids = array_map( 'intval', $q->posts );
				$ids = array_filter( $ids );
			}

			return $ids;
		}
	}

endif;
