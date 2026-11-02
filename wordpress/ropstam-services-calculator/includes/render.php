<?php
/**
 * Query, assets, shortcode and dynamic block.
 *
 * @package ropstam-services
 */

defined( 'ABSPATH' ) || exit;

/** Register (not enqueue) assets; they are enqueued only when the component renders. */
function ras_register_assets() {
	wp_register_style( 'ras-calculator', RAS_URL . 'assets/css/calculator.css', array(), RAS_VERSION );
	wp_register_script(
		'ras-calculator',
		RAS_URL . 'assets/js/calculator.js',
		array( 'wp-dom-ready' ),
		RAS_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_register_script(
		'ras-block-editor',
		RAS_URL . 'blocks/services-calculator/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		RAS_VERSION,
		array( 'in_footer' => true )
	);
}
add_action( 'init', 'ras_register_assets', 5 );

/**
 * Optional add-ons. Filterable: add_filter( 'ras_addons', ... ).
 * 'days' is a delivery-time delta (negative = faster).
 *
 * @return array[]
 */
function ras_get_addons() {
	$addons = array(
		array(
			'id'    => 'priority',
			'label' => __( 'Priority Turnaround', 'ropstam-services' ),
			'price' => 50,
			'days'  => -2,
		),
		array(
			'id'    => 'revision',
			'label' => __( 'Extra Revision Round', 'ropstam-services' ),
			'price' => 25,
			'days'  => 1,
		),
		array(
			'id'    => 'source',
			'label' => __( 'Source Files', 'ropstam-services' ),
			'price' => 40,
			'days'  => 0,
		),
	);
	return apply_filters( 'ras_addons', $addons );
}

/**
 * 3 most recently updated published services (WP_Query, no raw SQL).
 *
 * @return array[] id, title, price, days.
 */
function ras_get_services() {
	$query    = new WP_Query(
		array(
			'post_type'           => 'ras_service',
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'orderby'             => 'modified',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	$services = array();
	foreach ( $query->posts as $post ) {
		$services[] = array(
			'id'    => (int) $post->ID,
			'title' => get_the_title( $post ),
			'price' => (float) get_post_meta( $post->ID, 'base_price', true ),
			'days'  => (int) get_post_meta( $post->ID, 'delivery_time_days', true ),
		);
	}
	return $services;
}

/** Plain, no-JS-safe price string used for the server-rendered initial value. */
function ras_format_price( $amount ) {
	$symbol = apply_filters( 'ras_currency_symbol', '$' );
	return $symbol . number_format_i18n( (float) $amount, 2 );
}

/**
 * Markup shared by the block and the shortcode.
 *
 * @param string $heading Optional heading.
 * @return string
 */
function ras_render_calculator( $heading = '' ) {
	$services = ras_get_services();
	if ( empty( $services ) ) {
		return '<p class="ras-calc__empty">' . esc_html__( 'No services available yet.', 'ropstam-services' ) . '</p>';
	}

	wp_enqueue_style( 'ras-calculator' );
	wp_enqueue_script( 'ras-calculator' );

	$uid     = wp_unique_id( 'ras-calc-' );
	$addons  = ras_get_addons();
	$heading = '' !== $heading ? $heading : __( 'Estimate your price', 'ropstam-services' );
	$initial_days = max( 1, (int) $services[0]['days'] );
	$config  = array(
		'currency' => apply_filters( 'ras_currency', 'USD' ),
		'locale'   => str_replace( '_', '-', determine_locale() ),
		'i18n'     => array(
			'day'  => __( 'day', 'ropstam-services' ),
			'days' => __( 'days', 'ropstam-services' ),
		),
	);

	ob_start();
	?>
	<section class="ras-calc" data-ras-calc data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
		<h3 class="ras-calc__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $heading ); ?></h3>

		<fieldset class="ras-calc__fieldset">
			<legend><?php esc_html_e( 'Choose a service', 'ropstam-services' ); ?></legend>
			<?php foreach ( $services as $i => $service ) : ?>
				<label class="ras-calc__option">
					<input type="radio" name="<?php echo esc_attr( $uid ); ?>-service" value="<?php echo esc_attr( $service['id'] ); ?>" data-price="<?php echo esc_attr( $service['price'] ); ?>" data-days="<?php echo esc_attr( $service['days'] ); ?>" <?php checked( 0, $i ); ?>>
					<span class="ras-calc__name"><?php echo esc_html( $service['title'] ); ?></span>
					<span class="ras-calc__meta">
						<?php
						echo esc_html( ras_format_price( $service['price'] ) );
						/* translators: %d: number of days. */
						echo esc_html( ' · ' . sprintf( _n( '%d day', '%d days', $service['days'], 'ropstam-services' ), $service['days'] ) );
						?>
					</span>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="ras-calc__fieldset">
			<legend><?php esc_html_e( 'Optional add-ons', 'ropstam-services' ); ?></legend>
			<?php foreach ( $addons as $addon ) : ?>
				<label class="ras-calc__option">
					<input type="checkbox" data-addon value="<?php echo esc_attr( $addon['id'] ); ?>" data-price="<?php echo esc_attr( $addon['price'] ); ?>" data-days="<?php echo esc_attr( $addon['days'] ); ?>">
					<span class="ras-calc__name"><?php echo esc_html( $addon['label'] ); ?></span>
					<span class="ras-calc__meta">+<?php echo esc_html( ras_format_price( $addon['price'] ) ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<div class="ras-calc__result" aria-live="polite" aria-atomic="true">
			<p><?php esc_html_e( 'Estimated price:', 'ropstam-services' ); ?> <output data-ras-total><?php echo esc_html( ras_format_price( $services[0]['price'] ) ); ?></output></p>
			<p><?php esc_html_e( 'Estimated delivery:', 'ropstam-services' ); ?> <span data-ras-days><?php echo esc_html( sprintf( _n( '%d day', '%d days', $initial_days, 'ropstam-services' ), $initial_days ) ); ?></span></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** [ras_services_calculator heading="..."] */
function ras_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'heading' => '' ), $atts, 'ras_services_calculator' );
	return ras_render_calculator( sanitize_text_field( $atts['heading'] ) );
}
add_shortcode( 'ras_services_calculator', 'ras_shortcode' );

/** Dynamic block render callback. */
function ras_render_block( $attributes ) {
	$heading = isset( $attributes['heading'] ) ? sanitize_text_field( $attributes['heading'] ) : '';
	return '<div ' . get_block_wrapper_attributes() . '>' . ras_render_calculator( $heading ) . '</div>';
}

function ras_register_block() {
	register_block_type( RAS_PATH . 'blocks/services-calculator', array( 'render_callback' => 'ras_render_block' ) );
}
add_action( 'init', 'ras_register_block' );
