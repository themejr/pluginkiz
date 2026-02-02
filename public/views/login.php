<?php
/**
 * Kids Mode - Login card partial.
 *
 * Variables:
 * - (optional) $title string
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

$title = isset( $title ) ? sanitize_text_field( (string) $title ) : '';
?>
<div class="kqas-card" data-kqas="login">
	<?php if ( '' !== $title ) : ?>
		<h2><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<h3 style="margin-top:0;"><?php esc_html_e( 'Enter your Kid Code', 'kidquiz-age-smart' ); ?></h3>

	<div class="kqas-row">
		<input type="text" data-kqas="kid_code" placeholder="KID-1234" autocomplete="off" />
		<input type="text" data-kqas="nickname" placeholder="<?php esc_attr_e( 'Nickname (optional)', 'kidquiz-age-smart' ); ?>" autocomplete="off" />
		<button type="button" class="kqas-bigbtn kqas-primary" data-kqas="start_btn"><?php esc_html_e( 'Start', 'kidquiz-age-smart' ); ?></button>
	</div>

	<p class="kqas-hint"><?php esc_html_e( 'No email, no account. Just your Kid Code.', 'kidquiz-age-smart' ); ?></p>
	<div class="kqas-hint" data-kqas="login_msg" style="color:#b32d2e;"></div>
</div>
