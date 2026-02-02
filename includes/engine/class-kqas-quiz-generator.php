<?php
/**
 * Quiz Generator (Micro-Quiz builder).
 *
 * مسؤول عن:
 * - قراءة إعدادات Quiz (Target Age Group / Session settings / Reward mode)
 * - تطبيق defaults حسب العمر إذا لم يتم تحديد session length داخل الـQuiz
 * - اختيار أسئلة مناسبة عبر Age Rules Engine
 * - إخراج "خطة جلسة" session plan تستخدمها Kids Mode / REST
 *
 * يعتمد على:
 * - CPT Quiz: KQAS_Post_Types::CPT_QUIZ
 * - Meta keys للـQuiz من Metaboxes:
 *   _kqas_target_age_group, _kqas_session_questions, _kqas_session_seconds, _kqas_reward_mode
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Quiz_Generator', false ) ) :

	final class KQAS_Quiz_Generator {

		/**
		 * Get quiz config from post meta, normalized.
		 *
		 * @param int $quiz_id Quiz post ID.
		 * @return array{
		 *   quiz_id:int,
		 *   age_group:string,
		 *   questions_count:int,
		 *   time_limit_seconds:int,
		 *   reward_mode:string
		 * }
		 */
		public static function get_quiz_config( $quiz_id ) {
			$quiz_id = (int) $quiz_id;

			$age_group = (string) get_post_meta( $quiz_id, '_kqas_target_age_group', true );
			$age_group = class_exists( 'KQAS_Age_Rules' ) ? KQAS_Age_Rules::normalize_age_group( $age_group ) : '4-6';

			$reward_mode = (string) get_post_meta( $quiz_id, '_kqas_reward_mode', true );
			$reward_mode = sanitize_text_field( $reward_mode );
			if ( ! in_array( $reward_mode, array( 'stickers', 'badges' ), true ) ) {
				$reward_mode = ( '4-6' === $age_group ) ? 'stickers' : 'badges';
			}

			$questions_count   = (int) get_post_meta( $quiz_id, '_kqas_session_questions', true );
			$time_limit_second = (int) get_post_meta( $quiz_id, '_kqas_session_seconds', true );

			// Apply defaults if empty/invalid.
			if ( $questions_count < 1 || $time_limit_second < 1 ) {
				if ( class_exists( 'KQAS_Age_Rules' ) ) {
					$defaults = KQAS_Age_Rules::get_session_defaults( $age_group );
				} else {
					$defaults = array( 'questions' => 5, 'seconds' => 180 );
				}

				if ( $questions_count < 1 ) {
					$questions_count = (int) $defaults['questions'];
				}
				if ( $time_limit_second < 1 ) {
					$time_limit_second = (int) $defaults['seconds'];
				}
			}

			// Safe bounds.
			$questions_count   = max( 1, min( 50, $questions_count ) );
			$time_limit_second = max( 30, min( 3600, $time_limit_second ) );

			return array(
				'quiz_id'           => $quiz_id,
				'age_group'         => $age_group,
				'questions_count'   => $questions_count,
				'time_limit_seconds'=> $time_limit_second,
				'reward_mode'       => $reward_mode,
			);
		}

		/**
		 * Build a session plan for a quiz.
		 *
		 * @param int   $quiz_id Quiz ID.
		 * @param array $args    Optional:
		 *                       - skill_ids (int[])
		 *                       - difficulty (string)
		 *                       - exclude_question_ids (int[])
		 *
		 * @return array{
		 *   quiz_id:int,
		 *   age_group:string,
		 *   questions_count:int,
		 *   time_limit_seconds:int,
		 *   reward_mode:string,
		 *   question_ids:int[]
		 * }
		 */
		public static function build_session_plan( $quiz_id, $args = array() ) {
			$config = self::get_quiz_config( $quiz_id );

			$exclude_ids = array();
			if ( ! empty( $args['exclude_question_ids'] ) && is_array( $args['exclude_question_ids'] ) ) {
				$exclude_ids = array_map( 'intval', $args['exclude_question_ids'] );
				$exclude_ids = array_filter( $exclude_ids );
			}

			$filters = array(
				'exclude_ids' => $exclude_ids,
			);

			if ( isset( $args['difficulty'] ) ) {
				$filters['difficulty'] = $args['difficulty'];
			}
			if ( isset( $args['skill_ids'] ) ) {
				$filters['skill_ids'] = $args['skill_ids'];
			}

			$question_ids = array();
			if ( class_exists( 'KQAS_Age_Rules' ) ) {
				$question_ids = KQAS_Age_Rules::select_question_ids(
					$config['age_group'],
					$config['questions_count'],
					$filters
				);
			}

			// If not enough questions found, gracefully try again with fewer restrictions.
			if ( count( $question_ids ) < $config['questions_count'] && class_exists( 'KQAS_Age_Rules' ) ) {
				$remaining = $config['questions_count'] - count( $question_ids );

				$more = KQAS_Age_Rules::select_question_ids(
					$config['age_group'],
					$remaining,
					array(
						'exclude_ids' => array_merge( $exclude_ids, $question_ids ),
					)
				);

				if ( ! empty( $more ) ) {
					$question_ids = array_merge( $question_ids, $more );
					$question_ids = array_values( array_unique( array_map( 'intval', $question_ids ) ) );
				}
			}

			$plan = array(
				'quiz_id'            => (int) $config['quiz_id'],
				'age_group'          => (string) $config['age_group'],
				'questions_count'    => (int) $config['questions_count'],
				'time_limit_seconds' => (int) $config['time_limit_seconds'],
				'reward_mode'        => (string) $config['reward_mode'],
				'question_ids'       => array_values( array_map( 'intval', $question_ids ) ),
			);

			/**
			 * Filter session plan.
			 *
			 * @param array $plan
			 * @param array $args
			 */
			return apply_filters( 'kqas_quiz_generator_session_plan', $plan, $args );
		}

		/**
		 * Helper: pick a default quiz ID if none is provided.
		 *
		 * MVP assumption:
		 * - Choose latest published quiz.
		 *
		 * @return int|null
		 */
		public static function get_default_quiz_id() {
			if ( ! class_exists( 'KQAS_Post_Types' ) ) {
				return null;
			}

			$q = new WP_Query(
				array(
					'post_type'           => KQAS_Post_Types::CPT_QUIZ,
					'post_status'         => 'publish',
					'fields'              => 'ids',
					'posts_per_page'      => 1,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
				)
			);

			if ( ! empty( $q->posts ) && is_array( $q->posts ) ) {
				return (int) $q->posts[0];
			}

			return null;
		}
	}

endif;
