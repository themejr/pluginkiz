<?php
/**
 * REST API endpoints for Kids Mode (MVP).
 *
 * Namespace: /wp-json/kqas/v1
 *
 * Endpoints:
 * - POST /start-session   (kid_code, quiz_id?, nickname?)
 * - GET  /session-plan    (session_id, session_token)
 * - POST /attempt         (session_id, session_token, question_id, is_correct, time_spent_seconds?)
 * - POST /finish          (session_id, session_token)
 * - GET  /snapshot        (kid_code, days?)
 *
 * Notes:
 * - These endpoints are public (no WP login), protected by session_token for session routes.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_REST', false ) ) :

	final class KQAS_REST {

		const NS = 'kqas/v1';

		/**
		 * Register REST routes.
		 *
		 * @return void
		 */
		public function register_routes() {

			register_rest_route(
				self::NS,
				'/start-session',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'start_session' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'kid_code' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
								'validate_callback' => array( $this, 'validate_required_non_empty' ),
							),
							'quiz_id' => array(
								'required'          => false,
								'type'              => 'integer',
								'default'           => 0,
								'sanitize_callback' => 'absint',
							),
							'nickname' => array(
								'required'          => false,
								'type'              => 'string',
								'default'           => '',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
							),
						),
					),
				)
			);

			register_rest_route(
				self::NS,
				'/session-plan',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'session_plan' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'session_id' => array(
								'required'          => true,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => array( $this, 'validate_required_positive_int' ),
							),
							'session_token' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
								'validate_callback' => array( $this, 'validate_required_non_empty' ),
							),
						),
					),
				)
			);

			register_rest_route(
				self::NS,
				'/attempt',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'attempt' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'session_id' => array(
								'required'          => true,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => array( $this, 'validate_required_positive_int' ),
							),
							'session_token' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
								'validate_callback' => array( $this, 'validate_required_non_empty' ),
							),
							'question_id' => array(
								'required'          => true,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => array( $this, 'validate_required_positive_int' ),
							),
							'is_correct' => array(
								'required'          => true,
								'type'              => 'boolean',
								'sanitize_callback' => array( $this, 'sanitize_bool' ),
							),
							'time_spent_seconds' => array(
								'required'          => false,
								'type'              => 'integer',
								'default'           => 0,
								'sanitize_callback' => 'absint',
							),
						),
					),
				)
			);

			register_rest_route(
				self::NS,
				'/finish',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'finish' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'session_id' => array(
								'required'          => true,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => array( $this, 'validate_required_positive_int' ),
							),
							'session_token' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
								'validate_callback' => array( $this, 'validate_required_non_empty' ),
							),
						),
					),
				)
			);

			register_rest_route(
				self::NS,
				'/snapshot',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'snapshot' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'kid_code' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( $this, 'sanitize_text_nullable' ),
								'validate_callback' => array( $this, 'validate_required_non_empty' ),
							),
							'days' => array(
								'required'          => false,
								'type'              => 'integer',
								'default'           => 0,
								'sanitize_callback' => 'absint',
							),
						),
					),
				)
			);
		}

		/**
		 * POST /start-session
		 */
		public function start_session( WP_REST_Request $request ) {
			if ( ! class_exists( 'KQAS_Session' ) ) {
				return $this->err( 'kqas_missing_dependency', __( 'Session module is missing.', 'kidquiz-age-smart' ), 500 );
			}

			$kid_code = (string) $request->get_param( 'kid_code' );
			$quiz_id  = (int) $request->get_param( 'quiz_id' );
			$nickname = (string) $request->get_param( 'nickname' );

			// Use 0 as "no quiz selected" to avoid NULL passing around.
			$quiz_id = ( $quiz_id > 0 ) ? $quiz_id : 0;

			$res = KQAS_Session::start( $kid_code, $quiz_id, $nickname );
			if ( is_wp_error( $res ) ) {
				return $this->err( $res->get_error_code(), $res->get_error_message(), 400 );
			}

			return rest_ensure_response( $res );
		}

		/**
		 * GET /session-plan
		 */
		public function session_plan( WP_REST_Request $request ) {
			if ( ! class_exists( 'KQAS_Session' ) ) {
				return $this->err( 'kqas_missing_dependency', __( 'Session module is missing.', 'kidquiz-age-smart' ), 500 );
			}

			$session_id    = (int) $request->get_param( 'session_id' );
			$session_token = (string) $request->get_param( 'session_token' );

			$plan = KQAS_Session::get_session_plan( $session_id, $session_token );
			if ( is_wp_error( $plan ) ) {
				return $this->err( $plan->get_error_code(), $plan->get_error_message(), 400 );
			}

			return rest_ensure_response(
				array(
					'session_id'         => $session_id,
					'quiz_id'            => isset( $plan['quiz_id'] ) ? (int) $plan['quiz_id'] : 0,
					'age_group'          => isset( $plan['age_group'] ) ? (string) $plan['age_group'] : '',
					'reward_mode'        => isset( $plan['reward_mode'] ) ? (string) $plan['reward_mode'] : '',
					'time_limit_seconds' => isset( $plan['time_limit_seconds'] ) ? (int) $plan['time_limit_seconds'] : 0,
					'questions_count'    => isset( $plan['questions_count'] ) ? (int) $plan['questions_count'] : 0,
					'question_ids'       => array_values( array_map( 'intval', (array) ( isset( $plan['question_ids'] ) ? $plan['question_ids'] : array() ) ) ),
				)
			);
		}

		/**
		 * POST /attempt
		 */
		public function attempt( WP_REST_Request $request ) {
			if ( ! class_exists( 'KQAS_Session' ) ) {
				return $this->err( 'kqas_missing_dependency', __( 'Session module is missing.', 'kidquiz-age-smart' ), 500 );
			}

			$session_id    = (int) $request->get_param( 'session_id' );
			$session_token = (string) $request->get_param( 'session_token' );
			$question_id   = (int) $request->get_param( 'question_id' );
			$is_correct    = (bool) $request->get_param( 'is_correct' );
			$time_spent    = (int) $request->get_param( 'time_spent_seconds' );

			$res = KQAS_Session::record_attempt( $session_id, $session_token, $question_id, $is_correct, $time_spent );
			if ( is_wp_error( $res ) ) {
				return $this->err( $res->get_error_code(), $res->get_error_message(), 400 );
			}

			return rest_ensure_response( array( 'ok' => true ) );
		}

		/**
		 * POST /finish
		 */
		public function finish( WP_REST_Request $request ) {
			if ( ! class_exists( 'KQAS_Session' ) ) {
				return $this->err( 'kqas_missing_dependency', __( 'Session module is missing.', 'kidquiz-age-smart' ), 500 );
			}

			$session_id    = (int) $request->get_param( 'session_id' );
			$session_token = (string) $request->get_param( 'session_token' );

			$res = KQAS_Session::finish( $session_id, $session_token );
			if ( is_wp_error( $res ) ) {
				return $this->err( $res->get_error_code(), $res->get_error_message(), 400 );
			}

			return rest_ensure_response( $res );
		}

		/**
		 * GET /snapshot
		 */
		public function snapshot( WP_REST_Request $request ) {
			if ( ! class_exists( 'KQAS_Parent_Snapshot' ) ) {
				return $this->err( 'kqas_missing_dependency', __( 'Snapshot module is missing.', 'kidquiz-age-smart' ), 500 );
			}

			$kid_code = (string) $request->get_param( 'kid_code' );
			$days     = (int) $request->get_param( 'days' );

			$args = array();
			if ( $days > 0 ) {
				$args['days'] = $days;
			}

			$res = KQAS_Parent_Snapshot::get_snapshot( $kid_code, $args );
			if ( is_wp_error( $res ) ) {
				return $this->err( $res->get_error_code(), $res->get_error_message(), 400 );
			}

			return rest_ensure_response( $res );
		}

		/**
		 * Helpers
		 */

		/**
		 * Prevent PHP 8.1+ deprecated warnings by never passing NULL into sanitize_text_field().
		 *
		 * @param mixed $value Value.
		 * @return string
		 */
		public function sanitize_text_nullable( $value ) {
			if ( null === $value ) {
				return '';
			}
			return sanitize_text_field( (string) $value );
		}

		/**
		 * Validate required, non-empty string params.
		 *
		 * @param mixed           $value   Value.
		 * @param WP_REST_Request $request Request.
		 * @param string          $param   Param name.
		 * @return bool
		 */
		public function validate_required_non_empty( $value, $request, $param ) {
			$value = ( null === $value ) ? '' : (string) $value;
			return ( '' !== trim( $value ) );
		}

		/**
		 * Validate required positive integer params.
		 *
		 * @param mixed           $value   Value.
		 * @param WP_REST_Request $request Request.
		 * @param string          $param   Param name.
		 * @return bool
		 */
		public function validate_required_positive_int( $value, $request, $param ) {
			$value = (int) $value;
			return ( $value > 0 );
		}

		public function sanitize_bool( $value ) {
			if ( is_bool( $value ) ) {
				return $value;
			}
			if ( is_string( $value ) ) {
				$v = strtolower( trim( $value ) );
				return in_array( $v, array( '1', 'true', 'yes', 'on' ), true );
			}
			return ! empty( $value );
		}

		private function err( $code, $message, $status ) {
			return new WP_REST_Response(
				array(
					'code'    => (string) $code,
					'message' => (string) $message,
				),
				(int) $status
			);
		}
	}

endif;
