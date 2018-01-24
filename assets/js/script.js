;( function( $ ) {
	'use strict';

	$( function() {
		var $ifi = $( '.column-rfic' );

		$ifi.on( 'click', '.js-wp-ifi__add', function() {
			open_image_uploader( this );
		} );

		$ifi.on( 'click', '.js-RapidFeaturedImageController__delete', function() {
			var $removeButton = $( this ),
				post_id = $removeButton.data( 'post_id' ),
				nonce = $removeButton.data( 'nonce' ),
				$wrapper = $removeButton.parent( '.wp-ifi' ),
				$add_button = $wrapper.find( '.js-wp-ifi__add' ),
				$imageWrapper = $wrapper.find( '.wp-ifi__figure' );

			$.post(
				WP_IFI.ajaxurl,
				{
					action: 'rfic_delete_featured_image',
					post_id: post_id,
					nonce: nonce
				}
			).done(function(res) {
				$imageWrapper.html('');
				$delete_button.hide();
				$add_button.attr({
					'data-image_id': '',
					'title': WP_IFI.btnTextNormal
				}).find('.screen-reader-text').text(WP_IFI.btnTextNormal);
			});
		});
	});

	function open_image_uploader(add_button) {
		var file_frame,
			$add_button = $(add_button),
			post_id = $add_button.data('post_id'),
			image_id = $add_button.data('image_id'),
			nonce = $add_button.data('nonce'),
			$wrapper = $add_button.parent('.RapidFeaturedImageController'),
			$delete_button = $wrapper.find('.js-RapidFeaturedImageController__delete'),
			$imageWrapper = $wrapper.find('.RapidFeaturedImageController__holder');

		if (undefined !== file_frame) {
			file_frame.open();
			return;
		}

		file_frame = wp.media.frames.file_frame = wp.media({
			title: WP_IFI.modalTitle,
			library: {
				type: 'image'
			},
			button: {
				text: WP_IFI.modalBtnTextNormal
			},
			multiple: false
		});

		file_frame.on('select', function () {
			var image_src,
				image_data = file_frame.state().get('selection').first().toJSON();

			if (image_data.id) {
				$.post(
					WP_IFI.ajaxurl,
					{
						action: 'rfic_add_featured_image',
						image_id: image_data.id,
						post_id: post_id,
						nonce: nonce
					}
				).done(function(res) {
					image_src = ( image_data.sizes.thumbnail ) ? image_data.sizes.thumbnail.url : image_data.url;
					$imageWrapper.html('<img class="RapidFeaturedImageController__image" src="' + image_src + '">');
					$add_button.attr({
						'data-image_id': image_data.id,
						'title': WP_IFI.btnTextEdit
					}).find('.screen-reader-text').text(WP_IFI.btnTextEdit);

					if ($delete_button.is(':hidden')) {
						$delete_button.show();
					}
				});
			}
		});

		file_frame.open();
	};

})(jQuery);
