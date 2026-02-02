<?php
/**
 * Sanitizer helpers for KidQuiz Age-Smart.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Sanitizer', false ) ) :

	final class KQAS_Sanitizer {

		/**
		 * Sanitize boolean (0/1).
		 *
		 * @param mixed $value Value.
		 * @return int 0|1
		 */
		public static function bool01( $value ) {
			return ( (int) (bool) $value ) === 1 ? 1 : 0;
		}

		/**
		 * Sanitize int within range.
		 *
		 * @param mixed $value Value.
		 * @param int   $min Min.
		 * @param int   $max Max.
		 * @param int   $default Default.
		 * @return int
		 */
		public static function int_range( $value, $min, $max, $default = 0 ) {
			$val = (int) $value;
			if ( $val < (int) $min || $val > (int) $max ) {
				return (int) $default;
			}
			return $val;
		}

		/**
		 * Normalize prefix for Kid Codes.
		 *
		 * @param string $prefix Prefix.
		 * @return string
		 */
		public static function kid_code_prefix( $prefix ) {
			$prefix = strtoupper( sanitize_text_field( (string) $prefix ) );
			$prefix = preg_replace( '/[^A-Z]/', '', $prefix );
			$len    = strlen( $prefix );

			if ( $len < 2 ) {
				return 'KID';
			}

			if ( $len > 8 ) {
				$prefix = substr( $prefix, 0, 8 );
			}

			return $prefix;
		}

		/**
		 * Normalize a Kid Code (best effort) to PREFIX-1234.
		 *
		 * @param string $code Code.
		 * @return string
		 */
		public static function kid_code( $code ) {
			$code = strtoupper( sanitize_text_field( (string) $code ) );
			$code = preg_replace( '/\s+/', '', $code );

			// Accept: KID-1234 or KID1234.
			if ( preg_match( '/^([A-Z]{2,8})-?([0-9]{3,8})$/', $code, $m ) ) {
				$prefix = self::kid_code_prefix( $m[1] );
				$digits = $m[2];
				return $prefix . '-' . $digits;
			}

			return $code;
		}

		/**
		 * Sanitize age group.
		 *
		 * @param string $age_group Age group.
		 * @return string
		 */
		public static function age_group( $age_group ) {
			$age_group = sanitize_text_field( (string) $age_group );
			$allowed   = array( '4-6', '7-9', '10-12' );

			return in_array( $age_group, $allowed, true ) ? $age_group : '4-6';
		}

		/**
		 * Sanitize difficulty.
		 *
		 * @param string $difficulty Difficulty.
		 * @return string
		 */
		public static function difficulty( $difficulty ) {
			$difficulty = sanitize_text_field( (string) $difficulty );
			$allowed    = array( 'easy', 'medium', 'hard' );

			return in_array( $difficulty, $allowed, true ) ? $difficulty : 'easy';
		}

		/**
		 * Sanitize reading level (1..3).
		 *
		 * @param mixed $level Level.
		 * @return int
		 */
		public static function reading_level( $level ) {
			return self::int_range( $level, 1, 3, 1 );
		}

		/**
		 * Sanitize reward mode.
		 *
		 * @param string $mode Mode.
		 * @param string $age_group Age group (optional for default).
		 * @return string
		 */
		public static function reward_mode( $mode, $age_group = '4-6' ) {
			$mode    = sanitize_text_field( (string) $mode );
			$allowed = array( 'stickers', 'badges' );

			if ( in_array( $mode, $allowed, true ) ) {
				return $mode;
			}

			$age_group = self::age_group( $age_group );
			return ( '4-6' === $age_group ) ? 'stickers' : 'badges';
		}

		/**
		 * Sanitize attachment ID.
		 *
		 * @param mixed $id Attachment ID.
		 * @return int
		 */
		public static function attachment_id( $id ) {
			return max( 0, (int) $id );
		}
	}

endif;
