<?php
/**
 * Define the internationalization functionality.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_I18n', false ) ) :

	/**
	 * Defines i18n for plugin.
	 */
	final class KQAS_I18n {

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain(
				'kidquiz-age-smart',
				false,
				dirname( KQAS_PLUGIN_BASENAME ) . '/languages'
			);
		}
	}

endif;
