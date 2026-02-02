<?php
/**
 * Register taxonomies for KidQuiz Age-Smart.
 *
 * MVP uses a simple "Skill" taxonomy for questions to enable:
 * - Best 2 skills / Weakest 2 skills (Parent Snapshot)
 * - Filtering question bank by skill
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Taxonomies', false ) ) :

	final class KQAS_Taxonomies {

		/**
		 * Skill taxonomy slug.
		 */
		const TAX_SKILL = 'kqas_skill';

		/**
		 * Register all plugin taxonomies.
		 *
		 * @return void
		 */
		public function register_taxonomies() {
			$this->register_skill_taxonomy();
		}

		/**
		 * Register the Skill taxonomy for questions.
		 *
		 * @return void
		 */
		private function register_skill_taxonomy() {
			if ( ! class_exists( 'KQAS_Post_Types' ) ) {
				return;
			}

			$labels = array(
				'name'              => esc_html__( 'Skills', 'kidquiz-age-smart' ),
				'singular_name'     => esc_html__( 'Skill', 'kidquiz-age-smart' ),
				'search_items'      => esc_html__( 'Search Skills', 'kidquiz-age-smart' ),
				'all_items'         => esc_html__( 'All Skills', 'kidquiz-age-smart' ),
				'parent_item'       => esc_html__( 'Parent Skill', 'kidquiz-age-smart' ),
				'parent_item_colon' => esc_html__( 'Parent Skill:', 'kidquiz-age-smart' ),
				'edit_item'         => esc_html__( 'Edit Skill', 'kidquiz-age-smart' ),
				'update_item'       => esc_html__( 'Update Skill', 'kidquiz-age-smart' ),
				'add_new_item'      => esc_html__( 'Add New Skill', 'kidquiz-age-smart' ),
				'new_item_name'     => esc_html__( 'New Skill Name', 'kidquiz-age-smart' ),
				'menu_name'         => esc_html__( 'Skills', 'kidquiz-age-smart' ),
			);

			$args = array(
				'labels'            => $labels,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'query_var'         => false,
				'rewrite'           => false,
			);

			/**
			 * Filter Skill taxonomy registration args.
			 *
			 * @param array $args
			 */
			$args = apply_filters( 'kqas_tax_skill_args', $args );

			register_taxonomy(
				self::TAX_SKILL,
				array( KQAS_Post_Types::CPT_QUESTION ),
				$args
			);
		}
	}

endif;
