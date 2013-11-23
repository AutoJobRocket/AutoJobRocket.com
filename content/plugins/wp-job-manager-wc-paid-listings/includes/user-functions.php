<?php
/**
 * Get a users packages from the DB
 * @param  int $user_id
 * @return array of objects
 */
function get_user_job_packages( $user_id ) {
	global $wpdb;

	$packages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}user_job_packages WHERE user_id = %d AND job_count < job_limit;", $user_id ), OBJECT_K );

	return $packages;
}

/**
 * Get a package
 * @param  int $package_id
 * @return object
 */
function get_user_job_package( $package_id ) {
	global $wpdb;

	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}user_job_packages WHERE id = %d;", $package_id ) );
}

/**
 * Give a user a package
 * @param  int $user_id
 * @param  int $product_id
 * @return int|bool false
 */
function give_user_job_package( $user_id, $product_id ) {
	global $wpdb;

	$package = get_product( $product_id );

	if ( ! $package->is_type( 'job_package' ) )
		return false;

	$wpdb->insert(
		"{$wpdb->prefix}user_job_packages",
		array(
			'user_id'      => $user_id,
			'product_id'   => $product_id,
			'job_count'    => 0,
			'job_duration' => $package->get_duration(),
			'job_limit'    => $package->get_limit(),
			'job_featured' => ( $package->job_listing_featured == 'yes' ? 1 : 0 )
		),
		array(
			'%d',
			'%d',
			'%d',
			'%d',
			'%d',
			'%d'
		)
	);

	return $wpdb->insert_id;
}

/**
 * Increase job count for package
 * @param  int $user_id
 * @param  int $package_id
 * @return int affected rows
 */
function increase_job_package_job_count( $user_id, $package_id ) {
	global $wpdb;

	$packages = get_user_job_packages( $user_id );

	if ( isset( $packages[ $package_id ] ) ) {
		$new_count = $packages[ $package_id ]->job_count + 1;
	} else {
		$new_count = 1;
	}

	return $wpdb->update(
		"{$wpdb->prefix}user_job_packages",
		array(
			'job_count' => $new_count,
		),
		array(
			'user_id' => $user_id,
			'id'      => $package_id
		),
		array( '%d' ),
		array( '%d', '%d' )
	);

	return false;
}

/**
 * See if a package is valid for use
 * @return bool
 */
function user_job_package_is_valid( $user_id, $package_id ) {
	global $wpdb;

	$package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}user_job_packages WHERE user_id = %d AND id = %d;", $user_id, $package_id ) );

	if ( ! $package )
		return false;

	if ( $package->job_count >= $package->job_limit )
		return false;

	return true;
}