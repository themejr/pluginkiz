<?php
/**
 * Kid Codes manager.
 *
 * - Generate safe access codes (e.g., KID-4832)
 * - Store/activate/deactivate codes in custom table: {$wpdb->prefix}kqas_kid_codes
 * - Validate codes for Kids Mode login
 *
 * Table (created by KQAS_Activator):
 * - id, kid_code, label, status, created_at, updated_at
 *
 * Depends on settings/options:
 * - kqas_kid_code_prefix
 * - kqas_kid_code_length
 * - kqas_kid_code_max_active
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Kid_Codes', false ) ) :

	final class KQAS_Kid_Codes {

		/**
		 * Table name (without prefix).
		 */
		const TABLE = 'kqas_kid_codes';

		/**
		 * Status values.
		 */
		const STATUS_ACTIVE   = 'active';
		const STATUS_INACTIVE = 'inactive';

		/**
		 * Get full table name with wp prefix.
		 *
		 * @return string
		 */
		private static function table_name() {
			global $wpdb;
			return $wpdb->prefix . self::TABLE;
		}

		/**
		 * Get option with fallback (uses KQAS_Settings if available).
		 *
		 * @param string $option_name Option name.
		 * @param mixed  $default     Default value.
		 * @return mixed
		 */
		private static function opt( $option_name, $default ) {
			if ( class_exists( 'KQAS_Settings' ) && is_callable( array( 'KQAS_Settings', 'get' ) ) ) {
				return KQAS_Settings::get( $option_name, $default );
			}
			return get_option( $option_name, $default );
		}

		/**
		 * Normalize a code prefix.
		 *
		 * @param string $prefix Raw prefix.
		 * @return string
		 */
		public static function normalize_prefix( $prefix ) {
			$prefix = sanitize_text_field( (string) $prefix );
			$prefix = strtoupper( $prefix );
			$prefix = preg_replace( '/[^A-Z]/', '', $prefix );

			if ( strlen( $prefix ) < 2 ) {
				return 'KID';
			}
			if ( strlen( $prefix ) > 8 ) {
				return substr( $prefix, 0, 8 );
			}
			return $prefix;
		}

		/**
		 * Normalize/format a kid code as PREFIX-#### (digits length from settings).
		 *
		 * @param string $code Raw code.
		 * @return string
		 */
		public static function normalize_code( $code ) {
			$code = strtoupper( sanitize_text_field( (string) $code ) );
			$code = preg_replace( '/[^A-Z0-9\-]/', '', $code );

			// Accept formats like "KID4832" or "KID-4832" -> normalize to "KID-4832".
			$code = str_replace( '--', '-', $code );

			// If no hyphen, attempt to insert after prefix letters.
			if ( false === strpos( $code, '-' ) ) {
				$code = preg_replace( '/^([A-Z]+)([0-9]+)$/', '$1-$2', $code );
			}

			return $code;
		}

		/**
		 * Generate one unique kid code and store it as active.
		 *
		 * @param string $label Optional admin label (class/child group etc).
		 * @return string|WP_Error Generated code or WP_Error.
		 */
		public static function generate_one( $label = '' ) {
			global $wpdb;

			$prefix     = self::normalize_prefix( (string) self::opt( 'kqas_kid_code_prefix', 'KID' ) );
			$digits_len = (int) self::opt( 'kqas_kid_code_length', 4 );
			$digits_len = max( 3, min( 8, $digits_len ) );

			$max_active = (int) self::opt( 'kqas_kid_code_max_active', 5000 );
			$max_active = max( 1, min( 1000000, $max_active ) );

			// Enforce max active codes.
			$active_count = self::count_by_status( self::STATUS_ACTIVE );
			if ( $active_count >= $max_active ) {
				return new WP_Error(
					'kqas_max_active_reached',
					esc_html__( 'Maximum number of active Kid Codes reached. Please deactivate some codes or increase the limit in settings.', 'kidquiz-age-smart' )
				);
			}

			$label = sanitize_text_field( (string) $label );
			// Avoid passing NULL to wpdb->prepare() (PHP 8.1+ deprecated warnings).
			if ( '' === $label ) {
				$label = '';
			}

			$table = self::table_name();

			// Try several attempts to avoid collisions.
			$attempts = 0;
			$max_try  = 25;

			while ( $attempts < $max_try ) {
				$attempts++;

				$digits = self::random_digits( $digits_len );
				$code   = $prefix . '-' . $digits;

				$now = current_time( 'mysql' );

				$inserted = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$table} (kid_code, label, status, created_at, updated_at)
						 VALUES (%s, %s, %s, %s, %s)",
						$code,
						$label,
						self::STATUS_ACTIVE,
						$now,
						$now
					)
				);

				if ( false !== $inserted ) {
					return $code;
				}

				// If duplicate code, retry; otherwise surface DB error.
				// MySQL duplicate key error code is typically 1062.
				if ( isset( $wpdb->last_error ) && '' !== $wpdb->last_error ) {
					if ( false !== strpos( $wpdb->last_error, 'Duplicate' ) || false !== strpos( $wpdb->last_error, '1062' ) ) {
						continue;
					}
					return new WP_Error( 'kqas_db_insert_failed', $wpdb->last_error );
				}
			}

			return new WP_Error(
				'kqas_code_generation_failed',
				esc_html__( 'Could not generate a unique Kid Code. Please try again.', 'kidquiz-age-smart' )
			);
		}

		/**
		 * Generate many codes.
		 *
		 * @param int    $count How many to generate.
		 * @param string $label Optional label.
		 * @return array{codes:string[],errors:array<int,string>}
		 */
		public static function generate_many( $count, $label = '' ) {
			$count = max( 1, min( 500, (int) $count ) ); // safety.
			$codes = array();
			$errs  = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$res = self::generate_one( $label );
				if ( is_wp_error( $res ) ) {
					$errs[] = $res->get_error_message();
					break;
				}
				$codes[] = $res;
			}

			return array(
				'codes'  => $codes,
				'errors' => $errs,
			);
		}

		/**
		 * Check whether a code exists and is active.
		 *
		 * @param string $code Kid code.
		 * @return bool
		 */
		public static function is_valid_active_code( $code ) {
			$row = self::get_code_row( $code );
			return ( ! empty( $row ) && isset( $row['status'] ) && self::STATUS_ACTIVE === $row['status'] );
		}

		/**
		 * Get code row.
		 *
		 * @param string $code Kid code.
		 * @return array<string,mixed>|null
		 */
		public static function get_code_row( $code ) {
			global $wpdb;

			$code  = self::normalize_code( $code );
			$table = self::table_name();

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, kid_code, label, status, created_at, updated_at
					 FROM {$table}
					 WHERE kid_code = %s
					 LIMIT 1",
					$code
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return is_array( $row ) ? $row : null;
		}

		/**
		 * Count codes by status.
		 *
		 * @param string $status active|inactive
		 * @return int
		 */
		public static function count_by_status( $status ) {
			global $wpdb;

			$status = sanitize_text_field( (string) $status );
			if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_INACTIVE ), true ) ) {
				$status = self::STATUS_ACTIVE;
			}

			$table = self::table_name();

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE status = %s",
					$status
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return max( 0, $count );
		}

		/**
		 * Deactivate a code (soft disable).
		 *
		 * @param string $code Kid code.
		 * @return bool|WP_Error
		 */
		public static function deactivate_code( $code ) {
			return self::set_status( $code, self::STATUS_INACTIVE );
		}

		/**
		 * Activate a code.
		 *
		 * @param string $code Kid code.
		 * @return bool|WP_Error
		 */
		public static function activate_code( $code ) {
			return self::set_status( $code, self::STATUS_ACTIVE );
		}

		/**
		 * Set status for a code.
		 *
		 * @param string $code   Kid code.
		 * @param string $status New status.
		 * @return bool|WP_Error
		 */
		private static function set_status( $code, $status ) {
			global $wpdb;

			$code   = self::normalize_code( $code );
			$status = sanitize_text_field( (string) $status );

			if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_INACTIVE ), true ) ) {
				return new WP_Error( 'kqas_invalid_status', esc_html__( 'Invalid status.', 'kidquiz-age-smart' ) );
			}

			$row = self::get_code_row( $code );
			if ( empty( $row ) || empty( $row['id'] ) ) {
				return new WP_Error( 'kqas_code_not_found', esc_html__( 'Kid Code not found.', 'kidquiz-age-smart' ) );
			}

			$table = self::table_name();
			$now   = current_time( 'mysql' );

			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table}
					 SET status = %s, updated_at = %s
					 WHERE id = %d",
					$status,
					$now,
					(int) $row['id']
				)
			);

			if ( false === $updated ) {
				return new WP_Error( 'kqas_db_update_failed', (string) $wpdb->last_error );
			}

			return true;
		}

		/**
		 * Delete a code row (hard delete).
		 * Note: normally not needed; keep for admin tools.
		 *
		 * @param string $code Kid code.
		 * @return bool|WP_Error
		 */
		public static function delete_code( $code ) {
			global $wpdb;

			$code = self::normalize_code( $code );
			$row  = self::get_code_row( $code );
			if ( empty( $row ) || empty( $row['id'] ) ) {
				return new WP_Error( 'kqas_code_not_found', esc_html__( 'Kid Code not found.', 'kidquiz-age-smart' ) );
			}

			$table = self::table_name();

			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE id = %d",
					(int) $row['id']
				)
			);

			if ( false === $deleted ) {
				return new WP_Error( 'kqas_db_delete_failed', (string) $wpdb->last_error );
			}

			return true;
		}

		/**
		 * List codes (admin use).
		 *
		 * @param array $args {
		 *   @type string $status 'active'|'inactive'|''.
		 *   @type int    $limit
		 *   @type int    $offset
		 *   @type string $search Search by code/label.
		 * }
		 * @return array<int,array<string,mixed>>
		 */
		public static function list_codes( $args = array() ) {
			global $wpdb;

			$status = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
			$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 50;
			$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
			$search = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

			$limit  = max( 1, min( 200, $limit ) );
			$offset = max( 0, $offset );

			$table  = self::table_name();
			$where  = array();
			$params = array();

			if ( in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_INACTIVE ), true ) ) {
				$where[]  = 'status = %s';
				$params[] = $status;
			}

			if ( '' !== $search ) {
				$where[]  = '(kid_code LIKE %s OR label LIKE %s)';
				$like     = '%' . $wpdb->esc_like( $search ) . '%';
				$params[] = $like;
				$params[] = $like;
			}

			$where_sql = '';
			if ( ! empty( $where ) ) {
				$where_sql = 'WHERE ' . implode( ' AND ', $where );
			}

			$sql = "SELECT id, kid_code, label, status, created_at, updated_at
					FROM {$table}
					{$where_sql}
					ORDER BY created_at DESC
					LIMIT %d OFFSET %d";

			$params[] = $limit;
			$params[] = $offset;

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$prepared = $wpdb->prepare( $sql, $params );
			$rows     = $wpdb->get_results( $prepared, ARRAY_A );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return is_array( $rows ) ? $rows : array();
		}

		/**
		 * Create random digits string with fixed length (leading zeros allowed).
		 *
		 * @param int $len Length.
		 * @return string
		 */
		private static function random_digits( $len ) {
			$len = max( 1, (int) $len );

			$min = (int) pow( 10, $len - 1 );
			$max = (int) pow( 10, $len ) - 1;

			// For len=1, allow 0-9.
			if ( 1 === $len ) {
				$min = 0;
				$max = 9;
			}

			$n = wp_rand( $min, $max );

			// Pad with leading zeros (if any).
			return str_pad( (string) $n, $len, '0', STR_PAD_LEFT );
		}
	}

endif;
