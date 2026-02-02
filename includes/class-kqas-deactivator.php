<?php
/**
 * Fired during plugin deactivation.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Deactivator', false ) ) :

	final class KQAS_Deactivator {

		/**
		 * Deactivation handler.
		 *
		 * Note: Envato/WordPress best practice = do NOT delete data on deactivation.
		 * Data removal should be in uninstall.php only, and only if user explicitly uninstalls.
		 *
		 * @return void
		 */
		public static function deactivate() {
			// If you added rewrite rules, they should be flushed by the main plugin file hook too.
			// Keep this minimal; do not delete options or tables here.
		}
	}

endif;
