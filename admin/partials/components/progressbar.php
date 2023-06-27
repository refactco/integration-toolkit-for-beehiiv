<?php
/**
 * Progress Bar Component
 *
 * @package Re_Beehiiv
 */
use Re_Beehiiv\Import\Import;
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( empty( $all_actions ) ) {
	$percentage = 0;
} else {
	$percentage = ( count( $complete_actions ) / count( $all_actions ) ) * 80;
	$percentage = number_format( (float) $percentage, 2, '.', '' );
	$percentage += 20; // Add 5 to the calculated percentage because the data fetching takes some time.
}

if ( isset( $_GET['cancel'] ) && isset( $_GET['nonce'] ) ) {
	$logs = Import::maybe_cancel_import();
}

?>
<div class="status-info">
	<div class="bar-wrap">
		<div class="bar" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
	</div>
	<span class="percentage"><?php echo esc_html( $percentage ); ?>%</span>
</div>
<!-- result log box with scroll -->
<h4 class="result-log--title"><?php esc_html_e( 'Logs', 're-beehiiv' ); ?></h4>
<div class="result-log">
	<div class="log" id="log">
		<div class="log-item">
			<span class="log-item__time">[<?php echo esc_html( current_time( 'H:i:s' ) ); ?>]</span>
			<span class="log-item__status log-item__status--running"><?php esc_html_e( 'Running', 're-beehiiv' ); ?></span>
			<span class="log-item__message"><?php esc_html_e( 'Please wait... We are fetching data from Beehiiv.', 're-beehiiv' ); ?></span>
		</div>
		<?php if ( ! empty( $logs ) ) : ?>
			<?php foreach ( $logs as $log ) :
				$time = explode( ' ', $log['time'] );
				?>
				<div class="log-item">
					<span class="log-item__time">[<?php echo esc_html( $time[1] ); ?>]</span>
					<span class="log-item__status log-item__status--<?php echo esc_attr( $log['status'] ); ?>"><?php echo esc_html( $log['status'] ); ?></span>
					<span class="log-item__message"><?php echo $log['message']; ?></span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
