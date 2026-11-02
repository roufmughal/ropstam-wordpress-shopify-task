<?php
/**
 * "Services" custom post type, meta fields and admin meta box.
 *
 * @package ropstam-services
 */

defined( 'ABSPATH' ) || exit;

/** Register the Services post type (prefixed slug avoids collisions with themes/plugins). */
function ras_register_post_type() {
	register_post_type(
		'ras_service',
		array(
			'labels'       => array(
				'name'          => __( 'Services', 'ropstam-services' ),
				'singular_name' => __( 'Service', 'ropstam-services' ),
				'add_new_item'  => __( 'Add New Service', 'ropstam-services' ),
				'edit_item'     => __( 'Edit Service', 'ropstam-services' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-hammer',
			'rewrite'      => array( 'slug' => 'services' ),
			// custom-fields is required for meta to be exposed through the REST API.
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		)
	);
}
add_action( 'init', 'ras_register_post_type' );

/** Sanitise a price: non-negative float, 2 decimals. */
function ras_sanitize_price( $value ) {
	return round( max( 0.0, (float) $value ), 2 );
}

/** Sanitise delivery days: non-negative integer. */
function ras_sanitize_days( $value ) {
	return absint( $value );
}

/** Register meta fields (sanitised on every write path, incl. REST). */
function ras_register_meta() {
	$auth = static function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta(
		'ras_service',
		'base_price',
		array(
			'type'              => 'number',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ras_sanitize_price',
			'auth_callback'     => $auth,
		)
	);
	register_post_meta(
		'ras_service',
		'delivery_time_days',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 1,
			'show_in_rest'      => true,
			'sanitize_callback' => 'ras_sanitize_days',
			'auth_callback'     => $auth,
		)
	);
}
add_action( 'init', 'ras_register_meta' );

/** Meta box (works in both classic and block editor). */
function ras_add_meta_box() {
	add_meta_box( 'ras_service_details', __( 'Service details', 'ropstam-services' ), 'ras_render_meta_box', 'ras_service', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'ras_add_meta_box' );

/**
 * @param WP_Post $post Current post.
 */
function ras_render_meta_box( $post ) {
	wp_nonce_field( 'ras_save_service', 'ras_service_nonce' );
	$price = get_post_meta( $post->ID, 'base_price', true );
	$days  = get_post_meta( $post->ID, 'delivery_time_days', true );
	?>
	<p>
		<label for="ras_base_price"><?php esc_html_e( 'Base price', 'ropstam-services' ); ?></label>
		<input type="number" class="widefat" id="ras_base_price" name="ras_base_price" min="0" step="0.01" value="<?php echo esc_attr( $price ); ?>">
	</p>
	<p>
		<label for="ras_delivery_days"><?php esc_html_e( 'Delivery time (days)', 'ropstam-services' ); ?></label>
		<input type="number" class="widefat" id="ras_delivery_days" name="ras_delivery_days" min="0" step="1" value="<?php echo esc_attr( $days ); ?>">
	</p>
	<?php
}

/**
 * Save meta: nonce, autosave and capability checks, then sanitise.
 *
 * @param int $post_id Post ID.
 */
function ras_save_meta( $post_id ) {
	if ( ! isset( $_POST['ras_service_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ras_service_nonce'] ) ), 'ras_save_service' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['ras_base_price'] ) ) {
		update_post_meta( $post_id, 'base_price', ras_sanitize_price( sanitize_text_field( wp_unslash( $_POST['ras_base_price'] ) ) ) );
	}
	if ( isset( $_POST['ras_delivery_days'] ) ) {
		update_post_meta( $post_id, 'delivery_time_days', ras_sanitize_days( sanitize_text_field( wp_unslash( $_POST['ras_delivery_days'] ) ) ) );
	}
}
add_action( 'save_post_ras_service', 'ras_save_meta' );
