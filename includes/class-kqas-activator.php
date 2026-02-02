<?php
/**
 * Fired during plugin activation.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Activator', false ) ) :

	final class KQAS_Activator {

		/**
		 * Activation handler.
		 *
		 * @return void
		 */
		public static function activate() {

			// Capabilities: keep MVP minimal; add later if needed.
			// Default options (Envato-friendly: predictable defaults, safe).
			$defaults = array(
				// Kid codes.
				'kid_code_prefix'         => 'KID',
				'kid_code_length'         => 4, // digits.
				'kid_code_max_active'     => 5000,

				// Session defaults per age group.
				'session_4_6_questions'   => 5,
				'session_4_6_seconds'     => 3 * 60,

				'session_7_9_questions'   => 8,
				'session_7_9_seconds'     => 6 * 60,

				'session_10_12_questions' => 12,
				'session_10_12_seconds'   => 10 * 60,

				// Reward defaults.
				'reward_4_6'              => 'stickers',
				'reward_7_12'             => 'badges',

				// Privacy defaults.
				'allow_nickname'          => 1,
				'store_ip'                => 0, // safer default for kids.
				'store_user_agent'        => 0,

				// Parent snapshot window.
				'snapshot_days'           => 7,
			);

			foreach ( $defaults as $key => $value ) {
				$option_name = 'kqas_' . $key;
				if ( false === get_option( $option_name, false ) ) {
					add_option( $option_name, $value, '', false ); // autoload = false.
				}
			}

			// Create DB tables (MVP minimal) — safe to skip if permissions fail.
			self::maybe_create_tables();

			// Register CPT before flushing rules (best-effort).
			if ( class_exists( 'KQAS_Post_Types' ) ) {
				$core_cpt = new KQAS_Post_Types();
				if ( method_exists( $core_cpt, 'register_post_types' ) ) {
					$core_cpt->register_post_types();
				}
			}

			// Flush rewrite rules (handled also in main file, but safe here too).
			if ( function_exists( 'flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
			}
		}

		/**
		 * Create required custom tables using dbDelta.
		 *
		 * Tables (MVP):
		 * - kqas_kid_codes: stores generated codes and status
		 * - kqas_sessions: stores quiz sessions (for snapshot/report)
		 * - kqas_attempts: per-question attempts (optional but useful for weak/strong skills later)
		 *
		 * @return void
		 */
		private static function maybe_create_tables() {
			global $wpdb;

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			$charset_collate = $wpdb->get_charset_collate();

			$kid_codes  = $wpdb->prefix . 'kqas_kid_codes';
			$sessions   = $wpdb->prefix . 'kqas_sessions';
			$attempts   = $wpdb->prefix . 'kqas_attempts';

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$sql_kid_codes = "CREATE TABLE {$kid_codes} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				kid_code VARCHAR(32) NOT NULL,
				label VARCHAR(100) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY kid_code (kid_code),
				KEY status (status)
			) {$charset_collate};";

			$sql_sessions = "CREATE TABLE {$sessions} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				kid_code VARCHAR(32) NOT NULL,
				nickname VARCHAR(100) NULL,
				quiz_id BIGINT(20) UNSIGNED NULL,
				age_group VARCHAR(10) NOT NULL,
				started_at DATETIME NOT NULL,
				ended_at DATETIME NULL,
				time_limit_seconds INT(11) NOT NULL DEFAULT 0,
				questions_count INT(11) NOT NULL DEFAULT 0,
				correct_count INT(11) NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL DEFAULT 'started',
				PRIMARY KEY  (id),
				KEY kid_code (kid_code),
				KEY quiz_id (quiz_id),
				KEY age_group (age_group),
				KEY started_at (started_at),
				KEY status (status)
			) {$charset_collate};";

			$sql_attempts = "CREATE TABLE {$attempts} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				session_id BIGINT(20) UNSIGNED NOT NULL,
				question_id BIGINT(20) UNSIGNED NOT NULL,
				is_correct TINYINT(1) NOT NULL DEFAULT 0,
				time_spent_seconds INT(11) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY session_id (session_id),
				KEY question_id (question_id),
				KEY is_correct (is_correct)
			) {$charset_collate};";
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

			dbDelta( $sql_kid_codes );
			dbDelta( $sql_sessions );
			dbDelta( $sql_attempts );

			// Store schema version for future upgrades/migrations.
			if ( false === get_option( 'kqas_db_version', false ) ) {
				add_option( 'kqas_db_version', '1.0.0', '', false );
			} else {
				update_option( 'kqas_db_version', '1.0.0', false );
			}
		}
	}

endif;
