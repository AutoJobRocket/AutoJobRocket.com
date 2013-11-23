<div id="job-manager-alerts">
	<p><?php printf( __( 'Your job alerts are shown in the table below. Your alerts will be sent to %s.', 'job_manager_alerts' ), $user->user_email ); ?></p>
	<table class="job-manager-alerts">
		<thead>
			<tr>
				<th><?php _e( 'Alert Name', 'job_manager_alerts' ); ?></th>
				<th><?php _e( 'Date Created', 'job_manager_alerts' ); ?></th>
				<th><?php _e( 'Keywords', 'job_manager_alerts' ); ?></th>
				<th><?php _e( 'Location', 'job_manager_alerts' ); ?></th>
				<th><?php _e( 'Frequency', 'job_manager_alerts' ); ?></th>
				<th><?php _e( 'Status', 'job_manager_alerts' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<a href="<?php echo remove_query_arg( 'updated', add_query_arg( 'action', 'add_alert' ) ); ?>"><?php _e( 'Add alert', 'job_manager_alerts' ); ?></a>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ( $alerts as $alert ) : ?>
				<tr>
					<td>
						<?php echo esc_html( $alert->post_title ); ?>
						<ul class="job-alert-actions">
							<?php
								$actions = apply_filters( 'job_manager_alert_actions', array(
									'view' => array(
										'label' => __( 'Show Results', 'job_manager_alerts' ),
										'nonce' => false
									),
									'edit' => array(
										'label' => __( 'Edit', 'job_manager_alerts' ),
										'nonce' => false
									),
									'toggle_status' => array(
										'label' => $alert->post_status == 'draft' ? __( 'Enable', 'job_manager_alerts' ) : __( 'Disable', 'job_manager_alerts' ),
										'nonce' => true
									),
									'delete' => array(
										'label' => __( 'Delete', 'job_manager_alerts' ),
										'nonce' => true
									)
								), $alert );

								foreach ( $actions as $action => $value ) {
									$action_url = remove_query_arg( 'updated', add_query_arg( array( 'action' => $action, 'alert_id' => $alert->ID ) ) );

									if ( $value['nonce'] )
										$action_url = wp_nonce_url( $action_url, 'job_manager_alert_actions' );

									echo '<li><a href="' . $action_url . '" class="job-alerts-action-' . $action . '">' . $value['label'] . '</a></li>';
								}
							?>
						</ul>
					</td>
					<td class="date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $alert->post_date ) ); ?></td>
					<td class="alert_keyword"><?php
						if ( $value = get_post_meta( $alert->ID, 'alert_keyword', true ) )
							echo esc_html( '&ldquo;' . $value . '&rdquo;' );
						else
							echo '&ndash;';
					?></td>
					<td class="alert_location"><?php
						if ( $value = get_post_meta( $alert->ID, 'alert_location', true ) )
							echo esc_html( '&ldquo;' . $value . '&rdquo;' );
						else
							echo '&ndash;';
					?></td>
					<td class="alert_frequency"><?php
						switch ( $freq = get_post_meta( $alert->ID, 'alert_frequency', true ) ) {
							case "daily" :
								_e( 'Daily', 'job_manager_alerts' );
							break;
							case "weekly" :
								_e( 'Weekly', 'job_manager_alerts' );
							break;
							case "fornightly" :
								_e( 'Fornightly', 'job_manager_alerts' );
							break;
						}
					?></td>
					<td class="status"><?php echo $alert->post_status == 'draft' ? __( 'Disabled', 'job_manager_alerts' ) : __( 'Enabled', 'job_manager_alerts' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>