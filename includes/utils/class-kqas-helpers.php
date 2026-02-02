<?php
/**
 * General helpers for KidQuiz Age-Smart (views + context detection).
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Helpers', false ) ) :

	final class KQAS_Helpers {

		/**
		 * Render a PHP view file with variables.
		 *
		 * @param string $abs_path Absolute file path.
		 * @param array  $vars Variables.
		 * @return string
		 */
		public static function render_view( $abs_path, $vars = array() ) {
			$abs_path = (string) $abs_path;

			if ( '' === $abs_path || ! file_exists( $abs_path ) ) {
				return '';
			}

			if ( is_array( $vars ) && ! empty( $vars ) ) {
				extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			}

			ob_start();
			include $abs_path;
			return (string) ob_get_clean();
		}

		/**
		 * Render an admin view under /admin/views/.
		 *
		 * @param string $view View filename.
		 * @param array  $vars Vars.
		 * @return string
		 */
		public static function render_admin_view( $view, $vars = array() ) {
			$view = basename( (string) $view );
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . 'admin/views/' . $view;

			return self::render_view( $path, $vars );
		}

		/**
		 * Render a public view under /public/views/.
		 *
		 * @param string $view View filename.
		 * @param array  $vars Vars.
		 * @return string
		 */
		public static function render_public_view( $view, $vars = array() ) {
			$view = basename( (string) $view );
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . 'public/views/' . $view;

			return self::render_view( $path, $vars );
		}

		/**
		 * True when current request is Kids Mode context (route or shortcode page).
		 *
		 * @return bool
		 */
		public static function is_kids_context() {
			if ( (int) get_query_var( 'kqas_kids' ) === 1 ) {
				return true;
			}

			if ( is_singular() ) {
				global $post;
				if ( $post instanceof WP_Post ) {
					$content = (string) $post->post_content;
					if ( has_shortcode( $content, 'kidquiz_kids_mode' ) ) {
						return true;
					}
				}
			}

			return false;
		}
	}

endif;
