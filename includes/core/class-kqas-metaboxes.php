<?php
/**
 * Metaboxes for KidQuiz Age-Smart.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Metaboxes', false ) ) :

	final class KQAS_Metaboxes {

		const NONCE_ACTION = 'kqas_metaboxes_save';
		const NONCE_NAME   = '_kqas_metabox_nonce';

		public function register_metaboxes() {
			$question_cpt = class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUESTION : 'kqas_question';
			$quiz_cpt     = class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUIZ : 'kqas_quiz';

			add_meta_box(
				'kqas_question_meta',
				__( 'KidQuiz Question Settings', 'kidquiz-age-smart' ),
				array( $this, 'render_question_metabox' ),
				$question_cpt,
				'normal',
				'high'
			);

			add_meta_box(
				'kqas_quiz_meta',
				__( 'KidQuiz Quiz Settings', 'kidquiz-age-smart' ),
				array( $this, 'render_quiz_metabox' ),
				$quiz_cpt,
				'normal',
				'high'
			);
		}

		public function render_question_metabox( $post ) {
			wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

			$age_min       = (int) get_post_meta( $post->ID, '_kqas_age_min', true );
			$age_max       = (int) get_post_meta( $post->ID, '_kqas_age_max', true );
			$difficulty    = (string) get_post_meta( $post->ID, '_kqas_difficulty', true );
			$reading_level = (int) get_post_meta( $post->ID, '_kqas_reading_level', true );

			$image_id = (int) get_post_meta( $post->ID, '_kqas_attachment_image_id', true );
			$audio_id = (int) get_post_meta( $post->ID, '_kqas_attachment_audio_id', true );

			if ( '' === $difficulty ) {
				$difficulty = 'easy';
			}
			if ( $reading_level < 1 || $reading_level > 3 ) {
				$reading_level = 1;
			}

			if ( class_exists( 'KQAS_Helpers' ) ) {
				echo KQAS_Helpers::render_admin_view(
					'question-edit-metabox.php',
					array(
						'age_min'       => $age_min,
						'age_max'       => $age_max,
						'difficulty'    => $difficulty,
						'reading_level' => $reading_level,
						'image_id'      => $image_id,
						'audio_id'      => $audio_id,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}

			echo '<p>' . esc_html__( 'Metabox view is missing.', 'kidquiz-age-smart' ) . '</p>';
		}

		public function render_quiz_metabox( $post ) {
			wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

			$target_age_group = (string) get_post_meta( $post->ID, '_kqas_target_age_group', true );
			$session_q        = (int) get_post_meta( $post->ID, '_kqas_session_questions', true );
			$session_sec      = (int) get_post_meta( $post->ID, '_kqas_session_seconds', true );
			$reward_mode      = (string) get_post_meta( $post->ID, '_kqas_reward_mode', true );

			if ( '' === $target_age_group ) {
				$target_age_group = '4-6';
			}
			if ( '' === $reward_mode ) {
				$reward_mode = ( '4-6' === $target_age_group ) ? 'stickers' : 'badges';
			}

			if ( class_exists( 'KQAS_Helpers' ) ) {
				echo KQAS_Helpers::render_admin_view(
					'quiz-settings-metabox.php',
					array(
						'target_age_group' => $target_age_group,
						'session_q'        => $session_q,
						'session_sec'      => $session_sec,
						'reward_mode'      => $reward_mode,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}

			echo '<p>' . esc_html__( 'Metabox view is missing.', 'kidquiz-age-smart' ) . '</p>';
		}

		public function save_metaboxes( $post_id ) {
			if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}
			$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$post_type = get_post_type( $post_id );
			$question_cpt = class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUESTION : 'kqas_question';
			$quiz_cpt     = class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUIZ : 'kqas_quiz';

			if ( $question_cpt === $post_type ) {
				$this->save_question_meta( $post_id );
			} elseif ( $quiz_cpt === $post_type ) {
				$this->save_quiz_meta( $post_id );
			}
		}

		private function save_question_meta( $post_id ) {
			$age_min = isset( $_POST['kqas_age_min'] ) ? (int) $_POST['kqas_age_min'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$age_max = isset( $_POST['kqas_age_max'] ) ? (int) $_POST['kqas_age_max'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$difficulty = isset( $_POST['kqas_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_difficulty'] ) ) : 'easy'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$level      = isset( $_POST['kqas_reading_level'] ) ? (int) $_POST['kqas_reading_level'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$image_id = isset( $_POST['kqas_attachment_image_id'] ) ? (int) $_POST['kqas_attachment_image_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$audio_id = isset( $_POST['kqas_attachment_audio_id'] ) ? (int) $_POST['kqas_attachment_audio_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$age_min = max( 0, min( 18, $age_min ) );
			$age_max = max( 0, min( 18, $age_max ) );

			if ( $age_min > 0 && $age_max > 0 && $age_min > $age_max ) {
				$tmp     = $age_min;
				$age_min = $age_max;
				$age_max = $tmp;
			}

			$allowed_difficulty = array( 'easy', 'medium', 'hard' );
			if ( ! in_array( $difficulty, $allowed_difficulty, true ) ) {
				$difficulty = 'easy';
			}

			if ( $level < 1 || $level > 3 ) {
				$level = 1;
			}

			update_post_meta( $post_id, '_kqas_age_min', $age_min );
			update_post_meta( $post_id, '_kqas_age_max', $age_max );
			update_post_meta( $post_id, '_kqas_difficulty', $difficulty );
			update_post_meta( $post_id, '_kqas_reading_level', $level );

			update_post_meta( $post_id, '_kqas_attachment_image_id', max( 0, $image_id ) );
			update_post_meta( $post_id, '_kqas_attachment_audio_id', max( 0, $audio_id ) );
		}

		private function save_quiz_meta( $post_id ) {
			$age_group = isset( $_POST['kqas_target_age_group'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_target_age_group'] ) ) : '4-6'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$reward    = isset( $_POST['kqas_reward_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_reward_mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$session_q   = isset( $_POST['kqas_session_questions'] ) ? (int) $_POST['kqas_session_questions'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$session_sec = isset( $_POST['kqas_session_seconds'] ) ? (int) $_POST['kqas_session_seconds'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$allowed_age_groups = array( '4-6', '7-9', '10-12' );
			if ( ! in_array( $age_group, $allowed_age_groups, true ) ) {
				$age_group = '4-6';
			}

			$allowed_rewards = array( 'stickers', 'badges' );
			if ( ! in_array( $reward, $allowed_rewards, true ) ) {
				$reward = ( '4-6' === $age_group ) ? 'stickers' : 'badges';
			}

			if ( $session_q < 0 ) {
				$session_q = 0;
			}
			if ( $session_sec < 0 ) {
				$session_sec = 0;
			}

			update_post_meta( $post_id, '_kqas_target_age_group', $age_group );
			update_post_meta( $post_id, '_kqas_reward_mode', $reward );
			update_post_meta( $post_id, '_kqas_session_questions', $session_q );
			update_post_meta( $post_id, '_kqas_session_seconds', $session_sec );
		}
	}

endif;
