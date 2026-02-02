<?php
/**
 * Time/session helpers for KidQuiz Age-Smart.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Time', false ) ) :

	final class KQAS_Time {

		/**
		 * Convert minutes to seconds.
		 *
		 * @param int $minutes Minutes.
		 * @return int
		 */
		public static function minutes_to_seconds( $minutes ) {
			$minutes = max( 0, (int) $minutes );
			return $minutes * MINUTE_IN_SECONDS;
		}

		/**
		 * Get session defaults for an age group (pull from options if present).
		 *
		 * Options used (already registered by KQAS_Settings):
		 * - kqas_session_4_6_questions / kqas_session_4_6_seconds
		 * - kqas_session_7_9_questions / kqas_session_7_9_seconds
		 * - kqas_session_10_12_questions / kqas_session_10_12_seconds
		 *
		 * @param string $age_group Age group (4-6 / 7-9 / 10-12).
		 * @return array{questions:int,seconds:int}
		 */
		public static function session_profile( $age_group ) {
			$age_group = class_exists( 'KQAS_Sanitizer' ) ? KQAS_Sanitizer::age_group( $age_group ) : (string) $age_group;

			$defaults = array(
				'4-6'   => array( 'questions' => 5,  'seconds' => 3 * MINUTE_IN_SECONDS ),
				'7-9'   => array( 'questions' => 8,  'seconds' => 6 * MINUTE_IN_SECONDS ),
				'10-12' => array( 'questions' => 12, 'seconds' => 10 * MINUTE_IN_SECONDS ),
			);

			$opt_map = array(
				'4-6'   => array( 'q' => 'kqas_session_4_6_questions',   's' => 'kqas_session_4_6_seconds' ),
				'7-9'   => array( 'q' => 'kqas_session_7_9_questions',   's' => 'kqas_session_7_9_seconds' ),
				'10-12' => array( 'q' => 'kqas_session_10_12_questions', 's' => 'kqas_session_10_12_seconds' ),
			);

			$q = (int) get_option( $opt_map[ $age_group ]['q'], 0 );
			$s = (int) get_option( $opt_map[ $age_group ]['s'], 0 );

			if ( $q <= 0 ) {
				$q = (int) $defaults[ $age_group ]['questions'];
			}
			if ( $s <= 0 ) {
				$s = (int) $defaults[ $age_group ]['seconds'];
			}

			// Safety caps.
			$q = max( 1, min( 30, $q ) );
			$s = max( 30, min( 60 * 60, $s ) );

			return array(
				'questions' => $q,
				'seconds'   => $s,
			);
		}
	}

endif;
