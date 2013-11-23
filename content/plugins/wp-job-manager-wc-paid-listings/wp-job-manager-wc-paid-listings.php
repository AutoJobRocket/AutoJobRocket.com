<?php
/*
Plugin Name: WP Job Manager - WooCommerce Paid Listings
Plugin URI: http://mikejolley.com
Description: Add paid listing functionality via WooCommerce. Create 'job packages' as products with their own price, listing duration, listing limit, and job featured status and either sell them via your store or during the job submission process. A user's packages are shown on their account page and can be used to post future jobs if they allow more than 1 job listing. Requires Job Manager 1.2+
Version: 1.0.3
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.5
Tested up to: 3.5

	Copyright: 2013 Mike Jolley
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'MJ_Updater' ) )
	include( 'includes/updater/class-mj-updater.php' );

/**
 * Init the plugin when all plugins are loaded
 */
function wp_job_manager_wcpl_init() {

	if ( ! class_exists( 'WooCommerce' ) )
		return;

	/**
	 * WP_Job_Manager_WCPL class.
	 */
	class WP_Job_Manager_WCPL extends MJ_Updater {

		/**
		 * Constructor
		 */
		public function __construct() {
			// Define constants
			define( 'JOB_MANAGER_WCPL_VERSION', '1.0.3' );
			define( 'JOB_MANAGER_WCPL_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'JOB_MANAGER_WCPL_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
			define( 'JOB_MANAGER_WCPL_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

			// Hooks
			add_action( 'init', array( $this, 'init' ), 12 );
			add_filter( 'the_job_status', array( $this, 'the_job_status' ), 10, 2 );
			add_filter( 'job_manager_valid_submit_job_statuses', array( $this, 'valid_submit_job_statuses' ) );

			// Includes
			include_once( 'includes/class-wc-product-job-package.php' );
			include_once( 'includes/class-wp-job-manager-wcpl-admin.php' );
			include_once( 'includes/class-wp-job-manager-wcpl-cart.php' );
			include_once( 'includes/class-wp-job-manager-wcpl-orders.php' );
			include_once( 'includes/class-wp-job-manager-wcpl-forms.php' );
			include_once( 'includes/user-functions.php' );
			include_once( 'includes/job-functions.php' );

			// Updater
			$this->init_updates( __FILE__ );
		}

		/**
		 * Localisation
		 */
		public function init() {
			load_plugin_textdomain( 'job_manager_wcpl', false, dirname( plugin_basename( __FILE__ ) ) );

			register_post_status( 'pending_payment', array(
				'label'                     => _x( 'Pending Payment', 'job_listing', 'job_manager_wcpl' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'job_manager_wcpl' ),
			) );
		}

		/**
		 * Filter job status name
		 *
		 * @param  string $nice_status
		 * @param  string $status
		 * @return string
		 */
		public function the_job_status( $status, $job ) {
			if ( $job->post_status == 'pending_payment' )
				$status = __( 'Pending Payment', 'job_manager_wcpl' );
			return $status;
		}

		/**
		 * Ensure the submit form lets us continue to edit/process a job with the pending_payment status
		 * @return array
		 */
		public function valid_submit_job_statuses( $status ) {
			$status[] = 'pending_payment';
			return $status;
		}
	}

	new WP_Job_Manager_WCPL();
}

add_action( 'plugins_loaded', 'wp_job_manager_wcpl_init' );

/**
 * Install the plugin
 */
function wp_job_manager_wcpl_install() {
	global $wpdb;

	$wpdb->hide_errors();

	$collate = '';
    if ( $wpdb->has_cap( 'collation' ) ) {
		if( ! empty($wpdb->charset ) )
			$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
		if( ! empty($wpdb->collate ) )
			$collate .= " COLLATE $wpdb->collate";
    }

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    /**
     * Table for user job packs
     */
    $sql = "
CREATE TABLE {$wpdb->prefix}user_job_packages (
  id bigint(20) NOT NULL auto_increment,
  user_id bigint(20) NOT NULL,
  product_id bigint(20) NOT NULL,
  job_duration bigint(20) NOT NULL,
  job_featured int(1) NOT NULL,
  job_limit bigint(20) NOT NULL,
  job_count bigint(20) NOT NULL,
  PRIMARY KEY  (id)
) $collate;
";
    dbDelta($sql);
}

register_activation_hook( __FILE__, 'wp_job_manager_wcpl_install' );