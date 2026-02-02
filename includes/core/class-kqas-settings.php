<?php
/**
 * Settings for KidQuiz Age-Smart.
 *
 * Registers options and provides a helper to get sanitized values.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Settings', false ) ) :

	final class KQAS_Settings {

		/**
		 * Option group (settings API).
		 */
		const OPTION_GROUP = 'kqas_settings_group';

		/**
		 * Option page slug (used by admin page).
		 */
		const OPTION_PAGE = 'kqas-settings';

		/**
		 * Register plugin settings.
		 *
		 * @return void
		 */
		public function register_settings() {

			// Kid Codes.
			register_setting(
				self::OPTION_GROUP,
				'kqas_kid_code_prefix',
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_code_prefix' ),
					'default'           => 'KID',
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_kid_code_length',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_code_length' ),
					'default'           => 4,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_kid_code_max_active',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_positive_int' ),
					'default'           => 5000,
				)
			);

			// Parent snapshot.
			register_setting(
				self::OPTION_GROUP,
				'kqas_snapshot_days',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_snapshot_days' ),
					'default'           => 7,
				)
			);

			// Privacy.
			register_setting(
				self::OPTION_GROUP,
				'kqas_allow_nickname',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_bool_int' ),
					'default'           => 1,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_store_ip',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_bool_int' ),
					'default'           => 0,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_store_user_agent',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_bool_int' ),
					'default'           => 0,
				)
			);

			// Data removal policy (Envato-friendly).
			register_setting(
				self::OPTION_GROUP,
				'kqas_delete_data_on_uninstall',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_bool_int' ),
					'default'           => 0,
				)
			);

			// Session defaults (editable, but with safe bounds).
			$this->register_session_defaults();
		}

		/**
		 * Register session default options for each age group.
		 *
		 * @return void
		 */
		private function register_session_defaults() {
			// 4-6.
			register_setting(
				self::OPTION_GROUP,
				'kqas_session_4_6_questions',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_questions_count' ),
					'default'           => 5,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_session_4_6_seconds',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_seconds' ),
					'default'           => 180,
				)
			);

			// 7-9.
			register_setting(
				self::OPTION_GROUP,
				'kqas_session_7_9_questions',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_questions_count' ),
					'default'           => 8,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_session_7_9_seconds',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_seconds' ),
					'default'           => 360,
				)
			);

			// 10-12.
			register_setting(
				self::OPTION_GROUP,
				'kqas_session_10_12_questions',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_questions_count' ),
					'default'           => 12,
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_session_10_12_seconds',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_seconds' ),
					'default'           => 600,
				)
			);

			// Rewards defaults.
			register_setting(
				self::OPTION_GROUP,
				'kqas_reward_4_6',
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_reward' ),
					'default'           => 'stickers',
				)
			);

			register_setting(
				self::OPTION_GROUP,
				'kqas_reward_7_12',
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_reward' ),
					'default'           => 'badges',
				)
			);
		}

		/**
		 * Get option with fallback default.
		 *
		 * @param string $name    Full option name (e.g., kqas_snapshot_days).
		 * @param mixed  $default Default value.
		 * @return mixed
		 */
		public static function get( $name, $default = null ) {
			$value = get_option( $name, null );
			return ( null === $value ) ? $default : $value;
		}

		/**
		 * Sanitize kid code prefix: uppercase A-Z, 2-8 chars.
		 *
		 * @param string $value Raw.
		 * @return string
		 */
		public function sanitize_code_prefix( $value ) {
			$value = sanitize_text_field( $value );
			$value = strtoupper( $value );
			$value = preg_replace( '/[^A-Z]/', '', $value );

			$len = strlen( $value );
			if ( $len < 2 ) {
				return 'KID';
			}
			if ( $len > 8 ) {
				return substr( $value, 0, 8 );
			}

			return $value;
		}

		/**
		 * Sanitize code length (digits count): 3-8.
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_code_length( $value ) {
			$value = (int) $value;
			if ( $value < 3 ) {
				return 3;
			}
			if ( $value > 8 ) {
				return 8;
			}
			return $value;
		}

		/**
		 * Sanitize snapshot days: 1-30.
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_snapshot_days( $value ) {
			$value = (int) $value;
			if ( $value < 1 ) {
				return 1;
			}
			if ( $value > 30 ) {
				return 30;
			}
			return $value;
		}

		/**
		 * Sanitize boolean stored as int (0/1).
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_bool_int( $value ) {
			return ( ! empty( $value ) ) ? 1 : 0;
		}

		/**
		 * Sanitize reward mode.
		 *
		 * @param string $value Raw.
		 * @return string
		 */
		public function sanitize_reward( $value ) {
			$value = sanitize_text_field( $value );
			$allowed = array( 'stickers', 'badges' );
			return in_array( $value, $allowed, true ) ? $value : 'stickers';
		}

		/**
		 * Sanitize questions count: 1-50.
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_questions_count( $value ) {
			$value = (int) $value;
			if ( $value < 1 ) {
				return 1;
			}
			if ( $value > 50 ) {
				return 50;
			}
			return $value;
		}

		/**
		 * Sanitize seconds: 30-3600.
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_seconds( $value ) {
			$value = (int) $value;
			if ( $value < 30 ) {
				return 30;
			}
			if ( $value > 3600 ) {
				return 3600;
			}
			return $value;
		}

		/**
		 * Sanitize positive int with max cap (1-1,000,000).
		 *
		 * @param mixed $value Raw.
		 * @return int
		 */
		public function sanitize_positive_int( $value ) {
			$value = (int) $value;
			if ( $value < 1 ) {
				return 1;
			}
			if ( $value > 1000000 ) {
				return 1000000;
			}
			return $value;
		}
	}

endif;
