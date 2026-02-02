<?php
/**
 * Kids Mode - Result card partial.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="kqas-card" data-kqas="result" style="display:none;">
	<h3 style="margin-top:0;"><?php esc_html_e( 'Great job!', 'kidquiz-age-smart' ); ?></h3>
	<p data-kqas="result_text"></p>
	<div data-kqas="reward_box" style="margin-top:10px;"></div>
	<button type="button" class="kqas-bigbtn kqas-primary" data-kqas="restart_btn"><?php esc_html_e( 'Play again', 'kidquiz-age-smart' ); ?></button>
</div>
