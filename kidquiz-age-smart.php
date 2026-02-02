<?php
/**
 * Plugin Name:       KidQuiz Age-Smart
 * Plugin URI:        https://kqas.themejr.net
 * Description:       Age-adaptive micro-quizzes for kids (4–6, 7–9, 10–12) with safe Kid Codes, Kids Mode UI, and parent snapshots.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Themejr
 * Author URI:        https://themeforest.net/user/themejr
 * Text Domain:       kidquiz-age-smart
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KQAS_VERSION' ) ) {
	define( 'KQAS_VERSION', '1.0.0' );
}

if ( ! defined( 'KQAS_MIN_PHP' ) ) {
	define( 'KQAS_MIN_PHP', '7.4' );
}

if ( ! defined( 'KQAS_MIN_WP' ) ) {
	define( 'KQAS_MIN_WP', '6.0' );
}

if ( ! defined( 'KQAS_PLUGIN_FILE' ) ) {
	define( 'KQAS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'KQAS_PLUGIN_BASENAME' ) ) {
	define( 'KQAS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'KQAS_PLUGIN_DIR' ) ) {
	define( 'KQAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'KQAS_PLUGIN_URL' ) ) {
	define( 'KQAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Check requirements (WordPress + PHP) in a safe Envato-friendly way.
 *
 * @return bool
 */
function kqas_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, KQAS_MIN_PHP, '<' ) ) {
		return false;
	}

	if ( isset( $wp_version ) && version_compare( $wp_version, KQAS_MIN_WP, '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Admin notice when requirements are not met.
 *
 * @return void
 */
function kqas_requirements_notice() {
	global $wp_version;

	$php_ok = version_compare( PHP_VERSION, KQAS_MIN_PHP, '>=' );
	$wp_ok  = ( isset( $wp_version ) && version_compare( $wp_version, KQAS_MIN_WP, '>=' ) );

	$message = sprintf(
		/* translators: 1: min PHP, 2: current PHP, 3: min WP, 4: current WP */
		esc_html__( 'KidQuiz Age-Smart requires PHP %1$s+ (you have %2$s) and WordPress %3$s+ (you have %4$s). The plugin has been deactivated.', 'kidquiz-age-smart' ),
		esc_html( KQAS_MIN_PHP ),
		esc_html( PHP_VERSION ),
		esc_html( KQAS_MIN_WP ),
		esc_html( isset( $wp_version ) ? $wp_version : '—' )
	);

	if ( ! $php_ok && $wp_ok ) {
		$message = sprintf(
			/* translators: 1: min PHP, 2: current PHP */
			esc_html__( 'KidQuiz Age-Smart requires PHP %1$s+ (you have %2$s). The plugin has been deactivated.', 'kidquiz-age-smart' ),
			esc_html( KQAS_MIN_PHP ),
			esc_html( PHP_VERSION )
		);
	} elseif ( $php_ok && ! $wp_ok ) {
		$message = sprintf(
			/* translators: 1: min WP, 2: current WP */
			esc_html__( 'KidQuiz Age-Smart requires WordPress %1$s+ (you have %2$s). The plugin has been deactivated.', 'kidquiz-age-smart' ),
			esc_html( KQAS_MIN_WP ),
			esc_html( isset( $wp_version ) ? $wp_version : '—' )
		);
	}

	echo '<div class="notice notice-error"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Deactivate plugin safely if requirements are not met.
 *
 * @return void
 */
function kqas_maybe_deactivate_self() {
	if ( ! kqas_requirements_met() ) {
		if ( is_admin() && function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( KQAS_PLUGIN_BASENAME );
		}
		add_action( 'admin_notices', 'kqas_requirements_notice' );
	}
}
add_action( 'admin_init', 'kqas_maybe_deactivate_self' );

/**
 * Include core files (only if requirements are met).
 *
 * @return void
 */
function kqas_includes() {
	if ( ! kqas_requirements_met() ) {
		return;
	}

	$files = array(
		KQAS_PLUGIN_DIR . 'includes/class-kqas-activator.php',
		KQAS_PLUGIN_DIR . 'includes/class-kqas-deactivator.php',
		KQAS_PLUGIN_DIR . 'includes/class-kqas-loader.php',
		KQAS_PLUGIN_DIR . 'includes/class-kqas-i18n.php',

		KQAS_PLUGIN_DIR . 'includes/core/class-kqas-post-types.php',
		KQAS_PLUGIN_DIR . 'includes/core/class-kqas-taxonomies.php',
		KQAS_PLUGIN_DIR . 'includes/core/class-kqas-metaboxes.php',
		KQAS_PLUGIN_DIR . 'includes/core/class-kqas-settings.php',

		KQAS_PLUGIN_DIR . 'includes/engine/class-kqas-age-rules.php',
		KQAS_PLUGIN_DIR . 'includes/engine/class-kqas-quiz-generator.php',
		KQAS_PLUGIN_DIR . 'includes/engine/class-kqas-rewards.php',

		KQAS_PLUGIN_DIR . 'includes/kids/class-kqas-kid-codes.php',
		KQAS_PLUGIN_DIR . 'includes/kids/class-kqas-session.php',
		KQAS_PLUGIN_DIR . 'includes/kids/class-kqas-parent-snapshot.php',

		KQAS_PLUGIN_DIR . 'includes/api/class-kqas-rest.php',

		KQAS_PLUGIN_DIR . 'admin/class-kqas-admin.php',
		KQAS_PLUGIN_DIR . 'public/class-kqas-public.php',

		KQAS_PLUGIN_DIR . 'includes/public/class-kqas-shortcodes.php',
		KQAS_PLUGIN_DIR . 'includes/public/class-kqas-router.php',

		KQAS_PLUGIN_DIR . 'includes/utils/class-kqas-sanitizer.php',
		KQAS_PLUGIN_DIR . 'includes/utils/class-kqas-time.php',
		KQAS_PLUGIN_DIR . 'includes/utils/class-kqas-helpers.php',

		KQAS_PLUGIN_DIR . 'includes/class-kqas.php',
	);

	foreach ( $files as $file ) {
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
kqas_includes();

/**
 * Set a multisite-safe flag to flush rewrite rules on next init.
 *
 * @param int $value 0/1
 * @return void
 */
function kqas_set_rewrite_flush_flag( $value = 1 ) {
	$value = (int) $value;

	if ( is_multisite() ) {
		if ( 1 === $value ) {
			add_site_option( 'kqas_needs_rewrite_flush', 1 );
		} else {
			delete_site_option( 'kqas_needs_rewrite_flush' );
		}
	} else {
		if ( 1 === $value ) {
			add_option( 'kqas_needs_rewrite_flush', 1, '', false );
		} else {
			delete_option( 'kqas_needs_rewrite_flush' );
		}
	}
}

/**
 * Flush rewrite rules once (deferred), after init has registered CPT + router rules.
 *
 * @return void
 */
function kqas_maybe_flush_rewrite_rules() {
	if ( ! kqas_requirements_met() ) {
		return;
	}

	$needs = is_multisite()
		? (int) get_site_option( 'kqas_needs_rewrite_flush', 0 )
		: (int) get_option( 'kqas_needs_rewrite_flush', 0 );

	if ( 1 !== $needs ) {
		return;
	}

	// At init, WP rewrite is ready, and our Router/CPT hooks should have run.
	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules();
	}

	kqas_set_rewrite_flush_flag( 0 );
}
add_action( 'init', 'kqas_maybe_flush_rewrite_rules', 99 );

/**
 * Plugin activation (multisite-safe).
 *
 * @return void
 */
function kqas_activate() {
	if ( ! kqas_requirements_met() ) {
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( KQAS_PLUGIN_BASENAME );
		}
		wp_die(
			esc_html__( 'KidQuiz Age-Smart cannot be activated because your environment does not meet the minimum requirements.', 'kidquiz-age-smart' ),
			esc_html__( 'Activation failed', 'kidquiz-age-smart' ),
			array( 'back_link' => true )
		);
	}

	if ( class_exists( 'KQAS_Activator' ) ) {
		KQAS_Activator::activate();
	}

	// Defer rewrite flush to init (multisite/network-safe).
	kqas_set_rewrite_flush_flag( 1 );
}
register_activation_hook( __FILE__, 'kqas_activate' );

/**
 * Plugin deactivation.
 *
 * NOTE: we do not delete data here. (uninstall.php handles deletion if enabled)
 *
 * @return void
 */
function kqas_deactivate() {
	if ( class_exists( 'KQAS_Deactivator' ) ) {
		KQAS_Deactivator::deactivate();
	}

	// Defer flushing rewrite rules after deactivation to avoid multisite timing issues.
	// (Optional but safe to clear routes.)
	kqas_set_rewrite_flush_flag( 1 );
}
register_deactivation_hook( __FILE__, 'kqas_deactivate' );

/**
 * Begin execution of the plugin.
 *
 * @return void
 */
function kqas_run() {
	if ( ! kqas_requirements_met() ) {
		return;
	}

	if ( class_exists( 'KQAS' ) ) {
		$plugin = new KQAS();
		if ( method_exists( $plugin, 'run' ) ) {
			$plugin->run();
		}
	}
}
add_action( 'plugins_loaded', 'kqas_run', 20 );
