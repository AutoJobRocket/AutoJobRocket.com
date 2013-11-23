<?php
/*
Plugin Name: WP Job Manager - Job Tags
Plugin URI: http://mikejolley.com
Description: Adds tags to Job Manager for tagging jobs with requried Skills and Technologies. Also adds some extra shortcodes. Requires Job Manager 1.0.4+
Version: 1.0.2
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
 * WP_Job_Manager_Job_Tags class.
 */
class WP_Job_Manager_Job_Tags extends MJ_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {
		define( 'JOB_MANAGER_TAGS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_filter( 'submit_job_form_fields', array( $this, 'job_tag_field' ) );
		add_filter( 'submit_job_form_validate_fields', array( $this, 'validate_job_tag_field' ), 10, 3 );
		add_action( 'job_manager_update_job_data', array( $this, 'save_job_tag_field' ), 10, 2 );
		add_action( 'submit_job_form_fields_get_job_data', array( $this, 'get_job_tag_field_data' ), 10, 2 );
		add_filter( 'the_job_description', array( $this, 'display_tags' ) );

		// Feeds
		add_filter( 'job_feed_args', array( $this, 'job_feed_args' ) );

		// Add column to admin
		add_filter( 'manage_edit-job_listing_columns', array( $this, 'columns' ), 20 );
		add_action( 'manage_job_listing_posts_custom_column', array( $this, 'custom_columns' ), 2 );

		// Includes
		include( 'includes/class-job-manager-job-tags-shortcodes.php' );

		$this->init_updates( __FILE__ );
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'job_manager_tags', false, dirname( plugin_basename( __FILE__ ) ) );
	}

	/**
	 * register_post_types function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_taxonomy() {

		if ( taxonomy_exists( "job_listing_tag" ) )
			return;

		$singular = __( 'Job Tag', 'job_manager_tags' );
		$plural   = __( 'Job Tags', 'job_manager_tags' );
		$admin_capability = 'manage_job_listings';

		register_taxonomy( "job_listing_tag",
	        array( "job_listing" ),
	        array(
	            'hierarchical' 			=> false,
	            'update_count_callback' => '_update_post_term_count',
	            'label' 				=> $plural,
	            'labels' => array(
                    'name' 				=> $plural,
                    'singular_name' 	=> $singular,
                    'search_items' 		=> sprintf( __( 'Search %s', 'job_manager' ), $plural ),
                    'all_items' 		=> sprintf( __( 'All %s', 'job_manager' ), $plural ),
                    'parent_item' 		=> sprintf( __( 'Parent %s', 'job_manager' ), $singular ),
                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'job_manager' ), $singular ),
                    'edit_item' 		=> sprintf( __( 'Edit %s', 'job_manager' ), $singular ),
                    'update_item' 		=> sprintf( __( 'Update %s', 'job_manager' ), $singular ),
                    'add_new_item' 		=> sprintf( __( 'Add New %s', 'job_manager' ), $singular ),
                    'new_item_name' 	=> sprintf( __( 'New %s Name', 'job_manager' ),  $singular )
            	),
	            'show_ui' 				=> true,
	            'query_var' 			=> apply_filters( 'enable_job_tag_archives', get_option( 'job_manager_enable_tag_archive' ) ),
	            'capabilities'			=> array(
	            	'manage_terms' 		=> $admin_capability,
	            	'edit_terms' 		=> $admin_capability,
	            	'delete_terms' 		=> $admin_capability,
	            	'assign_terms' 		=> $admin_capability,
	            ),
	            'rewrite' 				=> array( 'slug' => _x( 'job-tag', 'permalink', 'job_manager_tags' ), 'with_front' => false ),
	        )
	    );
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['job_submission'][1][] = array(
			'name' 		=> 'job_manager_max_tags',
			'std' 		=> '',
			'label' 	=> __( 'Maximum Job Tags', 'job_manager_spl' ),
			'desc'		=> __( 'Enter the number of tags per job submission you wish to allow, or leave blank for unlimited tags.', 'job_manager_tags' ),
			'type'      => 'input'
		);
		$settings['job_listings'][1][] = array(
			'name' 		=> 'job_manager_enable_tag_archive',
			'std' 		=> '',
			'label' 	=> __( 'Tag Archives', 'job_manager_spl' ),
			'cb_label'  => __( 'Enable Tag Archives', 'job_manager_spl' ),
			'desc'		=> __( 'Enabling tag archives will make job tags (inside jobs and tag clouds) link through to an archive of all jobs with said tag. Please note, tag archives will look like your post archives unless you create a special template to handle the display of job listings called <code>taxonomy-job_listing_tag.php</code> inside your theme. See <a href="http://codex.wordpress.org/Template_Hierarchy#Custom_Taxonomies_display">Template Hierarchy</a> for more information.', 'job_manager_tags' ),
			'type'      => 'checkbox'
		);

		return $settings;
	}

	/**
	 * Add the job tag field to the submission form
	 * @return array
	 */
	public function job_tag_field( $fields ) {

		if ( $max = get_option( 'job_manager_max_tags' ) )
			$max = ' ' . sprintf( __( 'Maximum of %d.', 'job_manager_spl' ), $max );

		$fields['job']['job_tags'] = array(
			'label'       => __( 'Job tags', 'job_manager_tags' ),
			'description' => __( 'Comma separate tags, such as required skills or technologies, for this job.', 'job_manager_tags' ) . $max,
			'type'        => 'text',
			'required'    => false,
			'placeholder' => __( 'e.g. PHP, Social Media, Management', 'job_manager_tags' ),
			'priority'    => "4.5"
		);

		return $fields;
	}

	/**
	 * validate fields
	 * @param  bool $passed
	 * @param  array $fields
	 * @param  array $values
	 * @return bool on success, wp_error on failure
	 */
	public function validate_job_tag_field( $passed, $fields, $values ) {
		$max  = get_option( 'job_manager_max_tags' );
		$tags = array_filter( explode( ',', $values['job']['job_tags'] ) );

		if ( $max && sizeof( $tags ) > $max )
			return new WP_Error( 'validation-error', sprintf( __( 'Please enter no more than %d tags.', 'job_manager' ), $max ) );

		return $passed;
	}

	/**
	 * Save posted tags to the job
	 */
	public function save_job_tag_field( $job_id, $values ) {

		// Get posted tags field
		$raw_tags = $values['job']['job_tags'];

		// Explode and clean
		$raw_tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );

		if ( empty( $raw_tags ) )
			return;

		// Loop tags we want to set and put them into an array
		$tags = array();

		foreach ( $raw_tags as $tag ) {
			// We'll assume that small tags less than or equal to 3 chars are abbreviated. Uppercase them.
			if ( strlen( $tag ) <= 3 ) {
				$tags[] = strtoupper( $tag );
			} else {
				$tags[] = strtolower( $tag );
			}
		}

		wp_set_object_terms( $job_id, $tags, 'job_listing_tag', false );
	}

	/**
	 * Get Job Tags for the field when editing
	 * @param  object $job
	 * @param  class $form
	 */
	public function get_job_tag_field_data( $data, $job ) {
		$data[ 'job' ][ 'job_tags' ]['value'] = implode( ', ', wp_get_object_terms( $job->ID, 'job_listing_tag', array( 'fields' => 'names' ) ) );

		return $data;
	}

	/**
	 * Show tags on job pages
	 * @return string
	 */
	public function display_tags( $content ) {
		global $post;

		if ( $terms = $this->get_job_tag_list( $post->ID ) ) {
			$content .= '<p class="job_tags">' . __( 'Tagged as:' ) . ' ' . $terms . '</p>';
		}

		return $content;
	}

	/**
	 * Add a job tag column to admin
	 * @return array
	 */
	public function columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( $key == 'job_listing_category' )
				$new_columns['job_tags'] = __( 'Tags', 'job_manager_tags' );

			$new_columns[ $key ] = $value;
		}

		return $new_columns;
	}

	/**
	 * Handle display of new column
	 * @param  string $column
	 */
	public function custom_columns( $column ) {
		global $post;

		if ( $column == 'job_tags' ) {
			if ( ! $terms = $this->get_job_tag_list( $post->ID ) )
				echo '<span class="na">&ndash;</span>';
			else
				echo $terms;
		}
	}

	/**
	 * Gets a formatted list of job tags for a post ID
	 * @return string
	 */
	public function get_job_tag_list( $job_id ) {
		$terms = get_the_term_list( $job_id, 'job_listing_tag', '', ', ', '' );

		if ( ! apply_filters( 'enable_job_tag_archives', get_option( 'job_manager_enable_tag_archive' ) ) )
			$terms = strip_tags( $terms );

		return $terms;
	}

	/**
	 * Tag support for feeds
	 * @param  [type] $args
	 * @return [type]
	 */
	public function job_feed_args( $args ) {
		if ( ! empty( $_GET['job_tags'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'job_listing_tag',
				'field'    => 'slug',
				'terms'    => explode( ',', sanitize_text_field( $_GET['job_tags'] ) )
			);
		}

		return $args;
	}
}

$GLOBALS['job_manager_tags'] = new WP_Job_Manager_Job_Tags();