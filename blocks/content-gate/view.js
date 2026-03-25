document.addEventListener( 'DOMContentLoaded', () => {
	// Check for wp.hooks availability for third-party integrations.
	const wpHooks = window.wp && window.wp.hooks ? window.wp.hooks : null;

	const allGates = Array.from( document.querySelectorAll( '.matture-gate' ) );

	allGates.forEach( ( gate, gateIndex ) => {
		const mode = gate.dataset.mode;
		const allowRehide = gate.dataset.allowRehide === '1';
		const blurIntensity = parseInt( gate.dataset.blur, 10 ) || 20;
		const rememberReveal = gate.dataset.remember === '1';
		const overlay = gate.querySelector( '.matture-gate__overlay' );

		if ( ! overlay ) {
			return;
		}

		const content = gate.querySelector( '.matture-gate__content' );

		// Apply blur intensity via CSS custom property.
		gate.style.setProperty( '--matture-blur', `${ blurIntensity }px` );

		// Unique storage key: prefer anchor ID, fall back to mode + page position.
		const storageKey = `matture_revealed_${
			gate.id || `${ mode }_${ gateIndex }`
		}`;

		// Initial ARIA state.
		gate.setAttribute( 'aria-expanded', 'false' );

		// Make overlay keyboard-accessible.
		overlay.setAttribute( 'tabindex', '0' );
		overlay.setAttribute( 'role', 'button' );

		// Check localStorage.
		if (
			rememberReveal &&
			window.localStorage.getItem( storageKey ) === '1'
		) {
			reveal();
		}

		// Add re-hide button.
		if ( allowRehide ) {
			const rehideBtn = document.createElement( 'button' );
			rehideBtn.className = 'matture-gate__rehide-btn';
			rehideBtn.textContent = 'Re-hide';
			rehideBtn.setAttribute( 'type', 'button' );
			rehideBtn.addEventListener( 'click', ( e ) => {
				e.stopPropagation();
				hide();
			} );
			gate.appendChild( rehideBtn );
		}

		function reveal() {
			if ( wpHooks ) {
				wpHooks.doAction( 'matture.beforeReveal', gate, mode );
			}
			gate.classList.add( 'is-revealed' );
			gate.setAttribute( 'aria-expanded', 'true' );
			gate.dataset.mattureState = 'revealed';
			if ( content ) {
				content.removeAttribute( 'aria-hidden' );
			}
			if ( rememberReveal ) {
				window.localStorage.setItem( storageKey, '1' );
			}
			if ( wpHooks ) {
				wpHooks.doAction( 'matture.afterReveal', gate, mode );
			}
		}

		function hide() {
			gate.classList.remove( 'is-revealed' );
			gate.setAttribute( 'aria-expanded', 'false' );
			gate.dataset.mattureState = 'hidden';
			if ( content ) {
				content.setAttribute( 'aria-hidden', 'true' );
			}
			if ( rememberReveal ) {
				window.localStorage.removeItem( storageKey );
			}
		}

		// Click on overlay triggers reveal.
		overlay.addEventListener( 'click', reveal );

		// Keyboard: Enter or Space on the overlay triggers reveal.
		overlay.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				reveal();
			}
		} );

		const btn = overlay.querySelector( '.matture-gate__reveal-btn' );
		if ( btn ) {
			btn.addEventListener( 'click', ( e ) => {
				e.stopPropagation();
				reveal();
			} );
		}
	} );
} );
