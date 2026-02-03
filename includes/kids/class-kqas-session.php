<?php
/**
 * Session manager (Kids Mode runtime storage).
 *
 * Responsibilities (MVP):
 * - Start a session: validate kid code, build session plan, create DB row in wp_kqas_sessions
 * - Store attempts per question in wp_kqas_attempts
 * - Finish a session: compute correct_count, set ended_at/status
 *
 * Tables (created by KQAS_Activator):
 * - {$wpdb->prefix}kqas_sessions
 * - {$wpdb->prefix}kqas_attempts
 *
 * Notes:
 * - This class is storage-focused. UI/REST will call it.
 * - For kids privacy, IP/user-agent storage is controlled by settings (kqas_store_ip / kqas_store_user_agent)
 *
 * @package KidQuiz_Age_Smart
 */

define( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Session', false ) ) :

	final class KQAS_Session {

		/**
		 * Sessions table (without prefix).
		 */
		const TABLE_SESSIONS = 'kqas_sessions';

		/**
		 * Attempts table (without prefix).
		 */
		const TABLE_ATTEMPTS = 'kqas_attempts';

		/**
		 * Status values.
		 */
		const STATUS_STARTED   = 'started';
		const STATUS_FINISHED  = 'finished';
		const STATUS_ABANDONED = 'abandoned';

		/**
		 * Get sessions table name with prefix.
		 *
		 * @return string
		 */
		private static function sessions_table() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_SESSIONS;
		}

		/**
		 * Get attempts table name with prefix.
		 *
		 * @return string
		 */
		private static function attempts_table() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE_ATTEMPTS;
		}

		/**
		 * Get option with fallback (uses KQAS_Settings if available).
		 *
		 * @param string $option_name Option name.
		 * @param mixed  $default     Default.
		 * @return mixed
		 */
		private static function opt( $option_name, $default ) {
			if ( class_exists( 'KQAS_Settings' ) && is_callable( array( 'KQAS_Settings', 'get' ) ) ) {
				return KQAS_Settings::get( $option_name, $default );
			}
			return get_option( $option_name, $default );
		}

		/**
		 * Start a session.
		 *
		 * @param string   $kid_code  Kid code (e.g., KID-4832).
		 * @param int|null $quiz_id   Quiz ID. If null, will pick default quiz.
		 * @param string   $nickname  Optional nickname (if allowed by settings).
		 *
		 * @return array|WP_Error {
		 *   session_id:int,
		 *   quiz_id:int,
		 *   age_group:string,
		 *   time_limit_seconds:int,
		 *   questions_count:int,
		 *   question_ids:int[],
		 *   reward_mode:string,
		 *   session_token:string
		 * }
		 */
		public static function start( $kid_code, $quiz_id = null, $nickname = '' ) {
			global $wpdb;

			if ( ! class_exists( 'KQAS_Kid_Codes' ) ) {
				return new WP_Error( 'kqas_missing_dependency', esc_html__( 'Kid Codes module is missing.', 'kidquiz-age-smart' ) );
			}

			$kid_code = KQAS_Kid_Codes::normalize_code( (string) $kid_code );
			if ( ! KQAS_Kid_Codes::is_valid_active_code( $kid_code ) ) {
				return new WP_Error( 'kqas_invalid_kid_code', esc_html__( 'Invalid Kid Code.', 'kidquiz-age-smart' ) );
			}

			if ( null === $quiz_id ) {
				if ( class_exists( 'KQAS_Quiz_Generator' ) ) {
					$quiz_id = KQAS_Quiz_Generator::get_default_quiz_id();
				}
			}

			$quiz_id = (int) $quiz_id;
			if ( $quiz_id < 1 ) {
				return new WP_Error( 'kqas_missing_quiz', esc_html__( 'No quiz is available yet. Please ask the teacher to create a quiz.', 'kidquiz-age-smart' ) );
			}

			// Nickname policy.
			$allow_nickname = (int) self::opt( 'kqas_allow_nickname', 1 );
			$nickname       = sanitize_text_field( (string) $nickname );
			if ( 1 !== $allow_nickname ) {
				$nickname = '';
			}
			// Avoid passing NULL to wpdb->prepare() (PHP 8.1+ deprecated warnings).
			if ( '' === $nickname ) {
				$nickname = '';
			}

			if ( ! class_exists( 'KQAS_Quiz_Generator' ) ) {
				return new WP_Error( 'kqas_missing_dependency', esc_html__( 'Quiz generator is missing.', 'kidquiz-age-smart' ) );
			}

			$plan = KQAS_Quiz_Generator::build_session_plan( $quiz_id );

			if ( empty( $plan['question_ids'] ) ) {
				return new WP_Error(
					'kqas_no_questions',
					esc_html__( 'No suitable questions found for this quiz/age group. Please add questions to the bank.', 'kidquiz-age-smart' )
				);
			}

			// Ensure plan contains quiz_id (useful for REST session-plan endpoint).
			if ( ! isset( $plan['quiz_id'] ) ) {
				$plan['quiz_id'] = (int) $quiz_id;
			}

			$now = current_time( 'mysql' );

			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i
					 (kid_code, nickname, quiz_id, age_group, started_at, time_limit_seconds, questions_count, correct_count, status)
					 VALUES (%s, %s, %d, %s, %s, %d, %d, %d, %s)",
					$wpdb->prefix . 'kqas_sessions',
					$kid_code,
					$nickname,
					(int) $quiz_id,
					(string) $plan['age_group'],
					$now,
					(int) $plan['time_limit_seconds'],
					(int) $plan['questions_count'],
					0,
					self::STATUS_STARTED
				)
			);

			if ( false === $inserted ) {
				return new WP_Error( 'kqas_db_insert_failed', (string) $wpdb->last_error );
			}

			$session_id = (int) $wpdb->insert_id;

			// Generate token (used by REST calls to prevent session_id guessing).
			$token = wp_generate_password( 32, false, false );
			set_transient( self::token_transient_key( $session_id ), $token, 2 * DAY_IN_SECONDS );

			// Store plan in transient for quick retrieval (optional). Keep DB simple in MVP.
			self::store_session_plan_transient( $session_id, $plan );

			return array(
				'session_id'         => $session_id,
				'quiz_id'            => (int) $quiz_id,
				'age_group'          => (string) $plan['age_group'],
				'time_limit_seconds' => (int) $plan['time_limit_seconds'],
				'questions_count'    => (int) $plan['questions_count'],
				'question_ids'       => array_values( array_map( 'intval', (array) $plan['question_ids'] ) ),
				'reward_mode'        => (string) $plan['reward_mode'],
				'session_token'      => $token,
			);
		}

		/**
		 * Token transient key.
		 *
		 * @param int $session_id Session ID.
		 * @return string
		 */
		private static function token_transient_key( $session_id ) {
			return 'kqas_token_' . (int) $session_id;
		}

		/**
		 * Validate session token.
		 *
		 * @param int    $session_id Session ID.
		 * @param string $token Token.
		 * @return true|WP_Error
		 */
		private static function validate_token( $session_id, $token ) {
			$session_id = (int) $session_id;
			$token      = sanitize_text_field( (string) $token );

			if ( $session_id < 1 ) {
				return new WP_Error( 'kqas_invalid_input', esc_html__( 'Invalid session.', 'kidquiz-age-smart' ) );
			}

			if ( '' === $token ) {
				return new WP_Error( 'kqas_missing_token', esc_html__( 'Session token is required.', 'kidquiz-age-smart' ) );
			}

			$stored = get_transient( self::token_transient_key( $session_id ) );
			if ( ! is_string( $stored ) || '' === $stored || ! hash_equals( $stored, $token ) ) {
				return new WP_Error( 'kqas_invalid_token', esc_html__( 'Invalid session token.', 'kidquiz-age-smart' ) );
			}

			return true;
		}

		/**
		 * Record an attempt.
		 *
		 * @param int    $session_id Session ID.
		 * @param string $session_token Session token.
		 * @param int    $question_id Question ID.
		 * @param bool   $is_correct Is correct.
		 * @param int    $time_spent_seconds Optional.
		 * @return bool|WP_Error
		 */
		public static function record_attempt( $session_id, $session_token, $question_id, $is_correct, $time_spent_seconds = 0 ) {
			global $wpdb;

			$session_id         = (int) $session_id;
			$question_id        = (int) $question_id;
			$time_spent_seconds = (int) $time_spent_seconds;

			if ( $session_id < 1 || $question_id < 1 ) {
				return new WP_Error( 'kqas_invalid_input', esc_html__( 'Invalid session/question.', 'kidquiz-age-smart' ) );
			}

			$ok = self::validate_token( $session_id, $session_token );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			// Ensure session exists and not finished.
			$session = self::get_session( $session_id );
			if ( is_wp_error( $session ) ) {
				return $session;
			}
			if ( self::STATUS_STARTED !== $session['status'] ) {
				return new WP_Error( 'kqas_session_closed', esc_html__( 'This session is closed.', 'kidquiz-age-smart' ) );
			}

			// Ensure question belongs to the session plan (best for data integrity).
			$plan = get_transient( self::plan_transient_key( $session_id ) );
			if ( ! is_array( $plan ) || empty( $plan['question_ids'] ) ) {
				return new WP_Error( 'kqas_plan_missing', esc_html__( 'Session plan not found (it may have expired).', 'kidquiz-age-smart' ) );
			}
			$plan_qids = array_map( 'intval', (array) $plan['question_ids'] );
			if ( ! in_array( $question_id, $plan_qids, true ) ) {
				return new WP_Error( 'kqas_question_outside_plan', esc_html__( 'This question is not part of the current session.', 'kidquiz-age-smart' ) );
			}

			$now = current_time( 'mysql' );

			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i
					 (session_id, question_id, is_correct, time_spent_seconds, created_at)
					 VALUES (%d, %d, %d, %d, %s)",
					$wpdb->prefix . 'kqas_attempts',
					$session_id,
					$question_id,
					$is_correct ? 1 : 0,
					max( 0, min( 3600, $time_spent_seconds ) ),
					$now
				)
			);

			if ( false === $inserted ) {
				return new WP_Error( 'kqas_db_insert_failed', (string) $wpdb->last_error );
			}

			return true;
		}

		/**
		 * Finish a session (compute correct count from attempts).
		 *
		 * @param int    $session_id Session ID.
		 * @param string $session_token Session token.
		 * @return array|WP_Error {
		 *   session_id:int,
		 *   correct_count:int,
		 *   questions_count:int,
		 *   reward:array|null
		 * }
		 */
		public static function finish( $session_id, $session_token ) {
			global $wpdb;

			$session_id = (int) $session_id;
			if ( $session_id < 1 ) {
				return new WP_Error( 'kqas_invalid_input', esc_html__( 'Invalid session.', 'kidquiz-age-smart' ) );
			}

			$ok = self::validate_token( $session_id, $session_token );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			$session = self::get_session( $session_id );
			if ( is_wp_error( $session ) ) {
				return $session;
			}

			if ( self::STATUS_FINISHED === $session['status'] ) {
				return array(
					'session_id'      => $session_id,
					'correct_count'   => (int) $session['correct_count'],
					'questions_count' => (int) $session['questions_count'],
					'reward'          => self::build_reward_for_session( $session ),
				);
			}

			// Count distinct correct questions (better than counting every correct attempt).
			$correct_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT question_id)
					 FROM %i
					 WHERE session_id = %d AND is_correct = 1",
					$wpdb->prefix . 'kqas_attempts',
					$session_id
				)
			);

			// Cap to questions_count to keep sane.
			$questions_count = isset( $session['questions_count'] ) ? (int) $session['questions_count'] : 0;
			if ( $questions_count > 0 ) {
				$correct_count = min( $correct_count, $questions_count );
			}

			$now = current_time( 'mysql' );

			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE %i
					 SET correct_count = %d, ended_at = %s, status = %s
					 WHERE id = %d",
					$wpdb->prefix . 'kqas_sessions',
					$correct_count,
					$now,
					self::STATUS_FINISHED,
					$session_id
				)
			);

			if ( false === $updated ) {
				return new WP_Error( 'kqas_db_update_failed', (string) $wpdb->last_error );
			}

			$session = self::get_session( $session_id );
			if ( is_wp_error( $session ) ) {
				return $session;
			}

			return array(
				'session_id'      => $session_id,
				'correct_count'   => (int) $session['correct_count'],
				'questions_count' => (int) $session['questions_count'],
				'reward'          => self::build_reward_for_session( $session ),
			);
		}

		/**
		 * Get a session row.
		 *
		 * @param int $session_id Session ID.
		 * @return array|WP_Error
		 */
		public static function get_session( $session_id ) {
			global $wpdb;

			$session_id = (int) $session_id;
			if ( $session_id < 1 ) {
				return new WP_Error( 'kqas_invalid_input', esc_html__( 'Invalid session.', 'kidquiz-age-smart' ) );
			}

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, kid_code, nickname, quiz_id, age_group, started_at, ended_at, time_limit_seconds,
						questions_count, correct_count, status
					 FROM %i
					 WHERE id = %d
					 LIMIT 1",
					$wpdb->prefix . 'kqas_sessions',
					$session_id
				)
			,
				ARRAY_A
			);

			if ( ! is_array( $row ) ) {
				return new WP_Error( 'kqas_session_not_found', esc_html__( 'Session not found.', 'kidquiz-age-smart' ) );
			}

			return $row;
		}

		/**
		 * Get session plan (question order) for a session.
		 * Uses transient storage set in start().
		 *
		 * @param int    $session_id Session ID.
		 * @param string $session_token Session token.
		 * @return array|WP_Error Plan array or error.
		 */
		public static function get_session_plan( $session_id, $session_token ) {
			$session_id = (int) $session_id;
			if ( $session_id < 1 ) {
				return new WP_Error( 'kqas_invalid_input', esc_html__( 'Invalid session.', 'kidquiz-age-smart' ) );
			}

			$ok = self::validate_token( $session_id, $session_token );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			$key  = self::plan_transient_key( $session_id );
			$plan = get_transient( $key );

			if ( ! is_array( $plan ) || empty( $plan['question_ids'] ) ) {
				return new WP_Error( 'kqas_plan_missing', esc_html__( 'Session plan not found (it may have expired).', 'kidquiz-age-smart' ) );
			}

			return $plan;
		}

		/**
		 * Store session plan as transient (MVP).
		 *
		 * @param int   $session_id Session ID.
		 * @param array $plan Plan.
		 * @return void
		 */
		private static function store_session_plan_transient( $session_id, $plan ) {
			$session_id = (int) $session_id;
			if ( $session_id < 1 ) {
				return;
			}

			// Expire after 2 days (enough for kid flow).
			set_transient( self::plan_transient_key( $session_id ), $plan, 2 * DAY_IN_SECONDS );
		}

		/**
		 * Transient key for plans.
		 *
		 * @param int $session_id Session ID.
		 * @return string
		 */
		private static function plan_transient_key( $session_id ) {
			return 'kqas_plan_' . (int) $session_id;
		}

		/**
		 * Build reward for a finished session.
		 *
		 * @param array $session Session row.
		 * @return array|null
		 */
		private static function build_reward_for_session( $session ) {
			if ( ! is_array( $session ) ) {
				return null;
			}

			if ( ! class_exists( 'KQAS_Rewards' ) ) {
				return null;
			}

			$age_group = isset( $session['age_group'] ) ? (string) $session['age_group'] : '4-6';

			// Prefer quiz reward meta if possible; otherwise default by age group.
			$reward_mode = '';
			if ( ! empty( $session['quiz_id'] ) ) {
				$reward_mode = (string) get_post_meta( (int) $session['quiz_id'], '_kqas_reward_mode', true );
			}
			$reward_mode = sanitize_text_field( $reward_mode );

			if ( ! in_array( $reward_mode, array( 'stickers', 'badges' ), true ) ) {
				$reward_mode = KQAS_Rewards::get_default_reward_mode_for_age_group( $age_group );
			}

			$context = array(
				'correct'   => isset( $session['correct_count'] ) ? (int) $session['correct_count'] : 0,
				'questions' => isset( $session['questions_count'] ) ? (int) $session['questions_count'] : 0,
			);

			return KQAS_Rewards::pick_reward( $reward_mode, $age_group, $context );
		}
	}

endif;