<div class="options_group show_if_job_package">

	<?php woocommerce_wp_text_input( array( 'id' => '_job_listing_limit', 'label' => __( 'Job listing limit', 'job_manager_wcpl' ), 'description' => __( 'The number of job listings a user can post with this package. If more than 1, registration will be forced on checkout.', 'job_manager_wcpl' ), 'value' => max( get_post_meta( $post_id, '_job_listing_limit', true ), 1 ), 'placeholder' => 1, 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_text_input( array( 'id' => '_job_listing_duration', 'label' => __( 'Job listing duration', 'job_manager_wcpl' ), 'description' => __( 'The number of days that the job listing will be active.', 'job_manager_wcpl' ), 'value' => get_post_meta( $post_id, '_job_listing_duration', true ), 'placeholder' => get_option( 'job_manager_submission_duration' ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_checkbox( array( 'id' => '_job_listing_featured', 'label' => __( 'Feature job listings?', 'job_manager_wcpl' ), 'description' => __( 'Feature this job listing - it will be styled differently and sticky.', 'job_manager_wcpl' ), 'value' => get_post_meta( $post_id, '_job_listing_featured', true ) ) ); ?>

	<script type="text/javascript">
		jQuery('.pricing').addClass( 'show_if_job_package' );
	</script>

</div>