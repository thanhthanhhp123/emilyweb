<?php
/**
 * Front end: the /publications/ query and templates, Google Scholar meta tags and template helpers.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'query_vars', 'iat_pub_query_vars' );
add_action( 'pre_get_posts', 'iat_pub_archive_query' );
add_filter( 'template_include', 'iat_pub_template' );
add_action( 'wp_enqueue_scripts', 'iat_pub_assets', 20 );
add_action( 'wp_head', 'iat_pub_scholar_meta' );

/**
 * Filter params of the list page. "year" is reserved by WordPress, hence the prefix.
 */
function iat_pub_query_vars( $vars ) {
	$vars[] = 'pub_area';
	$vars[] = 'pub_year';

	return $vars;
}

/**
 * Newest year first, then newest entry. Every publication has a year (see iat_pub_save_details),
 * so ordering by it doesn't drop any.
 */
function iat_pub_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( IAT_PUB_POST_TYPE ) ) {
		return;
	}

	$query->set( 'posts_per_page', 10 );
	$query->set( 'meta_key', '_iat_pub_year' );
	$query->set(
		'orderby',
		array(
			'meta_value_num' => 'DESC',
			'date'           => 'DESC',
		)
	);

	$area = sanitize_title( (string) $query->get( 'pub_area' ) );
	if ( $area ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => IAT_PUB_AREA_TAX,
					'field'    => 'slug',
					'terms'    => $area,
				),
			)
		);
	}

	$year = absint( $query->get( 'pub_year' ) );
	if ( $year ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_iat_pub_year',
					'value' => $year,
					'type'  => 'NUMERIC',
				),
			)
		);
	}
}

/**
 * The theme's blog templates would show the WordPress author box and blog sidebar, so use our own.
 */
function iat_pub_template( $template ) {
	if ( is_post_type_archive( IAT_PUB_POST_TYPE ) ) {
		return IAT_PUB_DIR . 'templates/archive-publication.php';
	}

	if ( is_singular( IAT_PUB_POST_TYPE ) ) {
		return IAT_PUB_DIR . 'templates/single-publication.php';
	}

	return $template;
}

function iat_pub_assets() {
	if ( is_post_type_archive( IAT_PUB_POST_TYPE ) || is_singular( IAT_PUB_POST_TYPE ) ) {
		wp_enqueue_style( 'iat-publications', IAT_PUB_URL . 'assets/publications.css', array(), IAT_PUB_VERSION );
	}
}

/**
 * Highwire Press tags, which Google Scholar reads to index a paper page.
 */
function iat_pub_scholar_meta() {
	if ( ! is_singular( IAT_PUB_POST_TYPE ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$pub     = iat_pub_get( $post_id );
	$tags    = array( array( 'citation_title', get_the_title( $post_id ) ) );

	foreach ( $pub['author_list'] as $author ) {
		$tags[] = array( 'citation_author', $author );
	}

	if ( $pub['year'] ) {
		$tags[] = array( 'citation_publication_date', $pub['year'] );
	}

	$venue_tags = array(
		'journal'    => 'citation_journal_title',
		'conference' => 'citation_conference_title',
		'chapter'    => 'citation_inbook_title',
		'report'     => 'citation_technical_report_institution',
	);
	if ( $pub['venue'] && isset( $venue_tags[ $pub['type'] ] ) ) {
		$tags[] = array( $venue_tags[ $pub['type'] ], $pub['venue'] );
	}

	if ( $pub['doi'] ) {
		$tags[] = array( 'citation_doi', $pub['doi'] );
	}

	if ( $pub['pdf_url'] ) {
		$tags[] = array( 'citation_pdf_url', $pub['pdf_url'] );
	}

	$tags[] = array( 'citation_abstract_html_url', get_permalink( $post_id ) );

	foreach ( $tags as $tag ) {
		printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $tag[0] ), esc_attr( $tag[1] ) );
	}
}

/**
 * Everything the templates show about one publication.
 *
 * @param int|WP_Post|null $post Defaults to the current post.
 * @return array
 */
function iat_pub_get( $post = null ) {
	$post    = get_post( $post );
	$types   = iat_pub_types();
	$type    = get_post_meta( $post->ID, '_iat_pub_type', true );
	$authors = (string) get_post_meta( $post->ID, '_iat_pub_authors', true );
	$doi     = get_post_meta( $post->ID, '_iat_pub_doi', true );
	$pdf_id  = (int) get_post_meta( $post->ID, '_iat_pub_pdf_id', true );
	$areas   = get_the_terms( $post, IAT_PUB_AREA_TAX );

	return array(
		'type'        => $type,
		'type_label'  => isset( $types[ $type ] ) ? $types[ $type ] : '',
		'authors'     => $authors,
		'author_list' => iat_pub_split_authors( $authors ),
		'venue'       => get_post_meta( $post->ID, '_iat_pub_venue', true ),
		'year'        => (int) get_post_meta( $post->ID, '_iat_pub_year', true ),
		'doi'         => $doi,
		'doi_url'     => $doi ? 'https://doi.org/' . $doi : '',
		'url'         => get_post_meta( $post->ID, '_iat_pub_url', true ),
		'pdf_url'     => $pdf_id ? (string) wp_get_attachment_url( $pdf_id ) : '',
		'areas'       => is_array( $areas ) ? $areas : array(),
	);
}

/**
 * Splits on semicolons when there are any (for "Last, First" names), otherwise on commas.
 */
function iat_pub_split_authors( $authors ) {
	$separator = false !== strpos( $authors, ';' ) ? ';' : ',';

	return array_values( array_filter( array_map( 'trim', explode( $separator, $authors ) ) ) );
}

/**
 * "Journal article · Journal name · 2025"
 */
function iat_pub_meta_line( $pub ) {
	return implode( ' · ', array_filter( array( $pub['type_label'], $pub['venue'], $pub['year'] ? (string) $pub['year'] : '' ) ) );
}

function iat_pub_render_links( $pub ) {
	$links = array();

	if ( $pub['pdf_url'] ) {
		$links[] = array( $pub['pdf_url'], __( 'PDF', 'iat-publications' ), 'primary' );
	}
	if ( $pub['doi_url'] ) {
		$links[] = array( $pub['doi_url'], __( 'DOI', 'iat-publications' ), 'primary is-outline' );
	}
	if ( $pub['url'] ) {
		$links[] = array( $pub['url'], __( 'Publisher page', 'iat-publications' ), 'primary is-outline' );
	}

	if ( ! $links ) {
		return;
	}

	echo '<div class="iat-pub-links">';
	foreach ( $links as $link ) {
		printf(
			'<a class="button %3$s is-small" href="%1$s" target="_blank" rel="noopener"><span>%2$s</span></a>',
			esc_url( $link[0] ),
			esc_html( $link[1] ),
			esc_attr( $link[2] )
		);
	}
	echo '</div>';
}

function iat_pub_render_areas( $pub ) {
	if ( ! $pub['areas'] ) {
		return;
	}

	$archive = get_post_type_archive_link( IAT_PUB_POST_TYPE );

	echo '<p class="iat-pub-areas">';
	foreach ( $pub['areas'] as $area ) {
		printf(
			'<a class="iat-pub-area" href="%s">%s</a>',
			esc_url( add_query_arg( 'pub_area', $area->slug, $archive ) ),
			esc_html( $area->name )
		);
	}
	echo '</p>';
}

/**
 * Years that have at least one published publication, newest first.
 *
 * @return int[]
 */
function iat_pub_years() {
	global $wpdb;

	$years = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS pub_year FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'
			ORDER BY pub_year DESC",
			'_iat_pub_year',
			IAT_PUB_POST_TYPE
		)
	);

	return array_map( 'absint', $years );
}
