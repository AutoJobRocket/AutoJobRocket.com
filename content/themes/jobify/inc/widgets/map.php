<?php
/**
 * Interactive Map
 *
 * @since Jobify 1.0
 */
class Jobify_Widget_Map extends Jobify_Widget {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->widget_cssclass    = 'jobify_widget_map';
		$this->widget_description = __( 'Display a map with pins indicating areas with active job listings.', 'jobify' );
		$this->widget_id          = 'jobify_widget_map';
		$this->widget_name        = __( 'Job Map', 'jobify' );
		$this->settings           = array(
			'search' => array(
				'type'  => 'checkbox',
				'std'   => 1,
				'label' => __( 'Display search/filtering options', 'jobify' )
			),
			'zoom' => array(
				'type'  => 'select',
				'std'   => 'auto',
				'label' => __( 'Zoom Level:', 'jobify' ),
				'options' => array(
					'auto' => __( 'Auto', 'jobify' ),
					1      => 1,
					2      => 2,
					3      => 3,
					4      => 4,
					5      => 5,
					6      => 6,
					7      => 7,
					8      => 8,
					9      => 1,
					10     => 10,
					11     => 11,
					12     => 12,
					13     => 13,
					14     => 14,
					15     => 15,
					16     => 16,
					17     => 17
				)
			),
			'center' => array(
				'type'    => 'text',
				'label'   => __( 'Center Coordinates (optional):', 'jobify' ),
				'std'     => ''
			),
			'desc'  => array(
				'type'    => 'description',
				'std'     => __( 'Coordinates must be set to respect zoom level. Otherwise the map will automatically be set to the bounds of the latest jobs.', 'jobify' )
			)
		);
		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) )
			return;

		wp_enqueue_script( 'google-maps', ( is_ssl() ? 'https' : 'http' ) . '://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false' );
		wp_enqueue_script( 'jquery-map-ui', get_template_directory_uri() . '/js/jquery.ui.map.min.js', array( 'jquery', 'google-maps' ) );
		wp_enqueue_script( 'google-maps-tooltips', get_template_directory_uri() . '/js/tooltip.js', array( 'jquery-map-ui' ) );
		wp_enqueue_script( 'jobify-maps', get_template_directory_uri() . '/js/jobify-map.js', array( 'google-maps-tooltips' ) );

		ob_start();

		extract( $args );

		$search = $instance[ 'search' ];
		
		echo $before_widget;
		?>

			<div id="map-canvas-wrap">
				<?php if ( $search ) : ?>
				<div class="map-filter animated fadeInUp">
					<form class="live-map" method="post" action="<?php the_permalink(); ?>">
						<div class="search_jobs">
							<?php do_action( 'job_manager_job_filters_search_jobs_start' ); ?>

							<div class="search_keywords">
								<label for="search_keywords"><?php _e( 'Keywords', 'jobify' ); ?></label>
								<input type="text" name="search_keywords" id="search_keywords" placeholder="<?php esc_attr_e( 'All Jobs', 'jobify' ); ?>" />
							</div>
							<div class="search_location">
								<label for="search_location"><?php _e( 'Location', 'jobify' ); ?></label>
								<input type="text" name="search_location" id="search_location" placeholder="<?php esc_attr_e( 'Any Location', 'jobify' ); ?>" />
							</div>
							<?php if ( get_option( 'job_manager_enable_categories' ) ) : ?>
								<div class="search_category">
									<label for="search_category"><?php _e( 'Category', 'jobify' ); ?></label>
									<select name="search_category" id="search_category">
										<option value=""><?php _e( 'All Job Categories', 'jobify' ); ?></option>
										<?php foreach ( get_job_listing_categories() as $category ) : ?>
											<option value="<?php echo $category->slug; ?>"><?php echo $category->name; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>

							<?php do_action( 'job_manager_job_filters_search_jobs_end' ); ?>
						</div>
					</form>
				</div>
				<?php endif; ?>

				<div id="jobify-map-canvas"></div>

				<script><!--//--><![CDATA[//><!--
					jQuery( document ).ready(function($) {
						Jobify.Map.init();
					});
				//--><!]]></script>
			</div>

		<?php
		echo $after_widget;

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}

/**
 * Map Points
 *
 * @since Jobify 1.0
 */
class Jobify_Widget_Map_Interactive {

	/**
	 * @var $jobs
	 */
	var $jobs;

	/**
	 * Set things up
	 *
	 * @since Jobify 1.0
	 *
	 * @return void
	 */
	public function __construct( $args = array() ) {
		$this->jobs = get_job_listings( $args );

		$this->setup_actions();
	}

	/**
	 * Create default map points, and update when needed.
	 *
	 * @since Jobify 1.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		if ( ! has_action( 'wp_enqueue_scripts', array( $this, 'jobify_json_default_points' ) ) ) 
			add_action( 'wp_enqueue_scripts', array( $this, 'jobify_json_default_points' ) );

		add_action( 'wp_ajax_nopriv_jobify_update_map', array( $this, 'jobify_json_ajax_points' ) );
		add_action( 'wp_ajax_jobify_update_map', array( $this, 'jobify_json_ajax_points' ) );

		add_action( 'wp_ajax_jobify_cache_cords', array( $this, 'cache_cords' ) );
		add_action( 'wp_ajax_nopriv_jobify_cache_cords', array( $this, 'cache_cords' ) );

		remove_action( 'job_manager_job_filters_search_jobs_end', array( 'WP_Job_Manager_Job_Tags_Shortcodes', 'show_tag_filter' ) );
	}

	/**
	 * Create an array of all of the points found.
	 *
	 * @since Jobify 1.0
	 *
	 * @return void
	 */
	public function json_points() {
		$points = array();

		while ( $this->jobs->have_posts() ) { 
			$this->jobs->the_post();

			$location = get_post()->_job_location;

			if ( get_post()->job_cords ) {
				$location = get_post()->job_cords;
			}

			$points[] = array(
				'job'       => get_post()->ID,
				'location'  => $location,
				'permalink' => get_permalink( get_post()->ID ),
				'title'     => sprintf( _x( '%s at %s', 'Job title at Company Name', 'jobify' ), get_post_field( 'post_title', get_post()->ID ), get_the_company_name( get_post()->ID ) )
			);
		}

		return $points;
	}

	/**
	 * Set the default points.
	 *
	 * @since Jobify 1.0
	 *
	 * @return void
	 */
	function jobify_json_default_points() {
		$map    = new self;
		$points = $map->json_points();

		$options = get_option( 'widget_jobify_widget_map' );
		$options = current( $options ); // Assume they use it once...

		if ( $options[ 'center' ] ) {
			$center = explode( ',', $options[ 'center' ] );
			$center = array_map( 'trim', $center );
		} else {
			$center = array(0, 0);
		}
		
		wp_localize_script( 'jobify', 'jobifyMapSettings', array(
			'points' => $points,
			'zoom'   => $options[ 'zoom' ],
			'center' => array(
				'lat'  => $center[0],
				'long' => $center[1]
			)
		) );
	}

	/**
	 * Set the filtered points
	 *
	 * @since Jobify 1.0
	 *
	 * @return void
	 */
	function jobify_json_ajax_points() {
		global $job_manager, $wpdb;

		$search_location  = sanitize_text_field( stripslashes( $_POST['search_location'] ) );
		$search_keywords  = sanitize_text_field( stripslashes( $_POST['search_keywords'] ) );
		$search_category  = isset( $_POST['search_category'] ) ? sanitize_text_field( stripslashes( $_POST['search_category'] ) ) : '';

		$map = new self( array(
			'search_location'   => $search_location,
			'search_keywords'   => $search_keywords,
			'search_categories' => $search_category
		) );

		$points = $map->json_points();

		echo json_encode( $points );

		die(0);
	}

	function cache_cords() {
		$data = $_POST;

		$cords = $data[ 'cords' ];
		$cords = stripslashes_deep( $cords );

		$job   = absint( $data[ 'job' ] );

		update_post_meta( $job, 'job_cords', $cords );

		echo json_encode( $cords );
	}
}

if ( is_active_widget( false, false, 'jobify_widget_map', true ) )
	new Jobify_Widget_Map_Interactive;
