<?php
/**
 * Admin view: Quiz settings metabox fields.
 *
 * Variables:
 * - $target_age_group string
 * - $session_q int
 * - $session_sec int
 * - $reward_mode string
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$target_age_group = isset( $target_age_group ) ? sanitize_text_field( (string) $target_age_group ) : '4-6';
$session_q        = isset( $session_q ) ? (int) $session_q : 0;
$session_sec      = isset( $session_sec ) ? (int) $session_sec : 0;
$reward_mode      = isset( $reward_mode ) ? sanitize_text_field( (string) $reward_mode ) : '';
?>
<div class="kqas-grid">
	<div class="kqas-field">
		<label for="kqas_target_age_group"><?php esc_html_e( 'Target Age Group', 'kidquiz-age-smart' ); ?></label>
		<select id="kqas_target_age_group" name="kqas_target_age_group">
			<option value="4-6" <?php selected( $target_age_group, '4-6' ); ?>><?php esc_html_e( '4–6 (Kindergarten)', 'kidquiz-age-smart' ); ?></option>
			<option value="7-9" <?php selected( $target_age_group, '7-9' ); ?>><?php esc_html_e( '7–9 (Early Primary)', 'kidquiz-age-smart' ); ?></option>
			<option value="10-12" <?php selected( $target_age_group, '10-12' ); ?>><?php esc_html_e( '10–12 (Upper Primary)', 'kidquiz-age-smart' ); ?></option>
		</select>
	</div>

	<div class="kqas-field">
		<label for="kqas_reward_mode"><?php esc_html_e( 'Reward Mode', 'kidquiz-age-smart' ); ?></label>
		<select id="kqas_reward_mode" name="kqas_reward_mode">
			<option value="stickers" <?php selected( $reward_mode, 'stickers' ); ?>><?php esc_html_e( 'Stickers', 'kidquiz-age-smart' ); ?></option>
			<option value="badges" <?php selected( $reward_mode, 'badges' ); ?>><?php esc_html_e( 'Badges', 'kidquiz-age-smart' ); ?></option>
		</select>
		<p class="kqas-help"><?php esc_html_e( 'If empty/invalid, defaults by age group will be applied.', 'kidquiz-age-smart' ); ?></p>
	</div>

	<div class="kqas-field">
		<label for="kqas_session_questions"><?php esc_html_e( 'Session Questions (optional)', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_session_questions" name="kqas_session_questions" value="<?php echo esc_attr( $session_q ); ?>" min="0" max="30" />
		<p class="kqas-help"><?php esc_html_e( 'Leave 0 to use defaults from Settings.', 'kidquiz-age-smart' ); ?></p>
	</div>

	<div class="kqas-field">
		<label for="kqas_session_seconds"><?php esc_html_e( 'Session Time Limit (seconds)', 'kidquiz-age-smart' ); ?></label>
		<input type="number" id="kqas_session_seconds" name="kqas_session_seconds" value="<?php echo esc_attr( $session_sec ); ?>" min="0" max="3600" />
		<p class="kqas-help"><?php esc_html_e( 'Leave 0 to use defaults from Settings.', 'kidquiz-age-smart' ); ?></p>
	</div>
</div>
