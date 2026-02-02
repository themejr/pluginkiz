<?php
/**
 * Uninstall cleanup for KidQuiz Age-Smart.
 *
 * IMPORTANT (Envato best practice):
 * - Do NOT delete data by default.
 * - Only delete if user explicitly enabled: kqas_delete_data_on_uninstall = 1.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

/**
 * Check if delete-on-uninstall is enabled.
 *
 * @return bool
 */
function kqas_should_delete_data() {
	$flag = get_option( 'kqas_delete_data_on_uninstall', 0 );
	return ( 1 === (int) $flag );
}

/**
 * Delete plugin transients (best effort).
 *
 * @return void
 */
function kqas_delete_transients() {
	global $wpdb;

	// Delete transients created by this plugin (plan/token).
	// Stored as: _transient_{key} and _transient_timeout_{key}
	$like_keys = array(
		'_transient_kqas_plan_%',
		'_transient_timeout_kqas_plan_%',
		'_transient_kqas_token_%',
		'_transient_timeout_kqas_token_%',
	);

	foreach ( $like_keys as $like ) {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

/**
 * Delete plugin options (safe list + prefix cleanup best-effort).
 *
 * @return void
 */
function kqas_delete_options() {
	global $wpdb;

	$opts = array(
		'kqas_version',
		'kqas_allow_nickname',
		'kqas_store_ip',
		'kqas_store_user_agent',
		'kqas_delete_data_on_uninstall',

		// If you added more options later, keep them here too.
		'kqas_default_age_group',
		'kqas_default_reward_mode',
	);

	foreach ( $opts as $opt ) {
		delete_option( $opt );
	}

	// Best-effort: remove any other options with our prefix.
	// (Only executed when delete flag is enabled, so it's acceptable.)
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'kqas_%'
		)
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Drop plugin custom tables.
 *
 * @return void
 */
function kqas_drop_tables() {
	global $wpdb;

	$tables = array(
		$wpdb->prefix . 'kqas_kid_codes',
		$wpdb->prefix . 'kqas_sessions',
		$wpdb->prefix . 'kqas_attempts',
	);

	foreach ( $tables as $t ) {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$t}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

/**
 * Delete CPT content + related meta and term relationships.
 *
 * @return void
 */
function kqas_delete_cpt_content() {
	// We avoid relying on plugin classes here.
	// Keep these slugs consistent with your CPT registration.
	$post_types = apply_filters(
		'kqas_uninstall_post_types',
		array(
			'kqas_question',
			'kqas_quiz',
		)
	);

	foreach ( $post_types as $pt ) {
		$pt = sanitize_key( (string) $pt );
		if ( '' === $pt ) {
			continue;
		}

		$ids = get_posts(
			array(
				'post_type'      => $pt,
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'suppress_filters' => true,
			)
		);

		if ( empty( $ids ) ) {
			continue;
		}

		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true ); // force delete.
		}
	}

	// Optional: cleanup taxonomy terms (if you used one, e.g., kqas_skill).
	$taxes = apply_filters(
		'kqas_uninstall_taxonomies',
		array(
			'kqas_skill',
		)
	);

	foreach ( $taxes as $tax ) {
		$tax = sanitize_key( (string) $tax );
		if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term_id ) {
			wp_delete_term( (int) $term_id, $tax );
		}
	}
}

/**
 * Cleanup for a single site (used by multisite loop).
 *
 * @return void
 */
function kqas_cleanup_single_site() {
	if ( ! kqas_should_delete_data() ) {
		// Always safe to remove our transients even if user doesn't delete data?
		// Envato-wise: better to keep EVERYTHING unless user opts-in.
		return;
	}

	// Remove transient artifacts first.
	kqas_delete_transients();

	// Delete CPT content + terms.
	kqas_delete_cpt_content();

	// Drop tables.
	kqas_drop_tables();

	// Delete options.
	kqas_delete_options();
}

/**
 * Run uninstall.
 */
if ( ! kqas_should_delete_data() ) {
	// Do nothing unless explicitly enabled.
	exit;
}

if ( is_multisite() ) {
	// Detect network-activated.
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_basename = plugin_basename( WP_UNINSTALL_PLUGIN );
	$is_network      = function_exists( 'is_plugin_active_for_network' ) ? is_plugin_active_for_network( $plugin_basename ) : false;

	if ( $is_network ) {
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $blog_ids ) ) {
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( (int) $blog_id );
				kqas_cleanup_single_site();
				restore_current_blog();
			}
		}
	} else {
		kqas_cleanup_single_site();
	}
} else {
	kqas_cleanup_single_site();
}
