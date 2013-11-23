<?php
/**
 * Approve a job listing
 * @param  int $job_id
 * @param  int $user_id
 * @param  int $user_package_id
 */
function approve_paid_job_listing_with_package( $job_id, $user_id, $user_package_id ) {
	$update_job                = array();
	$update_job['ID']          = $job_id;
	$update_job['post_status'] = get_option( 'job_manager_submission_requires_approval' ) ? 'pending' : 'publish';
	wp_update_post( $update_job );
	increase_job_package_job_count( $user_id, $user_package_id );
}