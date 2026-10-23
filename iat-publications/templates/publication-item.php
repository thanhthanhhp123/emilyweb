<?php
/**
 * One entry of the publications list. Included inside the loop.
 */

defined( 'ABSPATH' ) || exit;

$iat_pub = iat_pub_get();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'iat-pub-item' ); ?>>
	<?php iat_pub_render_areas( $iat_pub ); ?>

	<h2 class="iat-pub-item-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

	<?php if ( $iat_pub['authors'] ) : ?>
		<p class="iat-pub-authors"><?php echo esc_html( $iat_pub['authors'] ); ?></p>
	<?php endif; ?>

	<p class="iat-pub-meta"><?php echo esc_html( iat_pub_meta_line( $iat_pub ) ); ?></p>

	<?php $iat_pub_summary = wp_trim_words( strip_shortcodes( get_the_content() ), 40 ); ?>
	<?php if ( $iat_pub_summary ) : ?>
		<p class="iat-pub-summary"><?php echo esc_html( $iat_pub_summary ); ?></p>
	<?php endif; ?>

	<?php iat_pub_render_links( $iat_pub ); ?>
</article>
