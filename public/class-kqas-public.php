<?php
/**
 * Public-facing functionality for KidQuiz Age-Smart.
 *
 * - Enqueue public assets (Kids Mode UI assets, optional)
 * - Add body class helpers
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Public', false ) ) :

	final class KQAS_Public {

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		private $plugin_name;

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		private $version;

		/**
		 * Constructor.
		 *
		 * @param string $plugin_name Plugin name/slug.
		 * @param string $version Plugin version.
		 */
		public function __construct( $plugin_name, $version ) {
			$this->plugin_name = (string) $plugin_name;
			$this->version     = (string) $version;
		}

		/**
		 * Enqueue public styles.
		 *
		 * @return void
		 */
		public function enqueue_styles() {
			$should_load = false;

			if ( (int) get_query_var( 'kqas_kids' ) === 1 ) {
				$should_load = true;
			} elseif ( is_singular() ) {
				global $post;
				if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'kidquiz_kids_mode' ) ) {
					$should_load = true;
				}
			}

			if ( ! $should_load ) {
				return;
			}

			$rel  = 'assets/public/css/kids-mode.css';
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . $rel;

			if ( file_exists( $path ) ) {
				wp_enqueue_style( 'kqas-public', trailingslashit( KQAS_PLUGIN_URL ) . $rel, array(), $this->version );
			}
		}

		public function enqueue_scripts() {
			$should_load = false;

			if ( (int) get_query_var( 'kqas_kids' ) === 1 ) {
				$should_load = true;
			} elseif ( is_singular() ) {
				global $post;
				if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'kidquiz_kids_mode' ) ) {
					$should_load = true;
				}
			}

			if ( ! $should_load ) {
				return;
			}

			$rel  = 'assets/public/js/kids-mode.js';
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . $rel;

			if ( file_exists( $path ) ) {
				wp_enqueue_script( 'kqas-public', trailingslashit( KQAS_PLUGIN_URL ) . $rel, array(), $this->version, true );

				wp_localize_script(
					'kqas-public',
					'KQAS',
					array(
						'restUrl' => esc_url_raw( rest_url( 'kqas/v1' ) ),
						'nonce'   => wp_create_nonce( 'wp_rest' ),
					)
				);
			}
		}

		/**
		 * Add body class for Kids Mode pages (if shortcode is used).
		 *
		 * @param string[] $classes Classes.
		 * @return string[]
		 */
		public function body_class( $classes ) {
			if ( is_singular() ) {
				global $post;
				if ( $post instanceof WP_Post ) {
					if ( has_shortcode( (string) $post->post_content, 'kidquiz_kids_mode' ) ) {
						$classes[] = 'kqas-kids-mode';
					}
				}
			}
			return $classes;
		}
	}

endif;
