<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Orders
 */
class WP_Job_Manager_WCPL_Forms {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		add_filter( 'submit_job_steps', array( $this, 'submit_job_steps' ), 10 );
		add_filter( 'submit_job_step_preview_submit_text', array( $this, 'submit_button_text' ), 10 );
	}

	/**
	 * Add form styles
	 */
	public function styles() {
		wp_enqueue_style( 'wc-paid-listings-packages', JOB_MANAGER_WCPL_PLUGIN_URL . '/assets/css/packages.css' );
	}

	/**
	 * Change the steps during the submission process
	 *
	 * @param  array $steps
	 * @return array
	 */
	public function submit_job_steps( $steps ) {
		// We need to hijack the preview submission so we can take a payment
		$steps['preview']['handler'] = array( $this, 'preview_handler' );

		// Add the payment step
		$steps['wc-pay'] = array(
			'name'     => __( 'Choose a package', 'job_manager_wcpl' ),
			'view'     => array( __CLASS__, 'choose_package' ),
			'handler'  => array( __CLASS__, 'choose_package_handler' ),
			'priority' => 25
		);

		return $steps;
	}

	/**
	 * [choose_package description]
	 * @return [type]
	 */
	public static function choose_package() {
		// get job listing packages
		$packages = get_posts( array(
			'post_type'  => 'product',
			'limit'      => -1,
			'tax_query'  => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'job_package'
				)
			)
		) );
		$user_packages = get_user_job_packages( get_current_user_id() );
		?>
		<form method="post" id="job_package_selection">
			<div class="job_listing_packages_title">
				<input type="submit" name="continue" class="button" value="<?php echo apply_filters( 'submit_job_step_choose_package_submit_text', __( 'Submit &rarr;', 'job_manager_wcpl' ) ); ?>" />
				<input type="hidden" name="job_id" value="<?php echo esc_attr( WP_Job_Manager_Form_Submit_Job::get_job_id() ); ?>" />
				<input type="hidden" name="step" value="<?php echo esc_attr( WP_Job_Manager_Form_Submit_Job::get_step() ); ?>" />
				<input type="hidden" name="job_manager_form" value="<?php echo WP_Job_Manager_Form_Submit_Job::$form_name; ?>" />
				<h2><?php _e( 'Choose a package', 'job_manager_wcpl' ); ?></h2>
			</div>
			<div class="job_listing_packages">
				<?php get_job_manager_template( 'package-selection.php', array( 'packages' => $packages, 'user_packages' => $user_packages ), 'job_manager_wc_paid_listings', JOB_MANAGER_WCPL_PLUGIN_DIR . '/templates/' ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Change submit button text
	 * @return string
	 */
	public function submit_button_text() {
		return __( 'Choose a package &rarr;', 'job_manager_wcpl' );
	}

	/**
	 * [choose_package_handler description]
	 * @return [type]
	 */
	public static function choose_package_handler() {
		global $woocommerce;

		// Get and validate package
		if ( is_numeric( $_POST['job_package'] ) )
			$job_package_id = absint( $_POST['job_package'] );
		else
			$user_job_package_id = absint( substr( $_POST['job_package'], 5 ) );

		// Get job ID
		$job_id = WP_Job_Manager_Form_Submit_Job::get_job_id();

		if ( ! empty( $job_package_id ) ) {
			$job_package = get_product( $job_package_id );

			if ( ! $job_package->is_type( 'job_package' ) ) {
				WP_Job_Manager_Form_Submit_Job::add_error( __( 'Invalid Package', 'job_manager_wcpl' ) );
				return;
			}

			// Give job the package attributes
			update_post_meta( $job_id, '_job_duration', $job_package->get_duration() );
			update_post_meta( $job_id, '_featured', $job_package->job_listing_featured == 'yes' ? 1 : 0 );

			// Add package to the cart
			$woocommerce->cart->add_to_cart( $job_package_id, 1, '', '', array(
				'job_id' => $job_id
			) );

			woocommerce_add_to_cart_message( $job_package_id );

			// Redirect to checkout page
			wp_redirect( get_permalink( woocommerce_get_page_id( 'checkout' ) ) );
			exit;
		} elseif ( $user_job_package_id && user_job_package_is_valid( get_current_user_id(), $user_job_package_id ) ) {
			$job = get_post( $job_id );

			// Give job the package attributes
			$job_package = get_user_job_package( $user_job_package_id );
			update_post_meta( $job_id, '_job_duration', $job_package->job_duration );
			update_post_meta( $job_id, '_featured', $job_package->job_featured );

			// Approve the job
			if ( $job->post_status == 'pending_payment' ) {
				approve_paid_job_listing_with_package( $job->ID, get_current_user_id(), $user_job_package_id );
			}

			WP_Job_Manager_Form_Submit_Job::next_step();
		} else {
			WP_Job_Manager_Form_Submit_Job::add_error( __( 'Invalid Package', 'job_manager_wcpl' ) );
			return;
		}
	}

	/**
	 * Handle the form when the preview page is submitted
	 */
	public function preview_handler() {
		if ( ! $_POST )
			return;

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_job'] ) ) {
			WP_Job_Manager_Form_Submit_Job::previous_step();
		}

		// Continue = Take Payment
		if ( ! empty( $_POST['continue'] ) ) {

			$job = get_post( WP_Job_Manager_Form_Submit_Job::get_job_id() );

			if ( $job->post_status == 'preview' ) {
				$update_job                = array();
				$update_job['ID']          = $job->ID;
				$update_job['post_status'] = 'pending_payment';
				wp_update_post( $update_job );
			}

			WP_Job_Manager_Form_Submit_Job::next_step();
		}
	}
}

new WP_Job_Manager_WCPL_Forms();