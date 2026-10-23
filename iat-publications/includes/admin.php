<?php
/**
 * wp-admin: the Publication details box, saving it, and the list table columns.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . IAT_PUB_POST_TYPE, 'iat_pub_add_details_box' );
add_action( 'edit_form_after_title', 'iat_pub_render_after_title' );
add_action( 'save_post_' . IAT_PUB_POST_TYPE, 'iat_pub_save_details' );
add_action( 'admin_enqueue_scripts', 'iat_pub_admin_assets' );
add_filter( 'enter_title_here', 'iat_pub_title_placeholder', 10, 2 );

add_filter( 'manage_' . IAT_PUB_POST_TYPE . '_posts_columns', 'iat_pub_columns' );
add_action( 'manage_' . IAT_PUB_POST_TYPE . '_posts_custom_column', 'iat_pub_column_content', 10, 2 );
add_filter( 'manage_edit-' . IAT_PUB_POST_TYPE . '_sortable_columns', 'iat_pub_sortable_columns' );
add_action( 'pre_get_posts', 'iat_pub_admin_orderby' );

/**
 * Registered in a custom context so it can be printed between the title and the abstract editor.
 */
function iat_pub_add_details_box() {
	add_meta_box(
		'iat_pub_details',
		__( 'Publication details', 'iat-publications' ),
		'iat_pub_render_details_box',
		IAT_PUB_POST_TYPE,
		'iat_pub_after_title',
		'high'
	);
}

function iat_pub_render_after_title( $post ) {
	if ( IAT_PUB_POST_TYPE !== $post->post_type ) {
		return;
	}

	echo '<div id="iat-pub-after-title" style="margin-top:20px">';
	do_meta_boxes( get_current_screen(), 'iat_pub_after_title', $post );
	echo '</div>';
	echo '<h2 style="padding:0 0 8px;font-size:1.3em">' . esc_html__( 'Abstract', 'iat-publications' ) . '</h2>';
}

function iat_pub_render_details_box( $post ) {
	$type    = get_post_meta( $post->ID, '_iat_pub_type', true );
	$year    = get_post_meta( $post->ID, '_iat_pub_year', true );
	$pdf_id  = (int) get_post_meta( $post->ID, '_iat_pub_pdf_id', true );
	$pdf_url = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';

	wp_nonce_field( 'iat_pub_save', 'iat_pub_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="iat_pub_type"><?php esc_html_e( 'Type', 'iat-publications' ); ?></label></th>
			<td>
				<select name="iat_pub_type" id="iat_pub_type">
					<?php foreach ( iat_pub_types() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type ? $type : 'journal', $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="iat_pub_authors"><?php esc_html_e( 'Authors', 'iat-publications' ); ?></label></th>
			<td>
				<input type="text" class="large-text" name="iat_pub_authors" id="iat_pub_authors" value="<?php echo esc_attr( get_post_meta( $post->ID, '_iat_pub_authors', true ) ); ?>" placeholder="Nguyen Van A, Tran Thi B">
				<p class="description"><?php esc_html_e( 'Separate authors with commas. Use semicolons instead if a name contains a comma.', 'iat-publications' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="iat_pub_venue"><?php esc_html_e( 'Published in', 'iat-publications' ); ?></label></th>
			<td>
				<input type="text" class="large-text" name="iat_pub_venue" id="iat_pub_venue" value="<?php echo esc_attr( get_post_meta( $post->ID, '_iat_pub_venue', true ) ); ?>">
				<p class="description"><?php esc_html_e( 'Journal, conference or publisher name.', 'iat-publications' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="iat_pub_year"><?php esc_html_e( 'Year', 'iat-publications' ); ?></label></th>
			<td>
				<input type="number" class="small-text" name="iat_pub_year" id="iat_pub_year" value="<?php echo esc_attr( $year ? $year : '' ); ?>" min="1900" max="<?php echo esc_attr( (int) gmdate( 'Y' ) + 1 ); ?>">
				<p class="description"><?php esc_html_e( 'Publication year. Left empty, the year the entry was created is used.', 'iat-publications' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="iat_pub_doi"><?php esc_html_e( 'DOI', 'iat-publications' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" name="iat_pub_doi" id="iat_pub_doi" value="<?php echo esc_attr( get_post_meta( $post->ID, '_iat_pub_doi', true ) ); ?>" placeholder="10.1234/abcd.5678">
				<p class="description"><?php esc_html_e( 'A doi.org link is fine too.', 'iat-publications' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="iat_pub_url"><?php esc_html_e( 'External link', 'iat-publications' ); ?></label></th>
			<td>
				<input type="url" class="large-text" name="iat_pub_url" id="iat_pub_url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_iat_pub_url', true ) ); ?>" placeholder="https://">
				<p class="description"><?php esc_html_e( 'Page of the paper on the publisher or journal site.', 'iat-publications' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'PDF', 'iat-publications' ); ?></th>
			<td class="iat-pub-pdf" data-title="<?php esc_attr_e( 'Choose the paper PDF', 'iat-publications' ); ?>" data-button="<?php esc_attr_e( 'Use this PDF', 'iat-publications' ); ?>">
				<input type="hidden" name="iat_pub_pdf_id" value="<?php echo esc_attr( $pdf_id ? $pdf_id : '' ); ?>">
				<a class="iat-pub-pdf-name" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener" <?php echo $pdf_url ? '' : 'hidden'; ?>><?php echo esc_html( $pdf_url ? wp_basename( $pdf_url ) : '' ); ?></a>
				<p>
					<button type="button" class="button iat-pub-pdf-choose"><?php esc_html_e( 'Choose PDF', 'iat-publications' ); ?></button>
					<button type="button" class="button-link iat-pub-pdf-remove" <?php echo $pdf_url ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'iat-publications' ); ?></button>
				</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Saves the details box. A year left empty is filled in afterwards by iat_pub_ensure_year().
 */
function iat_pub_save_details( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['iat_pub_nonce'] ) ? sanitize_key( wp_unslash( $_POST['iat_pub_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'iat_pub_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'type', 'authors', 'venue', 'year', 'doi', 'url', 'pdf_id' ) as $field ) {
		$key   = '_iat_pub_' . $field;
		$value = isset( $_POST[ 'iat_pub_' . $field ] ) ? wp_unslash( $_POST[ 'iat_pub_' . $field ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the registered meta callback below.
		$value = sanitize_meta( $key, $value, 'post', IAT_PUB_POST_TYPE );

		if ( '' === $value || 0 === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}
	}
}

function iat_pub_admin_assets( $hook ) {
	$screen = get_current_screen();

	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || IAT_PUB_POST_TYPE !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'iat-pub-admin', IAT_PUB_URL . 'assets/admin.js', array( 'jquery' ), IAT_PUB_VERSION, true );
}

function iat_pub_title_placeholder( $placeholder, $post ) {
	return IAT_PUB_POST_TYPE === $post->post_type ? __( 'Paper title', 'iat-publications' ) : $placeholder;
}

function iat_pub_columns( $columns ) {
	$result = array();

	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;

		if ( 'title' === $key ) {
			$result['iat_pub_authors'] = __( 'Authors', 'iat-publications' );
			$result['iat_pub_venue']   = __( 'Published in', 'iat-publications' );
			$result['iat_pub_year']    = __( 'Year', 'iat-publications' );
			$result['iat_pub_pdf']     = __( 'PDF', 'iat-publications' );
		}
	}

	return $result;
}

function iat_pub_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'iat_pub_authors':
			echo esc_html( get_post_meta( $post_id, '_iat_pub_authors', true ) );
			break;
		case 'iat_pub_venue':
			echo esc_html( get_post_meta( $post_id, '_iat_pub_venue', true ) );
			break;
		case 'iat_pub_year':
			echo esc_html( get_post_meta( $post_id, '_iat_pub_year', true ) );
			break;
		case 'iat_pub_pdf':
			echo get_post_meta( $post_id, '_iat_pub_pdf_id', true ) ? '&#10003;' : '&mdash;';
			break;
	}
}

function iat_pub_sortable_columns( $columns ) {
	$columns['iat_pub_year'] = 'iat_pub_year';

	return $columns;
}

function iat_pub_admin_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'iat_pub_year' !== $query->get( 'orderby' ) ) {
		return;
	}

	$query->set( 'meta_key', '_iat_pub_year' );
	$query->set( 'orderby', 'meta_value_num' );
}
