<?php
/**
 * Public Router for Kids Mode.
 *
 * Routes:
 * - /kidquiz/            -> Kids Mode (default quiz)
 * - /kidquiz/{quiz_id}/  -> Kids Mode for a specific quiz
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Router', false ) ) :

	final class KQAS_Router {

		const QV_KIDS   = 'kqas_kids';
		const QV_QUIZID = 'kqas_quiz_id';

		/**
		 * Hook: init
		 *
		 * @return void
		 */
		public function add_rewrite_rules() {
			$slug = $this->route_slug();

			add_rewrite_rule(
				'^' . preg_quote( $slug, '/' ) . '/?$',
				'index.php?' . self::QV_KIDS . '=1',
				'top'
			);

			add_rewrite_rule(
				'^' . preg_quote( $slug, '/' ) . '/([0-9]+)/?$',
				'index.php?' . self::QV_KIDS . '=1&' . self::QV_QUIZID . '=$matches[1]',
				'top'
			);
		}

		/**
		 * Hook: query_vars
		 *
		 * @param string[] $vars Vars.
		 * @return string[]
		 */
		public function register_query_vars( $vars ) {
			$vars[] = self::QV_KIDS;
			$vars[] = self::QV_QUIZID;
			return $vars;
		}

		/**
		 * Hook: template_redirect
		 *
		 * @return void
		 */
		public function maybe_render_kids_route() {
			$is_kids = (int) get_query_var( self::QV_KIDS );

			if ( 1 !== $is_kids ) {
				return;
			}

			// Optional: hide admin bar for kids route.
			add_filter( 'show_admin_bar', '__return_false' );

			$quiz_id = (int) get_query_var( self::QV_QUIZID );
			$quiz_id = max( 0, $quiz_id );

			status_header( 200 );
			nocache_headers();

			?>
			<!doctype html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title><?php echo esc_html( get_bloginfo( 'name' ) . ' - KidQuiz' ); ?></title>
				<?php wp_head(); ?>
			</head>
			<body <?php body_class( 'kqas-route-kids' ); ?>>
				<?php
				// For WP 5.2+ themes/plugins compatibility.
				if ( function_exists( 'wp_body_open' ) ) {
					wp_body_open();
				}
				?>

				<div class="kqas-route-wrap">
					<?php
					$sc = '[kidquiz_kids_mode]';
					if ( $quiz_id > 0 ) {
						$sc = '[kidquiz_kids_mode quiz_id="' . (int) $quiz_id . '"]';
					}
					echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>

				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
			exit;
		}

		/**
		 * Route slug (filterable).
		 *
		 * @return string
		 */
		private function route_slug() {
			$slug = apply_filters( 'kqas_kids_route_slug', 'kidquiz' );
			$slug = sanitize_title( (string) $slug );
			return ( '' !== $slug ) ? $slug : 'kidquiz';
		}
	}

endif;
