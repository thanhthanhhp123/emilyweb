<?php
/**
 * Publications list (/publications/), with research area and year filters.
 */

defined( 'ABSPATH' ) || exit;

$iat_pub_current_area = sanitize_title( (string) get_query_var( 'pub_area' ) );
$iat_pub_current_year = absint( get_query_var( 'pub_year' ) );
$iat_pub_areas        = get_terms(
	array(
		'taxonomy'   => IAT_PUB_AREA_TAX,
		'hide_empty' => true,
	)
);
$iat_pub_areas        = is_wp_error( $iat_pub_areas ) ? array() : $iat_pub_areas;
$iat_pub_years        = iat_pub_years();
$iat_pub_archive_url  = get_post_type_archive_link( IAT_PUB_POST_TYPE );

get_header();
?>

<div id="content" class="content-area page-wrapper iat-pub-archive" role="main">
	<div class="row row-main">
		<div class="large-12 col">
			<div class="col-inner">

				<header class="iat-pub-header">
					<h1 class="iat-pub-page-title"><?php post_type_archive_title(); ?></h1>
				</header>

				<?php if ( count( $iat_pub_areas ) > 1 || count( $iat_pub_years ) > 1 ) : ?>
					<form class="iat-pub-filter" method="get" action="<?php echo esc_url( $iat_pub_archive_url ); ?>">
						<?php if ( count( $iat_pub_areas ) > 1 ) : ?>
							<label class="screen-reader-text" for="iat-pub-area"><?php esc_html_e( 'Research area', 'iat-publications' ); ?></label>
							<select name="pub_area" id="iat-pub-area">
								<option value=""><?php esc_html_e( 'All research areas', 'iat-publications' ); ?></option>
								<?php foreach ( $iat_pub_areas as $iat_pub_area ) : ?>
									<option value="<?php echo esc_attr( $iat_pub_area->slug ); ?>" <?php selected( $iat_pub_current_area, $iat_pub_area->slug ); ?>><?php echo esc_html( $iat_pub_area->name ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>

						<?php if ( count( $iat_pub_years ) > 1 ) : ?>
							<label class="screen-reader-text" for="iat-pub-year"><?php esc_html_e( 'Year', 'iat-publications' ); ?></label>
							<select name="pub_year" id="iat-pub-year">
								<option value=""><?php esc_html_e( 'All years', 'iat-publications' ); ?></option>
								<?php foreach ( $iat_pub_years as $iat_pub_year ) : ?>
									<option value="<?php echo esc_attr( $iat_pub_year ); ?>" <?php selected( $iat_pub_current_year, $iat_pub_year ); ?>><?php echo esc_html( $iat_pub_year ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>

						<button type="submit" class="button primary"><?php esc_html_e( 'Filter', 'iat-publications' ); ?></button>

						<?php if ( $iat_pub_current_area || $iat_pub_current_year ) : ?>
							<a class="iat-pub-reset" href="<?php echo esc_url( $iat_pub_archive_url ); ?>"><?php esc_html_e( 'Clear filters', 'iat-publications' ); ?></a>
						<?php endif; ?>
					</form>
				<?php endif; ?>

				<?php if ( have_posts() ) : ?>
					<div class="iat-pub-list">
						<?php
						while ( have_posts() ) :
							the_post();
							include IAT_PUB_DIR . 'templates/publication-item.php';
						endwhile;
						?>
					</div>

					<?php
					if ( function_exists( 'flatsome_posts_pagination' ) ) {
						flatsome_posts_pagination();
					} else {
						the_posts_pagination();
					}
					?>
				<?php else : ?>
					<p class="iat-pub-empty"><?php esc_html_e( 'No publications found.', 'iat-publications' ); ?></p>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>

<?php
get_footer();
