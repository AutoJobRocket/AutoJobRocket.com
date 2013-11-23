<?php
/**
 * My Packages
 *
 * Shows packages on the account page
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<h2><?php echo apply_filters( 'woocommerce_my_account_job_packages_title', __( 'My job packages', 'job_manager_wcpl' ) ); ?></h2>

<table class="shop_table my_account_job_packages">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'Package Name', 'job_manager_wcpl' ); ?></th>
			<th scope="col"><?php _e( 'Jobs Remaining', 'job_manager_wcpl' ); ?></th>
			<th scope="col"><?php _e( 'Listing Duration', 'job_manager_wcpl' ); ?></th>
			<th scope="col"><?php _e( 'Featured?', 'job_manager_wcpl' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $packages as $package ) : ?>
			<tr>
				<td><?php
					$product = get_post( $package->product_id );
					if ( $product )
						echo $product->post_title;
					else
						echo '-';
				?></td>
				<td><?php echo absint( $package->job_limit - $package->job_count ); ?></td>
				<td><?php echo sprintf( _n( '%d day', '%d days', $package->job_duration, 'job_manager_wcpl' ), $package->job_duration ); ?></td>
				<td><?php if ( $package->job_featured ) _e( 'Yes', 'job_manager_wcpl' ); else _e( 'No', 'job_manager_wcpl' ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>