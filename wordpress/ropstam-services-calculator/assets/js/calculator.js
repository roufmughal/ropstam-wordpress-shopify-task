/**
 * Live price calculator (vanilla JS). Money is handled in integer cents to avoid float drift.
 * Works with any number of [data-ras-calc] instances on a page.
 */
( function () {
	'use strict';

	function toCents( value ) {
		return Math.round( parseFloat( value ) * 100 ) || 0;
	}

	function init( root ) {
		if ( root.dataset.rasReady ) {
			return;
		}
		root.dataset.rasReady = '1';

		var config = {};
		try {
			config = JSON.parse( root.dataset.config || '{}' );
		} catch ( e ) {
			config = {};
		}
		var i18n = config.i18n || { day: 'day', days: 'days' };
		var formatter;
		try {
			formatter = new Intl.NumberFormat( config.locale || undefined, { style: 'currency', currency: config.currency || 'USD' } );
		} catch ( e ) {
			formatter = { format: function ( v ) { return '$' + v.toFixed( 2 ); } };
		}

		var totalEl = root.querySelector( '[data-ras-total]' );
		var daysEl = root.querySelector( '[data-ras-days]' );

		function update() {
			var service = root.querySelector( 'input[type="radio"]:checked' );
			if ( ! service || ! totalEl || ! daysEl ) {
				return;
			}
			var cents = toCents( service.dataset.price );
			var days = parseInt( service.dataset.days, 10 ) || 0;

			root.querySelectorAll( 'input[data-addon]:checked' ).forEach( function ( box ) {
				cents += toCents( box.dataset.price );
				days += parseInt( box.dataset.days, 10 ) || 0;
			} );
			days = Math.max( 1, days );

			totalEl.textContent = formatter.format( cents / 100 );
			daysEl.textContent = days + ' ' + ( days === 1 ? i18n.day : i18n.days );
		}

		root.addEventListener( 'change', update );
		update();
	}

	wp.domReady( function () {
		document.querySelectorAll( '[data-ras-calc]' ).forEach( init );
	} );
} )();
