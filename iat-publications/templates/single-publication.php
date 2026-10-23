<?php
/**
 * A single publication: details, links and the abstract.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$iat_pub = iat_pub_get();
	?>

	<div id="content" class="content-area page-wrapper iat-pub-single" role="main">
		<div class="row row-main align-center">
			<div class="large-9 col">
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'col-inner' ); ?>>

					<p class="iat-pub-back">
						<a href="<?php echo esc_url( get_post_type_archive_link( IAT_PUB_POST_TYPE ) ); ?>">&larr; <?php esc_html_e( 'All publications', 'iat-publications' ); ?></a>
					</p>

					<?php iat_pub_render_areas( $iat_pub ); ?>

					<h1 class="iat-pub-page-title"><?php the_title(); ?></h1>

					<?php if ( $iat_pub['authors'] ) : ?>
						<p class="iat-pub-authors"><?php echo esc_html( $iat_pub['authors'] ); ?></p>
					<?php endif; ?>

					<p class="iat-pub-meta"><?php echo esc_html( iat_pub_meta_line( $iat_pub ) ); ?></p>

					<?php if ( $iat_pub['doi'] ) : ?>
						<p class="iat-pub-doi">DOI: <a href="<?php echo esc_url( $iat_pub['doi_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $iat_pub['doi'] ); ?></a></p>
					<?php endif; ?>

					<?php iat_pub_render_links( $iat_pub ); ?>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="iat-pub-figure"><?php the_post_thumbnail( 'large' ); ?></figure>
					<?php endif; ?>

					<?php if ( '' !== trim( get_the_content() ) ) : ?>
						<h2 class="iat-pub-section-title"><?php esc_html_e( 'Abstract', 'iat-publications' ); ?></h2>
						<div class="entry-content iat-pub-abstract">
							<?php the_content(); ?>
						</div>
					<?php endif; ?>

				</article>
			</div>
		</div>
	</div>

	<?php
endwhile;

get_footer();
