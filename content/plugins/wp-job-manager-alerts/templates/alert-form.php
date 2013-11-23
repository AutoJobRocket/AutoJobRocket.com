<form method="post" class="job-manager-form">
	<fieldset>
		<label for="alert_name"><?php _e( 'Alert Name', 'job_manager_alerts' ); ?></label>
		<div class="field">
			<input type="text" name="alert_name" value="<?php esc_attr_e( $alert_name ); ?>" id="alert_name" class="input-text" placeholder="<?php _e( 'Enter a name for your alert', 'job_manager_alerts' ); ?>" />
		</div>
	</fieldset>
	<fieldset>
		<label for="alert_keyword"><?php _e( 'Keyword', 'job_manager_alerts' ); ?></label>
		<div class="field">
			<input type="text" name="alert_keyword" value="<?php esc_attr_e( $alert_keyword ); ?>" id="alert_keyword" class="input-text" placeholder="<?php _e( 'Optionally add a keyword to match jobs against', 'job_manager_alerts' ); ?>" />
		</div>
	</fieldset>
	<fieldset>
		<label for="alert_location"><?php _e( 'Location', 'job_manager_alerts' ); ?></label>
		<div class="field">
			<input type="text" name="alert_location" value="<?php esc_attr_e( $alert_location ); ?>" id="alert_location" class="input-text" placeholder="<?php _e( 'Optionally define a location to search against', 'job_manager_alerts' ); ?>" />
		</div>
	</fieldset>
	<?php if ( get_option( 'job_manager_enable_categories' ) && wp_count_terms( 'job_listing_category' ) > 0 ) : ?>
		<fieldset>
			<label for="alert_cats"><?php _e( 'Categories', 'job_manager_alerts' ); ?></label>
			<div class="field">
				<select name="alert_cats[]" data-placeholder="<?php _e( 'Any category', 'job_manager_alerts' ); ?>" id="alert_cats" multiple="multiple" class="job-manager-chosen-select">
					<?php
						$terms = get_job_listing_categories();
						foreach ( $terms as $term )
							echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( in_array( $term->slug, $alert_cats ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
					?>
				</select>
			</div>
		</fieldset>
	<?php endif; ?>
	<fieldset>
		<label for="alert_job_type"><?php _e( 'Job Type', 'job_manager_alerts' ); ?></label>
		<div class="field">
			<select name="alert_job_type[]" data-placeholder="<?php _e( 'Any job type', 'job_manager_alerts' ); ?>" id="alert_job_type" multiple="multiple" class="job-manager-chosen-select">
				<?php
					$terms = get_job_listing_types();
					foreach ( $terms as $term )
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( in_array( $term->slug, $alert_job_type ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
				?>
			</select>
		</div>
	</fieldset>
	<fieldset>
		<label for="alert_frequency"><?php _e( 'Email Frequency', 'job_manager_alerts' ); ?></label>
		<div class="field">
			<select name="alert_frequency" id="alert_frequency">
				<option value="daily" <?php selected( $alert_frequency, 'daily' ); ?>><?php _e( 'Daily', 'job_manager_alerts' ); ?></option>
				<option value="weekly" <?php selected( $alert_frequency, 'weekly' ); ?>><?php _e( 'Weekly', 'job_manager_alerts' ); ?></option>
				<option value="fortnightly" <?php selected( $alert_frequency, 'fortnightly' ); ?>><?php _e( 'Fortnightly', 'job_manager_alerts' ); ?></option>
			</select>
		</div>
	</fieldset>
	<p>
		<?php wp_nonce_field( 'job_manager_alert_actions' ); ?>
		<input type="hidden" name="alert_id" value="<?php echo absint( $alert_id ); ?>" />
		<input type="submit" name="submit-job-alert" value="Save alert" />
	</p>
</form>