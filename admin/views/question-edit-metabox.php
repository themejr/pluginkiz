<?php
/**
 * Admin view: Question metabox fields.
 *
 * Variables:
 * - $age_min int
 * - $age_max int
 * - $difficulty string
 * - $reading_level int
 * - $image_id int
 * - $audio_id int
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$age_min       = isset( $age_min ) ? (int) $age_min : 0;
$age_max       = isset( $age_max ) ? (int) $age_max : 0;
$difficulty    = isset( $difficulty ) ? sanitize_text_field( (string) $difficulty ) : 'easy';
$reading_level = isset( $reading_level ) ? (int) $reading_level : 1;
$image_id      = isset( $image_id ) ? (int) $image_id : 0;
$audio_id      = isset( $audio_id ) ? (int) $audio_id : 0;
?>
<div class="kqas-grid">
	<div class="kqas-field">
		<label for="kqas_age_min"><?php esc_html_e( 'Age Min', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_age_min" name="kqas_age_min" value="<?php echo esc_attr( $age_min ); ?>" min="0" max="18" />
		<p class="kqas-help"><?php esc_html_e( 'Minimum recommended age (0–18).', 'kidquiz-age-smart' ); ?></p>
	</div>

	<div class="kqas-field">
		<label for="kqas_age_max"><?php esc_html_e( 'Age Max', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_age_max" name="kqas_age_max" value="<?php echo esc_attr( $age_max ); ?>" min="0" max="18" />
		<p class="kqas-help"><?php esc_html_e( 'Maximum recommended age (0–18).', 'kidquiz-age-smart' ); ?></p>
	</div>

	<div class="kqas-field">
		<label for="kqas_difficulty"><?php esc_html_e( 'Difficulty', 'kidquiz-age-smart' ); ?></label>
		<select id="kqas_difficulty" name="kqas_difficulty">
			<option value="easy" <?php selected( $difficulty, 'easy' ); ?>><?php esc_html_e( 'Easy', 'kidquiz-age-smart' ); ?></option>
			<option value="medium" <?php selected( $difficulty, 'medium' ); ?>><?php esc_html_e( 'Medium', 'kidquiz-age-smart' ); ?></option>
			<option value="hard" <?php selected( $difficulty, 'hard' ); ?>><?php esc_html_e( 'Hard', 'kidquiz-age-smart' ); ?></option>
		</select>
	</div>

	<div class="kqas-field">
		<label for="kqas_reading_level"><?php esc_html_e( 'Reading Level', 'kidquiz-age-smart' ); ?></label>
		<select id="kqas_reading_level" name="kqas_reading_level">
			<option value="1" <?php selected( $reading_level, 1 ); ?>><?php esc_html_e( 'Level 1', 'kidquiz-age-smart' ); ?></option>
			<option value="2" <?php selected( $reading_level, 2 ); ?>><?php esc_html_e( 'Level 2', 'kidquiz-age-smart' ); ?></option>
			<option value="3" <?php selected( $reading_level, 3 ); ?>><?php esc_html_e( 'Level 3', 'kidquiz-age-smart' ); ?></option>
		</select>
	</div>

	<div class="kqas-field">
		<label for="kqas_attachment_image_id"><?php esc_html_e( 'Image Attachment ID (optional)', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_attachment_image_id" name="kqas_attachment_image_id" value="<?php echo esc_attr( $image_id ); ?>" min="0" />
		<p class="kqas-help"><?php esc_html_e( 'Planned for v1.1: image-based questions.', 'kidquiz-age-smart' ); ?></p>
	</div>

	<div class="kqas-field">
		<label for="kqas_attachment_audio_id"><?php esc_html_e( 'Audio Attachment ID (optional)', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_attachment_audio_id" name="kqas_attachment_audio_id" value="<?php echo esc_attr( $audio_id ); ?>" min="0" />
		<p class="kqas-help"><?php esc_html_e( 'Planned for v1.1: audio reading of the question.', 'kidquiz-age-smart' ); ?></p>
	</div>
</div>
