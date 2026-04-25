( function () {
	'use strict';

	var PICKER_SELECTOR = '.liga-icon-picker';

	function getEmptyPreviewMarkup() {
		return '<span class="material-symbols-outlined liga-icon-picker__glyph" aria-hidden="true">hide_source</span>';
	}

	function setPickerSelection( picker, selectedIcon ) {
		var input = picker.querySelector( '.liga-icon-picker__value' );
		var options = picker.querySelectorAll( '.liga-icon-picker__option' );
		var previewIcon = picker.querySelector( '.liga-icon-picker__preview-icon' );
		var previewText = picker.querySelector( '.liga-icon-picker__preview-text' );
		var selectedLabel = '';
		var selectedGlyphMarkup = '';

		if ( ! input ) {
			return;
		}

		input.value = selectedIcon;

		options.forEach( function ( option ) {
			var optionIcon = option.getAttribute( 'data-icon' ) || '';
			var isSelected = optionIcon === selectedIcon;

			option.classList.toggle( 'is-selected', isSelected );
			option.setAttribute( 'aria-pressed', isSelected ? 'true' : 'false' );

			if ( isSelected ) {
				selectedLabel = option.getAttribute( 'data-label' ) || '';
				if ( option.querySelector( '.liga-icon-picker__glyph' ) ) {
					selectedGlyphMarkup = option.querySelector( '.liga-icon-picker__glyph' ).outerHTML;
				}
			}
		} );

		if ( previewIcon ) {
			previewIcon.innerHTML = selectedGlyphMarkup || getEmptyPreviewMarkup();
			previewIcon.classList.toggle( 'is-empty', ! selectedIcon || ! selectedGlyphMarkup );
		}

		if ( previewText ) {
			previewText.textContent = selectedLabel || 'Sin icono';
		}
	}

	function bindPickerSearch( picker ) {
		var searchInput = picker.querySelector( '.liga-icon-picker__search' );
		var options = picker.querySelectorAll( '.liga-icon-picker__option--icon' );

		if ( ! searchInput || ! options.length ) {
			return;
		}

		searchInput.addEventListener( 'input', function () {
			var query = searchInput.value.trim().toLowerCase();

			options.forEach( function ( option ) {
				var iconValue = ( option.getAttribute( 'data-icon' ) || '' ).toLowerCase();
				var iconLabel = ( option.getAttribute( 'data-label' ) || '' ).toLowerCase();
				var isMatch = ! query || iconValue.indexOf( query ) !== -1 || iconLabel.indexOf( query ) !== -1;

				option.hidden = ! isMatch;
			} );
		} );
	}

	function bindPickerOptions( picker ) {
		var input = picker.querySelector( '.liga-icon-picker__value' );
		var options = picker.querySelectorAll( '.liga-icon-picker__option' );

		if ( ! input || ! options.length ) {
			return;
		}

		options.forEach( function ( option ) {
			option.addEventListener( 'click', function () {
				var selectedIcon = option.getAttribute( 'data-icon' ) || '';
				setPickerSelection( picker, selectedIcon );
			} );
		} );

		setPickerSelection( picker, input.value || '' );
	}

	function initPicker( picker ) {
		if ( picker.dataset.ligaPickerReady === '1' ) {
			return;
		}

		picker.dataset.ligaPickerReady = '1';
		bindPickerOptions( picker );
		bindPickerSearch( picker );
	}

	function initAllPickers( root ) {
		var scope = root || document;
		var pickers = scope.querySelectorAll( PICKER_SELECTOR );

		pickers.forEach( function ( picker ) {
			initPicker( picker );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initAllPickers( document );

		if ( 'MutationObserver' in window ) {
			var observer = new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					mutation.addedNodes.forEach( function ( node ) {
						if ( ! node || node.nodeType !== 1 ) {
							return;
						}

						if ( node.matches && node.matches( PICKER_SELECTOR ) ) {
							initPicker( node );
							return;
						}

						initAllPickers( node );
					} );
				} );
			} );

			observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}
	} );
}() );
