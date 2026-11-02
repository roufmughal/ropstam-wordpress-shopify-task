/**
 * <bundle-builder> custom element. Vanilla JS, no dependencies.
 * A custom element re-initialises itself when the theme editor re-renders the section.
 * Prices from Liquid are minor units (cents).
 */
( function () {
	'use strict';

	if ( customElements.get( 'bundle-builder' ) ) {
		return;
	}

	var MSG = {
		incomplete: 'Please choose one item from each group.',
		network: 'Network error. Check your connection and try again.',
		generic: 'We could not add the bundle to your cart. Please try again.',
		added: 'Bundle added to your cart.',
		adding: 'Adding…',
		viewCart: 'View cart',
	};

	function uuid() {
		if ( window.crypto && crypto.randomUUID ) {
			return crypto.randomUUID();
		}
		return 'b' + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 8 );
	}

	class BundleBuilder extends HTMLElement {
		connectedCallback() {
			if ( this._ready ) {
				return;
			}
			this.form = this.querySelector( '[data-bb-form]' );
			if ( ! this.form ) {
				return; // Section not configured yet.
			}
			this._ready = true;
			this.busy = false;
			this.groups = Array.from( this.querySelectorAll( '[data-bb-group]' ) );
			this.el = {
				progress: this.querySelector( '[data-bb-progress]' ),
				lines: this.querySelector( '[data-bb-lines]' ),
				total: this.querySelector( '[data-bb-total]' ),
				compare: this.querySelector( '[data-bb-compare]' ),
				save: this.querySelector( '[data-bb-save]' ),
				submit: this.querySelector( '[data-bb-submit]' ),
				reset: this.querySelector( '[data-bb-reset]' ),
				label: this.querySelector( '[data-bb-label]' ),
				status: this.querySelector( '[data-bb-status]' ),
				error: this.querySelector( '[data-bb-error]' ),
			};
			this.idleLabel = this.el.label.textContent;
			this.discount = Math.min( 100, Math.max( 0, Number( this.dataset.discount ) || 0 ) );
			try {
				this.fmt = new Intl.NumberFormat( this.dataset.locale || undefined, { style: 'currency', currency: this.dataset.currency || 'USD' } );
			} catch ( e ) {
				this.fmt = new Intl.NumberFormat( undefined, { style: 'currency', currency: 'USD' } );
			}

			this.form.addEventListener( 'change', this.onChange.bind( this ) );
			this.form.addEventListener( 'submit', this.onSubmit.bind( this ) );
			this.el.reset.addEventListener( 'click', this.onReset.bind( this ) );
			this.update();
		}

		money( cents ) {
			return this.fmt.format( cents / 100 );
		}

		/** Current selection per group (null if none). Reads variant select when the product has one. */
		selections() {
			return this.groups.map( function ( group ) {
				var radio = group.querySelector( 'input[type="radio"]:checked' );
				if ( ! radio ) {
					return null;
				}
				var select = radio.closest( '[data-bb-card]' ).querySelector( 'select' );
				var src = select ? select.options[ select.selectedIndex ] : radio;
				return {
					group: group.dataset.groupLabel,
					title: radio.dataset.title,
					variantTitle: select ? src.dataset.variantTitle : '',
					variantId: Number( src.dataset.variantId ),
					price: Number( src.dataset.price ) || 0,
					available: src.dataset.available === 'true',
				};
			} );
		}

		onChange( event ) {
			if ( event.target.matches( 'select' ) ) {
				// Changing a variant selects its product.
				var radio = event.target.closest( '[data-bb-card]' ).querySelector( 'input[type="radio"]' );
				if ( radio && ! radio.disabled ) {
					radio.checked = true;
				}
			}
			this.clearMessages();
			this.update();
		}

		onReset() {
			this.form.reset();
			this.clearMessages();
			this.update();
		}

		update() {
			var sel = this.selections();
			var chosen = sel.filter( Boolean );
			var complete = chosen.length === sel.length;
			var subtotal = chosen.reduce( function ( sum, s ) { return sum + s.price; }, 0 );
			var total = chosen.reduce( function ( sum, s ) {
				return sum + Math.round( ( s.price * ( 100 - this.discount ) ) / 100 );
			}, 0 );

			this.el.progress.textContent = chosen.length + ' of ' + sel.length + ' selected';
			this.el.lines.textContent = '';
			chosen.forEach( function ( s ) {
				var li = document.createElement( 'li' );
				li.textContent = s.title + ( s.variantTitle && s.variantTitle !== 'Default Title' ? ' (' + s.variantTitle + ')' : '' );
				this.el.lines.appendChild( li );
			}, this );

			this.el.total.textContent = chosen.length ? this.money( complete ? total : subtotal ) : '—';
			var showCompare = complete && this.discount > 0;
			this.el.compare.hidden = ! showCompare;
			if ( showCompare ) {
				this.el.compare.textContent = this.money( subtotal );
			}
			// aria-disabled keeps the button focusable so the reason can be announced.
			this.el.submit.setAttribute( 'aria-disabled', complete ? 'false' : 'true' );
		}

		clearMessages() {
			this.el.status.textContent = '';
			this.el.error.hidden = true;
			this.el.error.textContent = '';
		}

		showError( message ) {
			this.el.status.textContent = '';
			this.el.error.textContent = message;
			this.el.error.hidden = false;
		}

		setBusy( on ) {
			this.busy = on;
			this.el.submit.setAttribute( 'aria-busy', on ? 'true' : 'false' );
			this.el.submit.classList.toggle( 'is-loading', on );
			this.el.reset.disabled = on;
			this.el.label.textContent = on ? MSG.adding : this.idleLabel;
			this.form.querySelectorAll( 'fieldset' ).forEach( function ( f ) { f.disabled = on; } );
		}

		disableDiscountDisplay() {
			this.discount = 0;
			if ( this.el.save ) {
				this.el.save.hidden = true;
			}
			this.update();
		}

		async onSubmit( event ) {
			event.preventDefault();
			if ( this.busy ) {
				return;
			}
			this.clearMessages();

			var sel = this.selections();
			var firstMissing = sel.indexOf( null );
			if ( firstMissing !== -1 ) {
				this.showError( MSG.incomplete );
				var target = this.groups[ firstMissing ].querySelector( 'input:not([disabled])' );
				if ( target ) {
					target.focus();
				}
				return;
			}
			if ( sel.some( function ( s ) { return ! s.available; } ) ) {
				this.showError( 'One of your selections is sold out. Please choose another option.' );
				return;
			}

			var bundleId = uuid();
			var name = this.dataset.bundleName || 'Bundle';
			var items = sel.map( function ( s ) {
				return {
					id: s.variantId,
					quantity: 1,
					properties: {
						Bundle: name + ' #' + bundleId.slice( 0, 6 ), // Visible link between the 3 lines.
						_bundle_id: bundleId, // Hidden (leading underscore) machine-readable id.
						_bundle_group: s.group,
					},
				};
			} );

			this.setBusy( true );
			try {
				var res = await fetch( this.dataset.addUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
					body: JSON.stringify( { items: items } ),
				} );
				var data = await res.json().catch( function () { return {}; } );
				if ( ! res.ok ) {
					// Shopify returns { status, message, description }; 422 = not enough stock.
					this.showError( data.description || data.message || MSG.generic );
					return;
				}
				if ( this.dataset.discountCode ) {
					try {
						var discountResponse = await fetch( '/discount/' + encodeURIComponent( this.dataset.discountCode ), {
							credentials: 'same-origin',
						} );
						if ( ! discountResponse.ok ) {
							this.disableDiscountDisplay();
						}
					} catch ( e ) {
						// A discount endpoint failure must not undo a successful cart add.
						this.disableDiscountDisplay();
					}
				}
				this.dispatchEvent( new CustomEvent( 'bundle-builder:added', { bubbles: true, detail: { bundleId: bundleId, items: data.items } } ) );
				if ( this.dataset.redirect === 'true' ) {
					window.location.assign( this.dataset.cartUrl );
					return;
				}
				this.el.status.textContent = MSG.added + ' ';
				var link = document.createElement( 'a' );
				link.href = this.dataset.cartUrl;
				link.textContent = MSG.viewCart;
				this.el.status.appendChild( link );
			} catch ( e ) {
				this.showError( MSG.network );
			} finally {
				this.setBusy( false );
			}
		}
	}

	customElements.define( 'bundle-builder', BundleBuilder );
} )();
