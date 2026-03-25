/**
 * @jest-environment jsdom
 */

/**
 * Tests for blocks/content-gate/view.js
 */

describe( 'Matture view.js', () => {
	let container;
	let mockWpHooks;
	let mockLocalStorage;

	beforeEach( () => {
		// Set up DOM container
		container = document.createElement( 'div' );
		document.body.appendChild( container );

		// Mock wp.hooks
		mockWpHooks = {
			doAction: jest.fn(),
		};
		global.wp = {
			hooks: mockWpHooks,
		};

		// Mock localStorage
		mockLocalStorage = {};
		const localStorageMock = {
			getItem: jest.fn( ( key ) => mockLocalStorage[ key ] || null ),
			setItem: jest.fn( ( key, value ) => {
				mockLocalStorage[ key ] = value;
			} ),
			removeItem: jest.fn( ( key ) => {
				delete mockLocalStorage[ key ];
			} ),
		};
		global.localStorage = localStorageMock;
		Object.defineProperty( window, 'localStorage', {
			value: localStorageMock,
			writable: true,
		} );
	} );

	afterEach( () => {
		document.body.removeChild( container );
		jest.clearAllMocks();
		mockLocalStorage = {};
	} );

	const createGate = ( options = {} ) => {
		const {
			mode = 'mature',
			allowRehide = '0',
			blur = '20',
			remember = '0',
			buttonLabel = 'Reveal',
			subLabel = '',
		} = options;

		const gate = document.createElement( 'div' );
		gate.className = 'matture-gate';
		gate.setAttribute( 'data-mode', mode );
		gate.setAttribute( 'data-allow-rehide', allowRehide );
		gate.setAttribute( 'data-blur', blur );
		gate.setAttribute( 'data-remember', remember );
		gate.setAttribute( 'data-button-label', buttonLabel );
		gate.setAttribute( 'data-sub-label', subLabel );
		gate.setAttribute( 'data-matture-state', 'hidden' );

		const overlay = document.createElement( 'div' );
		overlay.className = 'matture-gate__overlay';

		const warning = document.createElement( 'div' );
		warning.className = 'matture-gate__warning';

		const btn = document.createElement( 'button' );
		btn.className = 'matture-gate__reveal-btn';
		btn.textContent = buttonLabel;

		warning.appendChild( btn );
		overlay.appendChild( warning );

		const content = document.createElement( 'div' );
		content.className = 'matture-gate__content';
		content.setAttribute( 'aria-hidden', 'true' );
		content.innerHTML = '<p>Hidden content</p>';

		gate.appendChild( overlay );
		gate.appendChild( content );

		return gate;
	};

	const loadViewScript = () => {
		// eslint-disable-next-line @wordpress/no-global-event-listener
		const event = new Event( 'DOMContentLoaded' );
		// Load the view.js script content
		// eslint-disable-next-line global-require
		require( '../../blocks/content-gate/view.js' );
		document.dispatchEvent( event );
	};

	describe( 'Reveal functionality', () => {
		test( 'Clicking the overlay adds is-revealed class to gate', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( gate.classList.contains( 'is-revealed' ) ).toBe( true );
		} );

		test( 'Clicking the overlay sets aria-expanded="true" on gate', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( gate.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		} );

		test( 'Clicking the overlay sets data-matture-state="revealed" on gate', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( gate.dataset.mattureState ).toBe( 'revealed' );
		} );

		test( 'Clicking the overlay removes aria-hidden from content div', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			const content = gate.querySelector( '.matture-gate__content' );
			overlay.click();

			expect( content.hasAttribute( 'aria-hidden' ) ).toBe( false );
		} );

		test( 'Pressing Enter on the overlay triggers reveal', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			const event = new KeyboardEvent( 'keydown', { key: 'Enter' } );
			overlay.dispatchEvent( event );

			expect( gate.classList.contains( 'is-revealed' ) ).toBe( true );
		} );

		test( 'Pressing Space on the overlay triggers reveal', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			const event = new KeyboardEvent( 'keydown', { key: ' ' } );
			overlay.dispatchEvent( event );

			expect( gate.classList.contains( 'is-revealed' ) ).toBe( true );
		} );
	} );

	describe( 'Re-hide functionality', () => {
		test( 'data-allow-rehide="1" causes a re-hide button to be appended', () => {
			const gate = createGate( { allowRehide: '1' } );
			container.appendChild( gate );
			loadViewScript();

			const rehideBtn = gate.querySelector(
				'.matture-gate__rehide-btn'
			);
			expect( rehideBtn ).not.toBeNull();
			expect( rehideBtn.textContent ).toBe( 'Re-hide' );
		} );

		test( 'Clicking the re-hide button removes is-revealed class', () => {
			const gate = createGate( { allowRehide: '1' } );
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();
			expect( gate.classList.contains( 'is-revealed' ) ).toBe( true );

			const rehideBtn = gate.querySelector(
				'.matture-gate__rehide-btn'
			);
			rehideBtn.click();

			expect( gate.classList.contains( 'is-revealed' ) ).toBe( false );
		} );

		test( 'Clicking the re-hide button sets aria-expanded="false"', () => {
			const gate = createGate( { allowRehide: '1' } );
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			const rehideBtn = gate.querySelector(
				'.matture-gate__rehide-btn'
			);
			rehideBtn.click();

			expect( gate.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		} );

		test( 'Clicking the re-hide button sets data-matture-state="hidden"', () => {
			const gate = createGate( { allowRehide: '1' } );
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			const rehideBtn = gate.querySelector(
				'.matture-gate__rehide-btn'
			);
			rehideBtn.click();

			expect( gate.dataset.mattureState ).toBe( 'hidden' );
		} );
	} );

	describe( 'localStorage functionality', () => {
		test( 'data-remember="1" stores "1" in localStorage on reveal', () => {
			const gate = createGate( { remember: '1' } );
			gate.id = 'test-gate-1';
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( localStorage.setItem ).toHaveBeenCalledWith(
				'matture_revealed_test-gate-1',
				'1'
			);
		} );

		test( 'data-remember="1" auto-reveals on load if localStorage key is set', () => {
			const gate = createGate( { remember: '1' } );
			gate.id = 'test-gate-2';
			mockLocalStorage[ 'matture_revealed_test-gate-2' ] = '1';
			container.appendChild( gate );

			loadViewScript();

			expect( gate.classList.contains( 'is-revealed' ) ).toBe( true );
			expect( gate.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		} );

		test( 'data-remember="1" removes localStorage key on re-hide', () => {
			const gate = createGate( { remember: '1', allowRehide: '1' } );
			gate.id = 'test-gate-3';
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			const rehideBtn = gate.querySelector(
				'.matture-gate__rehide-btn'
			);
			rehideBtn.click();

			expect( localStorage.removeItem ).toHaveBeenCalledWith(
				'matture_revealed_test-gate-3'
			);
		} );

		test( 'localStorage uses mode + index fallback when no gate ID is set', () => {
			const gate = createGate( { remember: '1', mode: 'spoiler' } );
			// Don't set gate.id - it should fall back to mode + index
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			// First gate (index 0) with mode spoiler
			expect( localStorage.setItem ).toHaveBeenCalledWith(
				'matture_revealed_spoiler_0',
				'1'
			);
		} );
	} );

	describe( 'wp.hooks integration', () => {
		test( 'wp.hooks.doAction("matture.beforeReveal") fires before reveal completes', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( mockWpHooks.doAction ).toHaveBeenCalledWith(
				'matture.beforeReveal',
				gate,
				'mature'
			);
		} );

		test( 'wp.hooks.doAction("matture.afterReveal") fires after reveal completes', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			expect( mockWpHooks.doAction ).toHaveBeenCalledWith(
				'matture.afterReveal',
				gate,
				'mature'
			);
		} );

		test( 'Both hooks are called in correct order', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			overlay.click();

			const calls = mockWpHooks.doAction.mock.calls;
			expect( calls[ 0 ][ 0 ] ).toBe( 'matture.beforeReveal' );
			expect( calls[ 1 ][ 0 ] ).toBe( 'matture.afterReveal' );
		} );
	} );

	describe( 'Edge cases', () => {
		test( 'Gate with no overlay element does not throw', () => {
			const gate = document.createElement( 'div' );
			gate.className = 'matture-gate';
			gate.setAttribute( 'data-mode', 'mature' );
			// No overlay element
			container.appendChild( gate );

			expect( () => {
				loadViewScript();
			} ).not.toThrow();
		} );

		test( 'Multiple gates on the page all work independently', () => {
			const gate1 = createGate( { mode: 'nsfw' } );
			const gate2 = createGate( { mode: 'spoiler' } );
			container.appendChild( gate1 );
			container.appendChild( gate2 );
			loadViewScript();

			const overlay1 = gate1.querySelector( '.matture-gate__overlay' );
			overlay1.click();

			expect( gate1.classList.contains( 'is-revealed' ) ).toBe( true );
			expect( gate2.classList.contains( 'is-revealed' ) ).toBe( false );
		} );

		test( 'Blur intensity is applied via CSS custom property', () => {
			const gate = createGate( { blur: '30' } );
			container.appendChild( gate );
			loadViewScript();

			expect( gate.style.getPropertyValue( '--matture-blur' ) ).toBe(
				'30px'
			);
		} );

		test( 'Overlay is keyboard-accessible with tabindex and role', () => {
			const gate = createGate();
			container.appendChild( gate );
			loadViewScript();

			const overlay = gate.querySelector( '.matture-gate__overlay' );
			expect( overlay.getAttribute( 'tabindex' ) ).toBe( '0' );
			expect( overlay.getAttribute( 'role' ) ).toBe( 'button' );
		} );
	} );
} );
