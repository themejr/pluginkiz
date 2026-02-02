<?php
/**
 * Admin view: Reports.
 *
 * Variables:
 * - $kid_code string
 * - $days int
 * - $snapshot array|null
 * - $error string
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$kid_code = isset( $kid_code ) ? sanitize_text_field( (string) $kid_code ) : '';
$days     = isset( $days ) ? (int) $days : 0;
$snapshot = isset( $snapshot ) && is_array( $snapshot ) ? $snapshot : null;
$error    = isset( $error ) ? sanitize_text_field( (string) $error ) : '';
?>
<div class="wrap kqas-admin-wrap">
	<h1><?php esc_html_e( 'Reports', 'kidquiz-age-smart' ); ?></h1>

	<div class="kqas-card">
		<form method="get" class="kqas-actions">
			<input type="hidden" name="page" value="kqas-reports" />

			<label>
				<strong><?php esc_html_e( 'Kid Code', 'kidquiz-age-smart' ); ?></strong><br/>
				<input type="text" name="kid_code" value="<?php echo esc_attr( $kid_code ); ?>" class="regular-text" placeholder="KID-1234"/>
			</label>

			<label>
				<strong><?php esc_html_e( 'Days', 'kidquiz-age-smart' ); ?></strong><br/>
				<input type="number" name="days" value="<?php echo esc_attr( $days ); ?>" min="1" max="30" style="width:90px" />
			</label>

			<div style="padding-top:18px;">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'View', 'kidquiz-age-smart' ); ?></button>
			</div>
		</form>

		<?php if ( '' !== $error ) : ?>
			<p style="color:#b32d2e;margin-top:12px;"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( is_array( $snapshot ) ) : ?>
		<div class="kqas-card">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Snapshot', 'kidquiz-age-smart' ); ?></h2>

			<ul style="margin:0 0 10px 18px;">
				<li><?php echo esc_html( sprintf( __( 'Sessions: %d', 'kidquiz-age-smart' ), (int) $snapshot['sessions_count'] ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Total questions: %d', 'kidquiz-age-smart' ), (int) $snapshot['total_questions'] ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Total correct: %d', 'kidquiz-age-smart' ), (int) $snapshot['total_correct'] ) ); ?></li>
			</ul>

			<?php if ( ! empty( $snapshot['best_skills'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Best skills:', 'kidquiz-age-smart' ); ?></strong>
					<?php echo esc_html( implode( ', ', array_map( 'sanitize_text_field', (array) $snapshot['best_skills'] ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $snapshot['weakest_skills'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Needs practice:', 'kidquiz-age-smart' ); ?></strong>
					<?php echo esc_html( implode( ', ', array_map( 'sanitize_text_field', (array) $snapshot['weakest_skills'] ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $snapshot['recommendation'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Recommendation:', 'kidquiz-age-smart' ); ?></strong>
					<?php echo esc_html( (string) $snapshot['recommendation'] ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
