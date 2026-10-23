<?php
/**
 * Publication post type, Research area taxonomy and the publication meta fields.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'iat_pub_register' );
// After iat_pub_save_details (priority 10), which may have just removed an emptied year.
add_action( 'save_post_' . IAT_PUB_POST_TYPE, 'iat_pub_ensure_year', 20, 2 );

/**
 * Publication types, keyed by the value stored in _iat_pub_type.
 *
 * @return array<string, string>
 */
function iat_pub_types() {
	return array(
		'journal'    => __( 'Journal article', 'iat-publications' ),
		'conference' => __( 'Conference paper', 'iat-publications' ),
		'chapter'    => __( 'Book chapter', 'iat-publications' ),
		'preprint'   => __( 'Preprint', 'iat-publications' ),
		'report'     => __( 'Technical report', 'iat-publications' ),
		'other'      => __( 'Other', 'iat-publications' ),
	);
}

function iat_pub_register() {
	register_post_type(
		IAT_PUB_POST_TYPE,
		array(
			'labels'        => array(
				'name'               => __( 'Publications', 'iat-publications' ),
				'singular_name'      => __( 'Publication', 'iat-publications' ),
				'add_new'            => __( 'Add New', 'iat-publications' ),
				'add_new_item'       => __( 'Add New Publication', 'iat-publications' ),
				'edit_item'          => __( 'Edit Publication', 'iat-publications' ),
				'new_item'           => __( 'New Publication', 'iat-publications' ),
				'view_item'          => __( 'View Publication', 'iat-publications' ),
				'view_items'         => __( 'View Publications', 'iat-publications' ),
				'search_items'       => __( 'Search Publications', 'iat-publications' ),
				'not_found'          => __( 'No publications found.', 'iat-publications' ),
				'not_found_in_trash' => __( 'No publications found in Trash.', 'iat-publications' ),
				'all_items'          => __( 'All Publications', 'iat-publications' ),
				'archives'           => __( 'Publications', 'iat-publications' ),
				'menu_name'          => __( 'Publications', 'iat-publications' ),
			),
			'public'        => true,
			'has_archive'   => 'publications',
			'rewrite'       => array(
				'slug'       => 'publications',
				'with_front' => false,
			),
			'menu_position' => 20,
			'menu_icon'     => 'dashicons-media-document',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			// Keeps this post type on the classic editor, where the details box sits under the title.
			'show_in_rest'  => false,
		)
	);

	// Not publicly queryable: the list page filters by area through its own pub_area query var.
	register_taxonomy(
		IAT_PUB_AREA_TAX,
		IAT_PUB_POST_TYPE,
		array(
			'labels'            => array(
				'name'          => __( 'Research areas', 'iat-publications' ),
				'singular_name' => __( 'Research area', 'iat-publications' ),
				'all_items'     => __( 'All research areas', 'iat-publications' ),
				'edit_item'     => __( 'Edit research area', 'iat-publications' ),
				'add_new_item'  => __( 'Add new research area', 'iat-publications' ),
				'search_items'  => __( 'Search research areas', 'iat-publications' ),
				'not_found'     => __( 'No research areas found.', 'iat-publications' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);

	$fields = array(
		'_iat_pub_type'    => array( 'string', 'iat_pub_sanitize_type' ),
		'_iat_pub_authors' => array( 'string', 'sanitize_text_field' ),
		'_iat_pub_venue'   => array( 'string', 'sanitize_text_field' ),
		'_iat_pub_year'    => array( 'integer', 'iat_pub_sanitize_year' ),
		'_iat_pub_doi'     => array( 'string', 'iat_pub_sanitize_doi' ),
		'_iat_pub_url'     => array( 'string', 'esc_url_raw' ),
		'_iat_pub_pdf_id'  => array( 'integer', 'iat_pub_sanitize_pdf_id' ),
	);

	foreach ( $fields as $key => $field ) {
		register_post_meta(
			IAT_PUB_POST_TYPE,
			$key,
			array(
				'type'              => $field[0],
				'single'            => true,
				'sanitize_callback' => $field[1],
			)
		);
	}
}

/**
 * The list page sorts and filters by year, so every publication needs one, whether it was saved
 * from the edit screen, quick edit, WP-CLI or an import.
 */
function iat_pub_ensure_year( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || get_post_meta( $post_id, '_iat_pub_year', true ) ) {
		return;
	}

	update_post_meta( $post_id, '_iat_pub_year', (int) get_the_date( 'Y', $post ) );
}

function iat_pub_sanitize_type( $value ) {
	$value = sanitize_key( $value );

	return array_key_exists( $value, iat_pub_types() ) ? $value : '';
}

function iat_pub_sanitize_year( $value ) {
	$year = absint( $value );

	return ( $year >= 1900 && $year <= (int) gmdate( 'Y' ) + 1 ) ? $year : 0;
}

/**
 * Stores the bare DOI ("10.xxxx/...") whether it was pasted as a doi.org link, "doi:..." or plain.
 */
function iat_pub_sanitize_doi( $value ) {
	$doi = trim( sanitize_text_field( $value ) );
	$doi = preg_replace( '#^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)#i', '', $doi );

	return 0 === strpos( $doi, '10.' ) ? $doi : '';
}

function iat_pub_sanitize_pdf_id( $value ) {
	$id = absint( $value );

	return ( $id && 'application/pdf' === get_post_mime_type( $id ) ) ? $id : 0;
}
