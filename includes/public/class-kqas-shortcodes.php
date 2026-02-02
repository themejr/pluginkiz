<?php
/**
 * Shortcodes for KidQuiz Age-Smart.
 *
 * MVP shortcodes:
 * - [kidquiz_kids_mode quiz_id="123"]  -> Kids Mode app shell
 * - [kidquiz_parent_snapshot kid_code="KID-1234" days="7"] -> Simple snapshot output
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Shortcodes', false ) ) :

	final class KQAS_Shortcodes {

		/**
		 * Register shortcodes.
		 *
		 * @return void
		 */
		public function register_shortcodes() {
			add_shortcode( 'kidquiz_kids_mode', array( $this, 'kids_mode' ) );
			add_shortcode( 'kidquiz_parent_snapshot', array( $this, 'parent_snapshot' ) );
		}

		/**
		 * Kids Mode app shell.
		 *
		 * @param array $atts Shortcode atts.
		 * @return string
		 */
		public function kids_mode( $atts ) {
			$atts = shortcode_atts(
				array(
					'quiz_id' => '',
					'title'  => '',
				),
				(array) $atts,
				'kidquiz_kids_mode'
			);

			$quiz_id = (int) $atts['quiz_id'];
			$title   = sanitize_text_field( (string) $atts['title'] );

			ob_start();
			?>
			<div class="kqas-kids" data-kqas-root="1" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">
				<?php if ( '' !== $title ) : ?>
					<h2><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>

				<div class="kqas-card" data-kqas="login">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Enter your Kid Code', 'kidquiz-age-smart' ); ?></h3>

					<div class="kqas-row">
						<input type="text" data-kqas="kid_code" placeholder="KID-1234" autocomplete="off" />
						<input type="text" data-kqas="nickname" placeholder="<?php esc_attr_e( 'Nickname (optional)', 'kidquiz-age-smart' ); ?>" autocomplete="off" />
						<button type="button" class="kqas-bigbtn kqas-primary" data-kqas="start_btn"><?php esc_html_e( 'Start', 'kidquiz-age-smart' ); ?></button>
					</div>

					<p class="kqas-hint"><?php esc_html_e( 'No email, no account. Just your Kid Code.', 'kidquiz-age-smart' ); ?></p>
					<div class="kqas-hint" data-kqas="login_msg" style="color:#b32d2e;"></div>
				</div>

				<div class="kqas-card" data-kqas="quiz" style="display:none;">
					<div class="kqas-row" style="justify-content:space-between;">
						<div><strong><?php esc_html_e( 'KidQuiz', 'kidquiz-age-smart' ); ?></strong></div>
						<div class="kqas-hint" data-kqas="timer" style="margin:0;"></div>
					</div>

					<p class="kqas-q" data-kqas="question_text"><?php esc_html_e( 'Loading…', 'kidquiz-age-smart' ); ?></p>

					<div class="kqas-ans">
						<button type="button" data-kqas="answer_btn"><?php esc_html_e( 'A', 'kidquiz-age-smart' ); ?></button>
						<button type="button" data-kqas="answer_btn"><?php esc_html_e( 'B', 'kidquiz-age-smart' ); ?></button>
						<button type="button" data-kqas="answer_btn"><?php esc_html_e( 'C', 'kidquiz-age-smart' ); ?></button>
						<button type="button" data-kqas="answer_btn"><?php esc_html_e( 'D', 'kidquiz-age-smart' ); ?></button>
					</div>

					<div class="kqas-row" style="margin-top:14px;">
						<button type="button" class="kqas-bigbtn kqas-secondary" data-kqas="next_btn"><?php esc_html_e( 'Next', 'kidquiz-age-smart' ); ?></button>
						<button type="button" class="kqas-bigbtn" style="background:#b32d2e;color:#fff" data-kqas="finish_btn"><?php esc_html_e( 'Finish', 'kidquiz-age-smart' ); ?></button>
						<div class="kqas-hint" data-kqas="feedback" style="margin:0;"></div>
					</div>
				</div>

				<div class="kqas-card" data-kqas="result" style="display:none;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Great job!', 'kidquiz-age-smart' ); ?></h3>
					<p data-kqas="result_text"></p>
					<div data-kqas="reward_box" style="margin-top:10px;"></div>
					<button type="button" class="kqas-bigbtn kqas-primary" data-kqas="restart_btn"><?php esc_html_e( 'Play again', 'kidquiz-age-smart' ); ?></button>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		/**
		 * Parent Snapshot shortcode output.
		 *
		 * @param array $atts Shortcode atts.
		 * @return string
		 */
		public function parent_snapshot( $atts ) {
			$atts = shortcode_atts(
				array(
					'kid_code' => '',
					'days'     => '',
				),
				(array) $atts,
				'kidquiz_parent_snapshot'
			);

			$kid_code = sanitize_text_field( (string) $atts['kid_code'] );
			$days     = (int) $atts['days'];

			if ( '' === $kid_code ) {
				return '<div class="kqas-parent-snapshot"><em>' . esc_html__( 'Please provide a kid_code.', 'kidquiz-age-smart' ) . '</em></div>';
			}

			if ( ! class_exists( 'KQAS_Parent_Snapshot' ) ) {
				return '<div class="kqas-parent-snapshot"><em>' . esc_html__( 'Snapshot module is missing.', 'kidquiz-age-smart' ) . '</em></div>';
			}

			$args = array();
			if ( $days > 0 ) {
				$args['days'] = max( 1, min( 30, $days ) );
			}

			$res = KQAS_Parent_Snapshot::get_snapshot( $kid_code, $args );
			if ( is_wp_error( $res ) ) {
				return '<div class="kqas-parent-snapshot"><strong>' . esc_html__( 'Error:', 'kidquiz-age-smart' ) . '</strong> ' . esc_html( $res->get_error_message() ) . '</div>';
			}

			ob_start();
			?>
			<div class="kqas-parent-snapshot" style="border:1px solid #dcdcde;border-radius:12px;padding:14px;margin:12px 0;">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Parent Snapshot', 'kidquiz-age-smart' ); ?></h3>

				<p>
					<strong><?php esc_html_e( 'Last days:', 'kidquiz-age-smart' ); ?></strong> <?php echo esc_html( (string) $res['days'] ); ?>
					<br />
					<strong><?php esc_html_e( 'Sessions:', 'kidquiz-age-smart' ); ?></strong> <?php echo esc_html( (string) $res['sessions_count'] ); ?>
					&nbsp;|&nbsp;
					<strong><?php esc_html_e( 'Correct:', 'kidquiz-age-smart' ); ?></strong> <?php echo esc_html( (string) $res['total_correct'] ); ?>/<?php echo esc_html( (string) $res['total_questions'] ); ?>
				</p>

				<div style="display:flex;gap:18px;flex-wrap:wrap;">
					<div style="min-width:240px;">
						<strong><?php esc_html_e( 'Best Skills', 'kidquiz-age-smart' ); ?></strong>
						<ul>
							<?php if ( empty( $res['best_skills'] ) ) : ?>
								<li><?php esc_html_e( 'Not enough data yet.', 'kidquiz-age-smart' ); ?></li>
							<?php else : ?>
								<?php foreach ( (array) $res['best_skills'] as $it ) : ?>
									<li><?php echo esc_html( (string) $it['name'] ); ?> <small>(<?php echo esc_html( (string) $it['correct'] ); ?>/<?php echo esc_html( (string) $it['attempts'] ); ?>)</small></li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>

					<div style="min-width:240px;">
						<strong><?php esc_html_e( 'Weakest Skills', 'kidquiz-age-smart' ); ?></strong>
						<ul>
							<?php if ( empty( $res['weakest_skills'] ) ) : ?>
								<li><?php esc_html_e( 'Not enough data yet.', 'kidquiz-age-smart' ); ?></li>
							<?php else : ?>
								<?php foreach ( (array) $res['weakest_skills'] as $it ) : ?>
									<li><?php echo esc_html( (string) $it['name'] ); ?> <small>(<?php echo esc_html( (string) $it['correct'] ); ?>/<?php echo esc_html( (string) $it['attempts'] ); ?>)</small></li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>
				</div>

				<p><strong><?php esc_html_e( 'Recommendation:', 'kidquiz-age-smart' ); ?></strong> <?php echo esc_html( (string) $res['recommendation'] ); ?></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}
	}

endif;
