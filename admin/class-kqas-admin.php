<?php
/**
 * Admin area functionality for KidQuiz Age-Smart.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Admin', false ) ) :

	final class KQAS_Admin {

		private $plugin_name;
		private $version;

		private $cap_manage_content  = 'edit_posts';
		private $cap_manage_settings = 'manage_options';

		public function __construct( $plugin_name, $version ) {
			$this->plugin_name = (string) $plugin_name;
			$this->version     = (string) $version;
		}

		private function is_kqas_admin_screen() {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return false;
			}

			$screen = get_current_screen();
			if ( ! ( $screen instanceof WP_Screen ) ) {
				return false;
			}

			$ids = array(
				'toplevel_page_kqas-dashboard',
				'kqas-dashboard_page_kqas-kid-codes',
				'kqas-dashboard_page_kqas-reports',
				'kqas-dashboard_page_kqas-settings',
			);

			if ( in_array( $screen->id, $ids, true ) ) {
				return true;
			}

			// CPT screens.
			if ( class_exists( 'KQAS_Post_Types' ) ) {
				$cpts = array( KQAS_Post_Types::CPT_QUESTION, KQAS_Post_Types::CPT_QUIZ );

				if ( isset( $screen->post_type ) && in_array( (string) $screen->post_type, $cpts, true ) ) {
					return true;
				}

				if ( 0 === strpos( (string) $screen->id, 'edit-' ) ) {
					foreach ( $cpts as $cpt ) {
						if ( 'edit-' . $cpt === $screen->id ) {
							return true;
						}
					}
				}
			}

			return false;
		}

		public function enqueue_styles() {
			if ( ! $this->is_kqas_admin_screen() ) {
				return;
			}

			$rel  = 'assets/admin/css/admin.css';
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . $rel;

			if ( file_exists( $path ) ) {
				wp_enqueue_style(
					'kqas-admin',
					trailingslashit( KQAS_PLUGIN_URL ) . $rel,
					array(),
					$this->version
				);
			}
		}

		public function enqueue_scripts() {
			if ( ! $this->is_kqas_admin_screen() ) {
				return;
			}

			$rel  = 'assets/admin/js/admin.js';
			$path = trailingslashit( KQAS_PLUGIN_DIR ) . $rel;

			if ( file_exists( $path ) ) {
				wp_enqueue_script(
					'kqas-admin',
					trailingslashit( KQAS_PLUGIN_URL ) . $rel,
					array(),
					$this->version,
					true
				);
			}
		}

		public function register_menu_pages() {
			add_menu_page(
				__( 'KidQuiz', 'kidquiz-age-smart' ),
				__( 'KidQuiz', 'kidquiz-age-smart' ),
				$this->cap_manage_content,
				'kqas-dashboard',
				array( $this, 'render_dashboard_page' ),
				'dashicons-welcome-learn-more',
				58
			);

			add_submenu_page(
				'kqas-dashboard',
				__( 'Questions', 'kidquiz-age-smart' ),
				__( 'Questions', 'kidquiz-age-smart' ),
				$this->cap_manage_content,
				'edit.php?post_type=' . ( class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUESTION : 'kqas_question' )
			);

			add_submenu_page(
				'kqas-dashboard',
				__( 'Quizzes', 'kidquiz-age-smart' ),
				__( 'Quizzes', 'kidquiz-age-smart' ),
				$this->cap_manage_content,
				'edit.php?post_type=' . ( class_exists( 'KQAS_Post_Types' ) ? KQAS_Post_Types::CPT_QUIZ : 'kqas_quiz' )
			);

			add_submenu_page(
				'kqas-dashboard',
				__( 'Kid Codes', 'kidquiz-age-smart' ),
				__( 'Kid Codes', 'kidquiz-age-smart' ),
				$this->cap_manage_content,
				'kqas-kid-codes',
				array( $this, 'render_kid_codes_page' )
			);

			add_submenu_page(
				'kqas-dashboard',
				__( 'Reports', 'kidquiz-age-smart' ),
				__( 'Reports', 'kidquiz-age-smart' ),
				$this->cap_manage_content,
				'kqas-reports',
				array( $this, 'render_reports_page' )
			);

			add_submenu_page(
				'kqas-dashboard',
				__( 'Settings', 'kidquiz-age-smart' ),
				__( 'Settings', 'kidquiz-age-smart' ),
				$this->cap_manage_settings,
				'kqas-settings',
				array( $this, 'render_settings_page' )
			);
		}

		public function action_links( $links ) {
			$url = admin_url( 'admin.php?page=kqas-settings' );
			$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'kidquiz-age-smart' ) . '</a>';
			return $links;
		}

		public function render_dashboard_page() {
			if ( ! current_user_can( $this->cap_manage_content ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'kidquiz-age-smart' ) );
			}
			?>
			<div class="wrap kqas-admin-wrap">
				<h1><?php esc_html_e( 'KidQuiz', 'kidquiz-age-smart' ); ?></h1>
				<p class="kqas-muted"><?php esc_html_e( 'Use the menu to manage questions, quizzes, kid codes, and reports.', 'kidquiz-age-smart' ); ?></p>
			</div>
			<?php
		}

		public function render_kid_codes_page() {
			if ( ! current_user_can( $this->cap_manage_content ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'kidquiz-age-smart' ) );
			}

			$notice = '';
			$error  = '';

			$action = isset( $_POST['kqas_action'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( '' !== $action ) {
				$nonce_ok = ( isset( $_POST['_kqas_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_kqas_nonce'] ) ), 'kqas_kid_codes' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! $nonce_ok ) {
					$error = esc_html__( 'Security check failed. Please try again.', 'kidquiz-age-smart' );
				} elseif ( ! class_exists( 'KQAS_Kid_Codes' ) ) {
					$error = esc_html__( 'Kid Codes module is missing.', 'kidquiz-age-smart' );
				} else {
					if ( 'generate' === $action ) {
						$count = isset( $_POST['kqas_generate_count'] ) ? (int) $_POST['kqas_generate_count'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$label = isset( $_POST['kqas_generate_label'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_generate_label'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$res   = KQAS_Kid_Codes::generate_many( $count, $label );

						if ( ! empty( $res['errors'] ) ) {
							$error = implode( ' ', array_map( 'sanitize_text_field', (array) $res['errors'] ) );
						} else {
							$notice = sprintf(
								/* translators: %d: count */
								esc_html__( 'Generated %d Kid Codes.', 'kidquiz-age-smart' ),
								count( (array) $res['codes'] )
							);
						}
					}

					if ( 'activate' === $action || 'deactivate' === $action || 'delete' === $action ) {
						$code = isset( $_POST['kqas_code'] ) ? sanitize_text_field( wp_unslash( $_POST['kqas_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

						if ( 'activate' === $action ) {
							$res = KQAS_Kid_Codes::activate_code( $code );
						} elseif ( 'deactivate' === $action ) {
							$res = KQAS_Kid_Codes::deactivate_code( $code );
						} else {
							$res = KQAS_Kid_Codes::delete_code( $code );
						}

						if ( is_wp_error( $res ) ) {
							$error = $res->get_error_message();
						} else {
							$notice = esc_html__( 'Action completed.', 'kidquiz-age-smart' );
						}
					}
				}
			}

			$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$rows = array();
			if ( class_exists( 'KQAS_Kid_Codes' ) ) {
				$rows = KQAS_Kid_Codes::list_codes(
					array(
						'status' => $status,
						'limit'  => 100,
						'offset' => 0,
						'search' => $search,
					)
				);
			}

			if ( class_exists( 'KQAS_Helpers' ) ) {
				echo KQAS_Helpers::render_admin_view(
					'kid-codes.php',
					array(
						'notice' => $notice,
						'error'  => $error,
						'rows'   => $rows,
						'status' => $status,
						'search' => $search,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}

			// Fallback minimal.
			echo '<div class="wrap"><h1>' . esc_html__( 'Kid Codes', 'kidquiz-age-smart' ) . '</h1></div>';
		}

		public function render_reports_page() {
			if ( ! current_user_can( $this->cap_manage_content ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'kidquiz-age-smart' ) );
			}

			$kid_code = isset( $_GET['kid_code'] ) ? sanitize_text_field( wp_unslash( $_GET['kid_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$days     = isset( $_GET['days'] ) ? (int) $_GET['days'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$snapshot = null;
			$error    = '';

			if ( '' !== $kid_code ) {
				if ( class_exists( 'KQAS_Parent_Snapshot' ) ) {
					$args = array();
					if ( $days > 0 ) {
						$args['days'] = $days;
					}
					$res = KQAS_Parent_Snapshot::get_snapshot( $kid_code, $args );
					if ( is_wp_error( $res ) ) {
						$error = $res->get_error_message();
					} else {
						$snapshot = $res;
					}
				} else {
					$error = esc_html__( 'Snapshot module is missing.', 'kidquiz-age-smart' );
				}
			}

			if ( class_exists( 'KQAS_Helpers' ) ) {
				echo KQAS_Helpers::render_admin_view(
					'reports.php',
					array(
						'kid_code' => $kid_code,
						'days'     => $days,
						'snapshot' => $snapshot,
						'error'    => $error,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}

			echo '<div class="wrap"><h1>' . esc_html__( 'Reports', 'kidquiz-age-smart' ) . '</h1></div>';
		}

		public function render_settings_page() {
			if ( ! current_user_can( $this->cap_manage_settings ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'kidquiz-age-smart' ) );
			}

			$group = class_exists( 'KQAS_Settings' ) ? KQAS_Settings::OPTION_GROUP : 'kqas_settings_group';

			if ( class_exists( 'KQAS_Helpers' ) ) {
				echo KQAS_Helpers::render_admin_view(
					'settings.php',
					array(
						'group' => $group,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}

			echo '<div class="wrap"><h1>' . esc_html__( 'KidQuiz Settings', 'kidquiz-age-smart' ) . '</h1></div>';
		}
	}

endif;
