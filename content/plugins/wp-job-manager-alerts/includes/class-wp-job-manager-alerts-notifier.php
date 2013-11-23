<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Job_Manager_Alerts_Notifier class.
 */
class WP_Job_Manager_Alerts_Notifier {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'job-manager-alert', array( $this, 'job_manager_alert' ) );
	}

	/**
	 * Send an alert
	 */
	public function job_manager_alert( $alert_id ) {
		$alert = get_post( $alert_id );

		if ( ! $alert || $alert->post_status !== 'publish' || $alert->post_type !== 'job_alert' )
			return;

		$user  = get_user_by( 'id', $alert->post_author );
		$jobs  = $this->get_matching_jobs( $alert );

		if ( $jobs->found_posts || get_option( 'job_manager_alerts_matches_only' ) == 'no' ) {

			$email = $this->format_email( $alert, $user, $jobs );

			add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );

			if ( $email )
				wp_mail( $user->user_email, apply_filters( 'job_manager_alerts_subject', sprintf( __( 'Job Alert Results Matching "%s"', 'job_manager_alerts' ), $alert->post_title ), $alert ), $email );

			remove_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );
		}

		if ( $days_to_disable = get_option( 'job_manager_alerts_auto_disable' ) > 0 ) {
			$days = ( strtotime( 'NOW' ) - strtotime( $alert->post_modified ) ) / ( 60 * 60 * 24 );

			if ( $days > $days_to_disable ) {
				$update_alert = array();
				$update_alert['ID'] = $alert->ID;
				$update_alert['post_status'] = 'draft';
				wp_update_post( $update_alert );
				return;
			}
		}

		wp_clear_scheduled_hook( 'job-manager-alert', array( $alert->ID ) );

		// Reschedule next alert
		switch ( $alert->alert_frequency ) {
			case 'daily' :
				$next = strtotime( '+1 day' );
			break;
			case 'fortnightly' :
				$next = strtotime( '+2 week' );
			break;
			default :
				$next = strtotime( '+1 week' );
			break;
		}

		// Create cron
		wp_schedule_single_event( $next, 'job-manager-alert', array( $alert->ID ) );

		// Inc sent count
		update_post_meta( $alert->ID, 'send_count', 1 + absint( get_post_meta( $alert->ID, 'send_count', true ) ) );
	}

	/**
	 * Match jobs to an alert
	 */
	public function get_matching_jobs( $alert ) {
		if ( method_exists( $this, 'filter_ ' . $alert->alert_frequency ) )
			add_filter( 'posts_where', array( $this, 'filter_ ' . $alert->alert_frequency ) );

		$cats  = array_filter( (array) wp_get_post_terms( $alert->ID, 'job_listing_category', array( 'fields' => 'slugs' ) ) );
		$types = array_filter( (array) wp_get_post_terms( $alert->ID, 'job_listing_type', array( 'fields' => 'slugs' ) ) );

		$jobs = get_job_listings( array(
			'search_location'   => $alert->alert_location,
			'search_keywords'   => $alert->alert_keyword,
			'search_categories' => sizeof( $cats ) > 0 ? $cats : '',
			'job_types'         => sizeof( $types ) > 0 ? $types : '',
			'orderby'           => 'date',
			'order'             => 'desc',
			'offset'            => 0,
			'posts_per_page'    => 50
		) );

		if ( method_exists( $this, 'filter_ ' . $alert->alert_frequency ) )
			remove_filter( 'posts_where', array( $this, 'filter_ ' . $alert->alert_frequency ) );

		return $jobs;
	}

	/**
	 * Filter posts from the last day
	 */
	public function filter_daily( $where = '' ) {
		$where .= " AND post_date >= '" . date( 'Y-m-d', strtotime( '-1 days' ) ) . "'";
		return $where;
	}

	/**
	 * Filter posts from the last week
	 */
	public function filter_weekly( $where = '' ) {
		$where .= " AND post_date >= '" . date( 'Y-m-d', strtotime( '-1 week' ) ) . "'";
		return $where;
	}

	/**
	 * Filter posts from the last 2 weeks
	 */
	public function filter_fortnightly( $where = '' ) {
		$where .= " AND post_date >= '" . date( 'Y-m-d', strtotime( '-2 weeks' ) ) . "'";
		return $where;
	}

	/**
	 * Format the email
	 */
	public function format_email( $alert, $user, $jobs ) {

		$template = get_option( 'job_manager_alerts_email_template' );

		if ( ! $template ) {
			$template = $GLOBALS['job_manager_alerts']->get_default_email();
		}

		if ( $jobs && $jobs->have_posts() ) {
			ob_start();

			while ( $jobs->have_posts() ) {
				$jobs->the_post();

				get_job_manager_template( 'content-email_job_listing.php', array(), 'job_manager_alerts', JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/' );
			}
			$job_content = ob_get_clean();
		} else {
			$job_content = __( 'No jobs were found matching your search. Login to your account to change your alert criteria', 'job_manager_alerts' );
		}

		// Reschedule next alert
		switch ( $alert->alert_frequency ) {
			case 'daily' :
				$next = strtotime( '+1 day' );
			break;
			case 'fortnightly' :
				$next = strtotime( '+2 week' );
			break;
			default :
				$next = strtotime( '+1 week' );
			break;
		}

		if ( get_option( 'job_manager_alerts_auto_disable' ) > 0 ) {
			$alert_expirey = sprintf( __( 'This job alert will automatically stop sending after %s.', 'job_manager_alerts' ), date_i18n( get_option( 'date_format' ), strtotime( '+' . absint( get_option( 'job_manager_alerts_auto_disable' ) ) . ' days', strtotime( $alert->post_modified ) ) ) );
		} else {
			$alert_expirey = '';
		}

		$replacements = array(
			'{display_name}'    => $user->display_name,
			'{alert_name}'      => $alert->post_title,
			'{alert_expirey}'   => $alert_expirey,
			'{alert_next_date}' => date_i18n( get_option( 'date_format' ), $next ),
			'{alert_page_url}'  => get_permalink( get_page_by_path( get_option( 'job_manager_alerts_page_slug' ) )->ID ),
			'{jobs}'            => $job_content
		);

		$template = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		return apply_filters( 'job_manager_alerts_template', $template );
	}

	/**
	 * From name
	 */
	public function mail_from_name( $name ) {
	    return get_bloginfo( 'name' );
	}
}

new WP_Job_Manager_Alerts_Notifier();