<?php
/**
 * Register custom post types for KidQuiz Age-Smart.
 *
 * CPTs:
 * - kqas_question  (Question Bank)
 * - kqas_quiz      (Quiz Definitions)
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Post_Types', false ) ) :

	final class KQAS_Post_Types {

		/**
		 * Question CPT slug.
		 */
		const CPT_QUESTION = 'kqas_question';

		/**
		 * Quiz CPT slug.
		 */
		const CPT_QUIZ = 'kqas_quiz';

		/**
		 * Register all plugin post types.
		 *
		 * @return void
		 */
		public function register_post_types() {
			$this->register_question_cpt();
			$this->register_quiz_cpt();
		}

		/**
		 * Register the Question post type (Question Bank).
		 *
		 * @return void
		 */
		private function register_question_cpt() {

			$labels = array(
				'name'                  => esc_html__( 'KidQuiz Questions', 'kidquiz-age-smart' ),
				'singular_name'         => esc_html__( 'Question', 'kidquiz-age-smart' ),
				'menu_name'             => esc_html__( 'KidQuiz', 'kidquiz-age-smart' ),
				'name_admin_bar'        => esc_html__( 'Question', 'kidquiz-age-smart' ),
				'add_new'               => esc_html__( 'Add New', 'kidquiz-age-smart' ),
				'add_new_item'          => esc_html__( 'Add New Question', 'kidquiz-age-smart' ),
				'new_item'              => esc_html__( 'New Question', 'kidquiz-age-smart' ),
				'edit_item'             => esc_html__( 'Edit Question', 'kidquiz-age-smart' ),
				'view_item'             => esc_html__( 'View Question', 'kidquiz-age-smart' ),
				'all_items'             => esc_html__( 'Questions', 'kidquiz-age-smart' ),
				'search_items'          => esc_html__( 'Search Questions', 'kidquiz-age-smart' ),
				'parent_item_colon'     => esc_html__( 'Parent Question:', 'kidquiz-age-smart' ),
				'not_found'             => esc_html__( 'No questions found.', 'kidquiz-age-smart' ),
				'not_found_in_trash'    => esc_html__( 'No questions found in Trash.', 'kidquiz-age-smart' ),
				'featured_image'        => esc_html__( 'Question Image', 'kidquiz-age-smart' ),
				'set_featured_image'    => esc_html__( 'Set question image', 'kidquiz-age-smart' ),
				'remove_featured_image' => esc_html__( 'Remove question image', 'kidquiz-age-smart' ),
				'use_featured_image'    => esc_html__( 'Use as question image', 'kidquiz-age-smart' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => false, // We’ll attach under our own menu via admin class.
				'show_in_admin_bar'  => true,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'exclude_from_search'=> true,
				'publicly_queryable' => false,
				'query_var'          => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'menu_icon'          => 'dashicons-welcome-learn-more',
				'supports'           => array( 'title', 'editor', 'revisions' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
			);

			/**
			 * Filter Question CPT registration args.
			 *
			 * @param array $args
			 */
			$args = apply_filters( 'kqas_cpt_question_args', $args );

			register_post_type( self::CPT_QUESTION, $args );
		}

		/**
		 * Register the Quiz post type (Quiz Definitions).
		 *
		 * @return void
		 */
		private function register_quiz_cpt() {

			$labels = array(
				'name'               => esc_html__( 'KidQuiz Quizzes', 'kidquiz-age-smart' ),
				'singular_name'      => esc_html__( 'Quiz', 'kidquiz-age-smart' ),
				'menu_name'          => esc_html__( 'Quizzes', 'kidquiz-age-smart' ),
				'name_admin_bar'     => esc_html__( 'Quiz', 'kidquiz-age-smart' ),
				'add_new'            => esc_html__( 'Add New', 'kidquiz-age-smart' ),
				'add_new_item'       => esc_html__( 'Add New Quiz', 'kidquiz-age-smart' ),
				'new_item'           => esc_html__( 'New Quiz', 'kidquiz-age-smart' ),
				'edit_item'          => esc_html__( 'Edit Quiz', 'kidquiz-age-smart' ),
				'view_item'          => esc_html__( 'View Quiz', 'kidquiz-age-smart' ),
				'all_items'          => esc_html__( 'Quizzes', 'kidquiz-age-smart' ),
				'search_items'       => esc_html__( 'Search Quizzes', 'kidquiz-age-smart' ),
				'not_found'          => esc_html__( 'No quizzes found.', 'kidquiz-age-smart' ),
				'not_found_in_trash' => esc_html__( 'No quizzes found in Trash.', 'kidquiz-age-smart' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => false, // We’ll attach under our own menu via admin class.
				'show_in_admin_bar'  => true,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'exclude_from_search'=> true,
				'publicly_queryable' => false,
				'query_var'          => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'menu_icon'          => 'dashicons-forms',
				'supports'           => array( 'title', 'revisions' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
			);

			/**
			 * Filter Quiz CPT registration args.
			 *
			 * @param array $args
			 */
			$args = apply_filters( 'kqas_cpt_quiz_args', $args );

			register_post_type( self::CPT_QUIZ, $args );
		}
	}

endif;
