<?php
/**
 * Progress Bar Component
 *
 * @package Integration_Toolkit_For_Beehiiv
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! isset( $complete_items ) ) {
	$percentage = 0;
} elseif ( $complete_items === 0 || $total_items === 0 ) {
	$percentage = 0;
} else {
	$percentage  = ( $complete_items / $total_items ) * 80;
	$percentage  = number_format( (float) $percentage, 2, '.', '' );
	$percentage += 20; // Add 5 to the calculated percentage because the data fetching takes some time.
}

?>
<div class="status-info">
	<div class="bar-wrap">
		<div class="bar" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
	</div>
	<span class="percentage"><?php echo esc_html( $percentage ); ?>%</span>
</div>
<!-- result log box with scroll -->
<h4 class="result-log--title"><?php esc_html_e( 'Logs', 'integration-toolkit-for-beehiiv' ); ?></h4>
<div class="result-log">
	<div class="log" id="log">
		<div class="log-item">
			<span class="log-item__time">[<?php echo esc_html( current_time( 'H:i:s' ) ); ?>]</span>
			<span class="log-item__status log-item__status--running"><?php esc_html_e( 'Running', 'integration-toolkit-for-beehiiv' ); ?></span>
			<span class="log-item__message"><?php esc_html_e( 'Please wait... We are fetching data from.', 'integration-toolkit-for-beehiiv' ); ?></span>
		</div>
		<?php
		if ( ! empty( $logs ) ) :
			foreach ( $logs as $log ) :
				$time = explode( ' ', $log['time'] );
				?>
				<div class="log-item">
					<span class="log-item__time">
						[
							<?php
								printf(
									/* Translators: %s is a placeholder for the time. This text is displayed when there is an error in the form data. */
									esc_html__( 'Time : %s', 'Integration Toolkit for beehiiv' ),
									esc_html( $time[1] )
								);
							?>
						]
					</span>
					<span class="log-item__status log-item__status--<?php echo esc_attr( $log['status'] ); ?>">
					<?php
						printf(
							/* Translators: %s is a placeholder for the status. This text is displayed when there is an error in the form data. */
							esc_html__( 'Status: %s', 'Integration Toolkit for beehiiv' ),
							esc_html( $log['status'] )
						);
					?>
				</span>
					<span class="log-item__message">
						<?php
							printf(
								/* Translators: %s is a placeholder for the message. This text is displayed when there is an error in the form data. */
								esc_html__( 'Error: %s', 'Integration Toolkit for beehiiv' ),
								esc_html( $log['message'] )
							);
						?>
					</span>
				</div>
				<?php
			endforeach;
		endif;
		?>
	</div>
</div>
