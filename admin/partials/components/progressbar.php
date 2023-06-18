<?php
/**
 * Progress Bar Component
 *
 * @package Re_Beehiiv
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( empty( $all_actions ) ) {
	$percentage = 0;
} else {
	$percentage = ( count( $complete_actions ) / count( $all_actions ) ) * 95;
	$percentage = number_format( (float) $percentage, 2, '.', '' );
	$percentage += 5; // Add 5 to the calculated percentage because the data fetching takes some time.
}

?>
<div class="status-info">
	<div class="bar-wrap">
		<div class="bar" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
	</div>
	<span class="percentage"><?php echo esc_html( $percentage ); ?>%</span>
</div>
<!-- result log box with scroll -->
<h4 class="result-log--title"><?php esc_html_e( 'Log\'s', 're-beehiiv' ); ?></h4>
<div class="result-log">
	<div class="log" id="log">
		<div class="log-item">
			<span class="log-item__time">[<?php echo esc_html( current_time( 'H:i:s' ) ); ?>]</span>
			<span class="log-item__status log-item__status--running"><?php esc_html_e( 'Running', 're-beehiiv' ); ?></span>
			<span class="log-item__message"><?php esc_html_e( 'Please wait...', 're-beehiiv' ); ?></span>
		</div>
	</div>
</div>
