<?php
/**
 * Parent Snapshot view.
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
$days     = isset( $days ) ? (int) $days : 7;
$error    = isset( $error ) ? sanitize_text_field( (string) $error ) : '';
?>
<div class="kqas-card">
	<h3 style="margin-top:0;"><?php esc_html_e( 'Parent Snapshot', 'kidquiz-age-smart' ); ?></h3>

	<?php if ( '' !== $error ) : ?>
		<p style="color:#b32d2e;"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( is_array( $snapshot ) ) : ?>
		<p class="kqas-hint">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: days */
					__( 'Last %d days', 'kidquiz-age-smart' ),
					(int) $snapshot['days']
				)
			);
			?>
		</p>

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
	<?php else : ?>
		<p class="kqas-hint"><?php esc_html_e( 'Enter a valid Kid Code to view the snapshot.', 'kidquiz-age-smart' ); ?></p>
	<?php endif; ?>
</div>
