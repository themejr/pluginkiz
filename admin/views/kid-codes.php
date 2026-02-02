<?php
/**
 * Admin view: Kid Codes.
 *
 * Variables expected:
 * - $notice string
 * - $error string
 * - $rows array
 * - $status string
 * - $search string
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$notice = isset( $notice ) ? sanitize_text_field( (string) $notice ) : '';
$error  = isset( $error ) ? sanitize_text_field( (string) $error ) : '';
$status = isset( $status ) ? sanitize_text_field( (string) $status ) : '';
$search = isset( $search ) ? sanitize_text_field( (string) $search ) : '';
$rows   = isset( $rows ) && is_array( $rows ) ? $rows : array();

?>
<div class="wrap kqas-admin-wrap">
	<h1><?php esc_html_e( 'Kid Codes', 'kidquiz-age-smart' ); ?></h1>

	<?php if ( '' !== $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<?php if ( '' !== $error ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<div class="kqas-card">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Generate Kid Codes', 'kidquiz-age-smart' ); ?></h2>

		<form method="post">
			<?php wp_nonce_field( 'kqas_kid_codes', '_kqas_nonce' ); ?>
			<input type="hidden" name="kqas_action" value="generate" />

			<p>
				<label for="kqas_generate_count"><strong><?php esc_html_e( 'Count', 'kidquiz-age-smart' ); ?></strong></label><br/>
				<input type="number" id="kqas_generate_count" name="kqas_generate_count" value="10" min="1" max="200" />
			</p>

			<p>
				<label for="kqas_generate_label"><strong><?php esc_html_e( 'Label (optional)', 'kidquiz-age-smart' ); ?></strong></label><br/>
				<input type="text" id="kqas_generate_label" name="kqas_generate_label" value="" class="regular-text" />
			</p>

			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Generate', 'kidquiz-age-smart' ); ?></button>
			</p>
		</form>
	</div>

	<div class="kqas-card">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Codes List', 'kidquiz-age-smart' ); ?></h2>

		<form method="get" style="margin: 10px 0 14px;">
			<input type="hidden" name="page" value="kqas-kid-codes" />
			<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Search code/label…', 'kidquiz-age-smart' ); ?>" />
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'kidquiz-age-smart' ); ?></option>
				<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'kidquiz-age-smart' ); ?></option>
				<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'kidquiz-age-smart' ); ?></option>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'kidquiz-age-smart' ); ?></button>
		</form>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'kidquiz-age-smart' ); ?></th>
					<th><?php esc_html_e( 'Label', 'kidquiz-age-smart' ); ?></th>
					<th><?php esc_html_e( 'Status', 'kidquiz-age-smart' ); ?></th>
					<th><?php esc_html_e( 'Created', 'kidquiz-age-smart' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'kidquiz-age-smart' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No codes found.', 'kidquiz-age-smart' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$code   = isset( $row['kid_code'] ) ? sanitize_text_field( (string) $row['kid_code'] ) : '';
						$label  = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
						$rstat  = isset( $row['status'] ) ? sanitize_text_field( (string) $row['status'] ) : '';
						$create = isset( $row['created_at'] ) ? sanitize_text_field( (string) $row['created_at'] ) : '';
						?>
						<tr>
							<td><code class="kqas-code"><?php echo esc_html( $code ); ?></code></td>
							<td><?php echo esc_html( $label ); ?></td>
							<td><?php echo esc_html( $rstat ); ?></td>
							<td><?php echo esc_html( $create ); ?></td>
							<td>
								<form method="post" style="display:inline-block;margin-right:6px;">
									<?php wp_nonce_field( 'kqas_kid_codes', '_kqas_nonce' ); ?>
									<input type="hidden" name="kqas_code" value="<?php echo esc_attr( $code ); ?>" />

									<?php if ( 'active' === $rstat ) : ?>
										<input type="hidden" name="kqas_action" value="deactivate" />
										<button type="submit" class="button"><?php esc_html_e( 'Deactivate', 'kidquiz-age-smart' ); ?></button>
									<?php else : ?>
										<input type="hidden" name="kqas_action" value="activate" />
										<button type="submit" class="button"><?php esc_html_e( 'Activate', 'kidquiz-age-smart' ); ?></button>
									<?php endif; ?>
								</form>

								<form method="post" style="display:inline-block;">
									<?php wp_nonce_field( 'kqas_kid_codes', '_kqas_nonce' ); ?>
									<input type="hidden" name="kqas_action" value="delete" />
									<input type="hidden" name="kqas_code" value="<?php echo esc_attr( $code ); ?>" />
									<button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this code?', 'kidquiz-age-smart' ) ); ?>');">
										<?php esc_html_e( 'Delete', 'kidquiz-age-smart' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
