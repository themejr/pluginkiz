<?php
/**
 * Admin view: Settings.
 *
 * Variables:
 * - $group string (settings group)
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$group = isset( $group ) ? sanitize_text_field( (string) $group ) : 'kqas_settings_group';
?>
<div class="wrap kqas-admin-wrap">
	<h1><?php esc_html_e( 'KidQuiz Settings', 'kidquiz-age-smart' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( $group ); ?>

		<h2><?php esc_html_e( 'Kid Codes', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="kqas_kid_code_prefix"><?php esc_html_e( 'Prefix', 'kidquiz-age-smart' ); ?></label></th>
				<td>
					<input type="text" id="kqas_kid_code_prefix" name="kqas_kid_code_prefix" value="<?php echo esc_attr( get_option( 'kqas_kid_code_prefix', 'KID' ) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Letters only (2–8). Example: KID', 'kidquiz-age-smart' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="kqas_kid_code_length"><?php esc_html_e( 'Digits length', 'kidquiz-age-smart' ); ?></label></th>
				<td>
					<input type="number" id="kqas_kid_code_length" name="kqas_kid_code_length" value="<?php echo esc_attr( (int) get_option( 'kqas_kid_code_length', 4 ) ); ?>" min="3" max="8" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="kqas_kid_code_max_active"><?php esc_html_e( 'Max active codes', 'kidquiz-age-smart' ); ?></label></th>
				<td>
					<input type="number" id="kqas_kid_code_max_active" name="kqas_kid_code_max_active" value="<?php echo esc_attr( (int) get_option( 'kqas_kid_code_max_active', 500 ) ); ?>" min="1" max="5000" />
					<p class="description"><?php esc_html_e( 'Safety limit for active codes.', 'kidquiz-age-smart' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Snapshot', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="kqas_snapshot_days"><?php esc_html_e( 'Default days', 'kidquiz-age-smart' ); ?></label></th>
				<td>
					<input type="number" id="kqas_snapshot_days" name="kqas_snapshot_days" value="<?php echo esc_attr( (int) get_option( 'kqas_snapshot_days', 7 ) ); ?>" min="1" max="30" />
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Privacy', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Allow nickname', 'kidquiz-age-smart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="kqas_allow_nickname" value="1" <?php checked( (int) get_option( 'kqas_allow_nickname', 1 ), 1 ); ?> />
						<?php esc_html_e( 'Allow kids to enter an optional nickname.', 'kidquiz-age-smart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Store IP', 'kidquiz-age-smart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="kqas_store_ip" value="1" <?php checked( (int) get_option( 'kqas_store_ip', 0 ), 1 ); ?> />
						<?php esc_html_e( 'Store IP address for sessions (not recommended by default).', 'kidquiz-age-smart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Store User-Agent', 'kidquiz-age-smart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="kqas_store_user_agent" value="1" <?php checked( (int) get_option( 'kqas_store_user_agent', 0 ), 1 ); ?> />
						<?php esc_html_e( 'Store device user-agent (not recommended by default).', 'kidquiz-age-smart' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Session Defaults', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( '4–6', 'kidquiz-age-smart' ); ?></th>
				<td>
					<input type="number" name="kqas_session_4_6_questions" value="<?php echo esc_attr( (int) get_option( 'kqas_session_4_6_questions', 5 ) ); ?>" min="1" max="30" />
					<?php esc_html_e( 'questions', 'kidquiz-age-smart' ); ?>
					&nbsp; | &nbsp;
					<input type="number" name="kqas_session_4_6_seconds" value="<?php echo esc_attr( (int) get_option( 'kqas_session_4_6_seconds', 180 ) ); ?>" min="30" max="3600" />
					<?php esc_html_e( 'seconds', 'kidquiz-age-smart' ); ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( '7–9', 'kidquiz-age-smart' ); ?></th>
				<td>
					<input type="number" name="kqas_session_7_9_questions" value="<?php echo esc_attr( (int) get_option( 'kqas_session_7_9_questions', 8 ) ); ?>" min="1" max="30" />
					<?php esc_html_e( 'questions', 'kidquiz-age-smart' ); ?>
					&nbsp; | &nbsp;
					<input type="number" name="kqas_session_7_9_seconds" value="<?php echo esc_attr( (int) get_option( 'kqas_session_7_9_seconds', 360 ) ); ?>" min="30" max="3600" />
					<?php esc_html_e( 'seconds', 'kidquiz-age-smart' ); ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( '10–12', 'kidquiz-age-smart' ); ?></th>
				<td>
					<input type="number" name="kqas_session_10_12_questions" value="<?php echo esc_attr( (int) get_option( 'kqas_session_10_12_questions', 12 ) ); ?>" min="1" max="30" />
					<?php esc_html_e( 'questions', 'kidquiz-age-smart' ); ?>
					&nbsp; | &nbsp;
					<input type="number" name="kqas_session_10_12_seconds" value="<?php echo esc_attr( (int) get_option( 'kqas_session_10_12_seconds', 600 ) ); ?>" min="30" max="3600" />
					<?php esc_html_e( 'seconds', 'kidquiz-age-smart' ); ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Rewards', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default for 4–6', 'kidquiz-age-smart' ); ?></th>
				<td>
					<select name="kqas_reward_4_6">
						<option value="stickers" <?php selected( get_option( 'kqas_reward_4_6', 'stickers' ), 'stickers' ); ?>><?php esc_html_e( 'Stickers', 'kidquiz-age-smart' ); ?></option>
						<option value="badges" <?php selected( get_option( 'kqas_reward_4_6', 'stickers' ), 'badges' ); ?>><?php esc_html_e( 'Badges', 'kidquiz-age-smart' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Default for 7–12', 'kidquiz-age-smart' ); ?></th>
				<td>
					<select name="kqas_reward_7_12">
						<option value="badges" <?php selected( get_option( 'kqas_reward_7_12', 'badges' ), 'badges' ); ?>><?php esc_html_e( 'Badges', 'kidquiz-age-smart' ); ?></option>
						<option value="stickers" <?php selected( get_option( 'kqas_reward_7_12', 'badges' ), 'stickers' ); ?>><?php esc_html_e( 'Stickers', 'kidquiz-age-smart' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Uninstall', 'kidquiz-age-smart' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'kidquiz-age-smart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="kqas_delete_data_on_uninstall" value="1" <?php checked( (int) get_option( 'kqas_delete_data_on_uninstall', 0 ), 1 ); ?> />
						<?php esc_html_e( 'If enabled, plugin data will be removed when uninstalling.', 'kidquiz-age-smart' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
