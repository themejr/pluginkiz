<?php
/**
 * The core plugin class (orchestrator).
 *
 * Defines internationalization, admin/public hooks, REST, shortcodes, router.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS', false ) ) :

	/**
	 * Core plugin orchestrator.
	 */
	final class KQAS {

		/**
		 * Loader instance that coordinates WordPress hooks.
		 *
		 * @var KQAS_Loader
		 */
		protected $loader;

		/**
		 * Plugin slug / unique ID.
		 *
		 * @var string
		 */
		protected $plugin_name = 'kidquiz-age-smart';

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		protected $version;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->version = defined( 'KQAS_VERSION' ) ? KQAS_VERSION : '1.0.0';

			$this->load_dependencies();
			$this->set_locale();
			$this->define_core_hooks();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->define_api_hooks();
		}

		/**
		 * Load required dependencies.
		 *
		 * @return void
		 */
		private function load_dependencies() {
			// Loader is required for hook orchestration.
			if ( class_exists( 'KQAS_Loader' ) ) {
				$this->loader = new KQAS_Loader();
			} else {
				// Fail gracefully: no loader => plugin won't run hooks, but no fatal.
				$this->loader = null;
			}
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * @return void
		 */
		private function set_locale() {
			if ( ! $this->loader ) {
				return;
			}

			if ( class_exists( 'KQAS_I18n' ) ) {
				$plugin_i18n = new KQAS_I18n();
				$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
			}
		}

		/**
		 * Define hooks for core components that should run in both admin/public.
		 *
		 * @return void
		 */
		private function define_core_hooks() {
			if ( ! $this->loader ) {
				return;
			}

			// Register CPT/Taxonomies.
			if ( class_exists( 'KQAS_Post_Types' ) ) {
				$core_cpt = new KQAS_Post_Types();
				$this->loader->add_action( 'init', $core_cpt, 'register_post_types' );
			}

			if ( class_exists( 'KQAS_Taxonomies' ) ) {
				$core_tax = new KQAS_Taxonomies();
				$this->loader->add_action( 'init', $core_tax, 'register_taxonomies' );
			}

			// Metaboxes & saving.
			if ( is_admin() && class_exists( 'KQAS_Metaboxes' ) ) {
				$metaboxes = new KQAS_Metaboxes();
				$this->loader->add_action( 'add_meta_boxes', $metaboxes, 'register_metaboxes' );
				$this->loader->add_action( 'save_post', $metaboxes, 'save_metaboxes', 10, 2 );
			}

			// Settings.
			if ( is_admin() && class_exists( 'KQAS_Settings' ) ) {
				$settings = new KQAS_Settings();
				$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
			}
		}

		/**
		 * Register all of the hooks related to the admin area functionality.
		 *
		 * @return void
		 */
		private function define_admin_hooks() {
			if ( ! $this->loader || ! is_admin() ) {
				return;
			}

			if ( class_exists( 'KQAS_Admin' ) ) {
				$plugin_admin = new KQAS_Admin( $this->get_plugin_name(), $this->get_version() );

				$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
				$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

				$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_menu_pages' );

				// Optional: add “Settings” link in Plugins list.
				$this->loader->add_filter( 'plugin_action_links_' . KQAS_PLUGIN_BASENAME, $plugin_admin, 'action_links' );
			}
		}

		/**
		 * Register all of the hooks related to the public-facing functionality.
		 *
		 * @return void
		 */
		private function define_public_hooks() {
			if ( ! $this->loader ) {
				return;
			}

			if ( class_exists( 'KQAS_Public' ) ) {
				$plugin_public = new KQAS_Public( $this->get_plugin_name(), $this->get_version() );

				$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
				$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

				// Optional: template or body class helpers.
				$this->loader->add_filter( 'body_class', $plugin_public, 'body_class' );
			}

			// Shortcodes.
			if ( class_exists( 'KQAS_Shortcodes' ) ) {
				$shortcodes = new KQAS_Shortcodes();
				$this->loader->add_action( 'init', $shortcodes, 'register_shortcodes' );
			}

			// Router (pretty URLs).
			if ( class_exists( 'KQAS_Router' ) ) {
				$router = new KQAS_Router();
				$this->loader->add_action( 'init', $router, 'add_rewrite_rules' );
				$this->loader->add_filter( 'query_vars', $router, 'register_query_vars' );
				$this->loader->add_action( 'template_redirect', $router, 'maybe_render_kids_route' );
			}
		}

		/**
		 * Register hooks related to REST API endpoints.
		 *
		 * @return void
		 */
		private function define_api_hooks() {
			if ( ! $this->loader ) {
				return;
			}

			if ( class_exists( 'KQAS_REST' ) ) {
				$rest = new KQAS_REST();
				$this->loader->add_action( 'rest_api_init', $rest, 'register_routes' );
			}
		}

		/**
		 * Run the loader to execute all hooks.
		 *
		 * @return void
		 */
		public function run() {
			if ( $this->loader && method_exists( $this->loader, 'run' ) ) {
				$this->loader->run();
			}
		}

		/**
		 * Plugin name getter.
		 *
		 * @return string
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * Version getter.
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}
	}

endif;
