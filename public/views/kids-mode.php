<?php
/**
 * Kids Mode wrapper view.
 *
 * Variables:
 * - $quiz_id int
 * - $title string
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$quiz_id = isset( $quiz_id ) ? (int) $quiz_id : 0;
$title   = isset( $title ) ? sanitize_text_field( (string) $title ) : '';
?>
<div class="kqas-kids" data-kqas-root="1" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">

	<?php
	// Login card.
	include __DIR__ . '/login.php';
	?>

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

	<?php
	// Result card.
	include __DIR__ . '/result.php';
	?>

</div>
