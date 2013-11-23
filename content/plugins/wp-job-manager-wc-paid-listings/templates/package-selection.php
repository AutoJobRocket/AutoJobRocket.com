<?php if ( $packages || $user_packages ) : ?>
	<ul class="job_packages">
		<?php foreach ( $packages as $key => $package ) : $product = get_product( $package ); ?>

			<li>
				<input type="radio" <?php checked( $key, 0 ); ?> name="job_package" value="<?php echo $product->id; ?>" id="package-<?php echo $product->id; ?>" />
				<label for="package-<?php echo $product->id; ?>"><?php echo $product->get_title(); ?></label><br/>
				<?php
					printf( _n( '%s for %d job', '%s for %s jobs', $product->get_limit(), 'job_manager_wcpl' ) . ' ', $product->get_price_html(), $product->get_limit() );

					printf( _n( 'listed for %s day', 'listed for %s days', $product->get_duration(), 'job_manager_wcpl' ), $product->get_duration() );
				?>
			</li>

		<?php endforeach; ?>

		<?php foreach ( $user_packages as $key => $package ) : $product = get_product( $package->product_id ); ?>

			<li>
				<input type="radio" name="job_package" value="user-<?php echo $key; ?>" id="user-package-<?php echo $package->id; ?>" />
				<label for="user-package-<?php echo $package->id; ?>"><?php if ( $product ) echo $product->get_title(); else echo '-'; ?></label><br/>
				<?php
					printf( _n( '%s job posted out of %d', '%s jobs posted out of %s', $package->job_count, 'job_manager_wcpl' ) . ', ', $package->job_count, $package->job_limit );

					printf( _n( 'listed for %s day', 'listed for %s days', $package->job_duration, 'job_manager_wcpl' ), $package->job_duration );
				?>
			</li>

		<?php endforeach; ?>
	</ul>
<?php else : ?>

	<p><?php _e( 'No packages found', 'job_manager_wcpl' ); ?></p>

<?php endif; ?>