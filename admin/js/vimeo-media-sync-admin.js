(function( $ ) {
	'use strict';

	function debugLog( message, data ) {
		if ( window.VimeoMediaSync && VimeoMediaSync.debug && window.console ) {
			if ( typeof data !== 'undefined' ) {
				console.log( '[Vimeo Media Sync]', message, data );
			} else {
				console.log( '[Vimeo Media Sync]', message );
			}
		}
	}

	function fetchDetailsHtml( attachmentId, refresh ) {
		if ( ! window.VimeoMediaSync ) {
			debugLog( 'VimeoMediaSync not available' );
			return $.Deferred().resolve();
		}

		debugLog( 'Fetching Vimeo details', { attachmentId: attachmentId, refresh: refresh } );
		return $.post( VimeoMediaSync.ajaxUrl, {
			action: 'vimeo_media_sync_render_details',
			nonce: VimeoMediaSync.nonce,
			post_id: attachmentId,
			refresh: refresh ? 1 : 0
		} );
	}

	function injectDetails( view, html ) {
		var $settings = view.$el.find( '.media-modal-content .attachment-info .settings' );
		if ( ! $settings.length ) {
			$settings = $( '.media-modal:visible .media-modal-content .attachment-info .settings' );
		}
		if ( ! $settings.length ) {
			debugLog( 'Settings container not found for injection' );
			return;
		}

		debugLog( 'Injecting Vimeo details' );
		$settings.next( '.vimeo-media-sync-details' ).remove();
		if ( html ) {
			$settings.after( html );
		}
	}

	function attachRefreshHandler( view ) {
		view.$el.on( 'click', '.vimeo-media-sync-details .button', function( event ) {
			event.preventDefault();
			var $button = $( this );
			var attachmentId = view.model && view.model.get( 'id' );
			if ( ! attachmentId ) {
				return;
			}

			$button.prop( 'disabled', true );
			fetchDetailsHtml( attachmentId, true ).done( function( response ) {
				if ( response && response.success ) {
					injectDetails( view, response.data.html );
				}
			} ).always( function() {
				$button.prop( 'disabled', false );
			} );
		} );
	}

	function addVimeoDetailsSection( view ) {
		var model = view.model;
		if ( ! model || 'video' !== model.get( 'type' ) ) {
			view.$el.find( '.vimeo-media-sync-details' ).remove();
			debugLog( 'Skipping non-video attachment' );
			return;
		}

		var attachmentId = model.get( 'id' );
		debugLog( 'Rendering details for attachment', attachmentId );
		fetchDetailsHtml( attachmentId, false ).done( function( response ) {
			if ( response && response.success ) {
				injectDetails( view, response.data.html );
			} else {
				debugLog( 'Failed to fetch details', response );
			}
		} );

		attachRefreshHandler( view );
	}

	function getSelectedAttachmentId() {
		if ( window.wp && wp.media && wp.media.frame && wp.media.frame.state ) {
			var state = wp.media.frame.state();
			if ( state && state.get ) {
				var selection = state.get( 'selection' );
				if ( selection && selection.first ) {
					var model = selection.first();
					if ( model ) {
						return model.get( 'id' );
					}
				}
			}
		}
		return 0;
	}

	function getAttachmentIdFromContainer( $container ) {
		var id = $container.data( 'id' );
		if ( id ) {
			return id;
		}
		var $dataNode = $container.find( '[data-id]' ).first();
		if ( $dataNode.length ) {
			return $dataNode.data( 'id' );
		}
		return 0;
	}

	function ensureModalDetails() {
		var $modal = $( '.media-modal:visible' );
		if ( ! $modal.length ) {
			debugLog( 'No modal found' );
			return;
		}

		var $settings = $modal.find( '.media-modal-content .attachment-info .settings' ).first();
		if ( ! $settings.length ) {
			debugLog( 'No settings container in modal' );
			return;
		}

		var attachmentId = getAttachmentIdFromContainer( $settings ) || getSelectedAttachmentId();
		if ( ! attachmentId ) {
			debugLog( 'No attachment id found in modal' );
			return;
		}

		if ( $modal.data( 'vimeoMediaSyncId' ) === attachmentId && $settings.next( '.vimeo-media-sync-details' ).length ) {
			return;
		}

		debugLog( 'Ensuring modal details for attachment', attachmentId );
		fetchDetailsHtml( attachmentId, false ).done( function( response ) {
			if ( response && response.success ) {
				$modal.data( 'vimeoMediaSyncId', attachmentId );
				$settings.next( '.vimeo-media-sync-details' ).remove();
				$settings.after( response.data.html );
			} else {
				debugLog( 'Modal details fetch failed', response );
			}
		} );
	}

	function wrapAttachmentDetailsView( ViewClass, assign ) {
		if ( ! ViewClass || ViewClass.__vimeoMediaSyncWrapped ) {
			return;
		}

		ViewClass.__vimeoMediaSyncWrapped = true;
		assign(
			ViewClass.extend( {
			render: function() {
				ViewClass.prototype.render.apply( this, arguments );
				addVimeoDetailsSection( this );
				return this;
			}
			} )
		);
	}

	if ( window.wp && wp.media && wp.media.view && wp.media.view.Attachment ) {
		if ( wp.media.view.Attachment.Details ) {
			wrapAttachmentDetailsView( wp.media.view.Attachment.Details, function( Wrapped ) {
				wp.media.view.Attachment.Details = Wrapped;
			} );
		}
		if ( wp.media.view.Attachment.Details && wp.media.view.Attachment.Details.TwoColumn ) {
			wrapAttachmentDetailsView( wp.media.view.Attachment.Details.TwoColumn, function( Wrapped ) {
				wp.media.view.Attachment.Details.TwoColumn = Wrapped;
			} );
		}
	}

	$( document ).on( 'click', '.media-modal .vimeo-media-sync-details .button', function( event ) {
		event.preventDefault();
		var $button = $( this );
		var $modal = $button.closest( '.media-modal' );
		var attachmentId = getSelectedAttachmentId();
		if ( ! attachmentId ) {
			debugLog( 'No attachment id for refresh' );
			return;
		}

		debugLog( 'Refreshing Vimeo details for attachment', attachmentId );
		$button.prop( 'disabled', true );
		fetchDetailsHtml( attachmentId, true ).done( function( response ) {
			if ( response && response.success ) {
				var $settings = $modal.find( '.media-modal-content .attachment-info .settings' ).first();
				if ( $settings.length ) {
					$settings.next( '.vimeo-media-sync-details' ).remove();
					$settings.after( response.data.html );
				} else {
					debugLog( 'Settings container missing during refresh' );
				}
			} else {
				debugLog( 'Refresh failed', response );
			}
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );

	$( document ).on( 'click', '.vimeo-media-sync-details .vimeo-media-sync-refresh', function( event ) {
		var $button = $( this );
		if ( $button.closest( '.media-modal' ).length ) {
			return;
		}

		event.preventDefault();
		var attachmentId = parseInt( $button.data( 'post-id' ), 10 );
		if ( ! attachmentId ) {
			debugLog( 'No attachment id for refresh' );
			return;
		}

		$button.prop( 'disabled', true );
		fetchDetailsHtml( attachmentId, true ).done( function( response ) {
			if ( response && response.success ) {
				$button.closest( '.vimeo-media-sync-details' ).replaceWith( response.data.html );
			} else {
				debugLog( 'Refresh failed', response );
			}
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );

	$( document ).on( 'click', '.vimeo-media-sync-row', function() {
		var $button = $( this );
		var $row = $button.closest( 'tr' );
		var postId = $button.data( 'post-id' );
		if ( ! postId || ! window.VimeoMediaSync ) {
			return;
		}

		$button.prop( 'disabled', true );
		if ( $row.length ) {
			$row.find( '[data-status]' ).text( 'Queued' );
		}
		$.post( VimeoMediaSync.ajaxUrl, {
			action: 'vimeo_media_sync_sync_attachment',
			nonce: VimeoMediaSync.syncNonce,
			post_id: postId
		} ).done( function( response ) {
			if ( response && response.success ) {
				if ( $row.length && response.data && response.data.info ) {
					var status = response.data.info.status || 'queued';
					$row.find( '[data-status]' ).text( status );
				}
				$button.text( 'Queued' );
			}
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );

	$( document ).on( 'click', '.vimeo-media-sync-all', function() {
		var $button = $( this );
		if ( ! window.VimeoMediaSync ) {
			return;
		}

		$button.prop( 'disabled', true );
		$( '[data-status]' ).text( 'Queued' );
		$.post( VimeoMediaSync.ajaxUrl, {
			action: 'vimeo_media_sync_sync_missing',
			nonce: VimeoMediaSync.syncMissingNonce
		} ).done( function( response ) {
			if ( response && response.success ) {
				$button.text( 'Queued (' + response.data.synced + ')' );
			}
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );

	$( document ).on( 'click', '.vimeo-media-sync-clear-meta', function() {
		var $button = $( this );
		if ( ! window.VimeoMediaSync ) {
			return;
		}

		var confirmMessage = $button.data( 'confirm' ) || 'Clear Vimeo metadata for all video attachments?';
		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		var $status = $button.closest( 'p' ).find( '.vimeo-media-sync-clear-meta-status' );
		$button.prop( 'disabled', true );
		if ( $status.length ) {
			$status.text( 'Clearing metadata...' );
		}

		$.post( VimeoMediaSync.ajaxUrl, {
			action: 'vimeo_media_sync_clear_all_metadata',
			nonce: VimeoMediaSync.clearMetaNonce
		} ).done( function( response ) {
			if ( response && response.success ) {
				var cleared = response.data && response.data.cleared ? response.data.cleared : 0;
				if ( $status.length ) {
					$status.text( 'Cleared metadata for ' + cleared + ' attachment(s).' );
				}
			} else if ( $status.length ) {
				$status.text( 'Unable to clear metadata.' );
			}
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );

	if ( window.wp && wp.media && wp.media.frame ) {
		wp.media.frame.on( 'open', ensureModalDetails );
		wp.media.frame.on( 'close', ensureModalDetails );
		wp.media.frame.on( 'selection:toggle', ensureModalDetails );
		wp.media.frame.on( 'selection:single', ensureModalDetails );
		wp.media.frame.on( 'selection:unsingle', ensureModalDetails );
		wp.media.frame.on( 'content:render:details', ensureModalDetails );
		wp.media.frame.on( 'content:render:edit', ensureModalDetails );
	}
})( jQuery );
