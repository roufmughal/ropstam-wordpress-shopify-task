/* Editor UI for the dynamic block. No build step: uses WP globals; front end is rendered in PHP. */
( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	wp.blocks.registerBlockType( 'ropstam/services-calculator', {
		edit: function ( props ) {
			var blockProps = wp.blockEditor.useBlockProps();
			return el(
				wp.element.Fragment,
				null,
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: __( 'Settings', 'ropstam-services' ) },
						el( wp.components.TextControl, {
							label: __( 'Heading', 'ropstam-services' ),
							value: props.attributes.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( wp.serverSideRender, { block: 'ropstam/services-calculator', attributes: props.attributes } )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
