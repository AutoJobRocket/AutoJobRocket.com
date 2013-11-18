<?php
/**
 * The default template for displaying content. Used for both single and index/archive/search.
 *
 * @package Jobify
 * @since Jobify 1.0
 */
?>

<div class="page-header">
	<h1 class="page-title"><?php the_title(); ?></h1>
	<h2 class="page-subtitle">
		<span class="job-type <?php echo get_the_job_type() ? sanitize_title( get_the_job_type()->slug ) : ''; ?>"><?php the_job_type(); ?></span>
		<span class="job-company"><?php the_company_name(); ?></span>
		<span class="job-location"><i class="icon-location"></i> <?php the_job_location(false); ?></span>
		<span class="job-date-posted"><i class="icon-calendar"></i> <?php printf( __( 'Posted <date>%s</date> ago', 'jobify' ), human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?></span>
	</h2>
</div>

<div id="content" class="site-content full" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="entry-content">
			<?php the_content(); ?>

			<?php get_template_part( 'content-single-job', 'related' ); ?>
		</div>
	</article><!-- #post -->
</div>