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
	$percentage = ( count( $complete_actions ) / count( $all_actions ) ) * 100;
	$percentage = number_format( (float) $percentage, 2, '.', '' );
}

?>
<div class="status-info">
	<div class="bar-wrap">
		<div class="bar" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
	</div>
	<span class="percentage"><?php echo esc_html( $percentage ); ?>%</span>
</div>
<!-- result log box with scroll -->
<h4 class="hidden result-log--title">Result Log</h4>
<div class="hidden result-log">
	<div class="log" id="log">
	</div>
</div>
