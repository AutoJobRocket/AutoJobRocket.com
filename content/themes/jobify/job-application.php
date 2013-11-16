<?php if ( $apply = get_the_job_application_method() ) :
	?>
	<div class="application">
		<input class="application_button" type="button" value="<?php esc_attr_e( 'Apply', 'jobify' ); ?>" />

		<div class="application_details animated fadeIn modal">
			<h2 class="modal-title"><?php _e( 'Apply', 'jobify' ); ?></h2>

			<div class="application-content">
			<?php
				switch ( $apply->type ) {
					case 'email' :

						if ( class_exists( 'Astoundify_Job_Manager_Apply' ) ) :
							echo do_shortcode( '[gravityform id="' . get_option( 'job_manager_gravity_form' ) . '" title="false" ajax="true"]' );
						else : 
							echo '<p>' . sprintf( __( 'To apply for this job <strong>email your details to</strong> <a class="job_application_email" href="mailto:%1$s%2$s">%1$s</a>', 'jobify' ), $apply->email, '?subject=' . rawurlencode( $apply->subject ) ) . '</p>';
						endif;

					break;
					case 'url' :
						echo '<p>' . sprintf( __( 'To apply for this job please visit the following URL: <a href="%1$s">%1$s &rarr;</a>', 'jobify' ), $apply->url ) . '</p>';
					break;
				}
			?>
			</div>
		</div>
	</div>
<?php endif; ?>