<?php
/**
 * Share Post/Page/Job
 *
 * @package Jobify
 * @since Jobify 1.0
 */

global $post;

$message = apply_filters( 'jobify_share_message', sprintf( _x( 'Check out %1$s on %2$s! %3$s', '1: Article title 2: Site Name 3: Site URL', 'jobify' ), get_the_title(), get_bloginfo( 'name' ), get_permalink() ) );
?>
<div class="entry-share">
	<span class="entry-share-link">
		<div class="share-popup">
			<?php do_action( 'jobify_share_before', $message ); ?>
			<a target="_blank" href="<?php echo esc_url( sprintf( 'http://www.twitter.com?status=%s', urlencode( $message ) ) ); ?>"><i class="icon-twitter"></i></a>
			<a target="_blank" href="<?php echo esc_url( sprintf( 'http://www.facebook.com/sharer.php?u=%s', urlencode( $message ) ) ); ?>"><i class="icon-facebook"></i></a>
			<a target="_blank" href="https://plus.google.com/share?url=<?php the_permalink(); ?>"><i class="icon-gplus"></i></a>
			<?php do_action( 'jobify_share_after', $message ); ?>
		</div>

		<a href="#" class="open-share-popup"><i class="icon-share"></i> <?php _e( 'Share', 'jobify' ); ?></a>
	</span>

	<?php if ( function_exists( 'zilla_likes' ) ) : ?>
	<span class="entry-like">
		<?php zilla_likes(); ?>
	</span>
	<?php endif; ?>
</div>