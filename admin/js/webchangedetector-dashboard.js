/**
 * Dashboard lazy-loaded cards for the WebChange Detector plugin.
 *
 * On DOMReady, finds every .wcd-card[data-lazy-action] element and replaces its
 * content with the HTML returned by the named admin-ajax action. Each card is
 * fetched independently, so one failing request never blocks the others. Used by
 * the "Latest Detected Changes" and "Recently AI-Cleared" cards, both scoped to
 * the current website on the server side.
 */
( function ( $ ) {
	'use strict';

	function loadDashboardLazyCard( $card ) {
		var action = $card.data( 'lazy-action' );
		if ( ! action || typeof wcdAjaxData === 'undefined' ) {
			return;
		}

		var $content = $card.find( '.wcd-card-content' );

		$.ajax( {
			url: wcdAjaxData.ajax_url,
			type: 'POST',
			data: {
				action: action,
				nonce: wcdAjaxData.nonce,
				wcd_blog_id: wcdAjaxData.wcd_blog_id || ''
			}
		} )
			.done( function ( response ) {
				if ( response && typeof response === 'string' && response.length > 0 ) {
					$card.html( response );
				} else {
					showRetry( $content );
				}
			} )
			.fail( function () {
				showRetry( $content );
			} );
	}

	function showRetry( $content ) {
		$content.html(
			'<p class="wcd-stat-empty">' +
			'<a href="javascript:void(0);" class="wcd-lazy-retry">Could not load this section. Retry &rarr;</a>' +
			'</p>'
		);
	}

	// Manual "Start the tour" trigger on the Getting Started card.
	$( document ).on( 'click', '.wcd-start-wizard', function ( e ) {
		e.preventDefault();
		if ( typeof window.wcdStartWizard === 'function' ) {
			window.wcdStartWizard();
		}
	} );

	// Retry handler for any lazy card that errored out.
	$( document ).on( 'click', '.wcd-lazy-retry', function () {
		var $card = $( this ).closest( '.wcd-card[data-lazy-action]' );
		if ( $card.length ) {
			$card.find( '.wcd-card-content' ).html(
				'<div class="wcd-lazy-skeleton">' +
				'<div class="wcd-lazy-skeleton-bar"></div>' +
				'<div class="wcd-lazy-skeleton-bar wcd-lazy-skeleton-bar-short"></div>' +
				'<div class="wcd-lazy-skeleton-bar"></div>' +
				'</div>'
			);
			loadDashboardLazyCard( $card );
		}
	} );

	$( function () {
		$( '.wcd-card[data-lazy-action]' ).each( function () {
			loadDashboardLazyCard( $( this ) );
		} );
	} );
} )( jQuery );
