;(function ($) {
	'use strict';

	$(document).ready(function() {
		var $RFIC_column = $('.column-rfic');

		$RFIC_column.on('click', '.js-RapidFeaturedImageController__add', function() {
			open_image_uploader(this);
		});

		$RFIC_column.on('click', '.js-RapidFeaturedImageController__delete', function() {
			var $delete_button = $(this),
				post_id = $delete_button.data('post_id'),
				nonce = $delete_button.data('nonce'),
				$wrapper = $delete_button.parent('.RapidFeaturedImageController'),
				$add_button = $wrapper.find('.js-RapidFeaturedImageController__add'),
				$image_holder = $wrapper.find('.RapidFeaturedImageController__holder');

			$.post(
				RFIC.ajaxurl,
				{
					action: 'rfic_delete_featured_image',
					post_id: post_id,
					nonce: nonce
				}
			).done(function(res) {
				$image_holder.html('');
				$delete_button.hide();
				$add_button.attr({
					'data-image_id': '',
					'title': RFIC.btnTextNormal
				}).find('.screen-reader-text').text(RFIC.btnTextNormal);
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
			$image_holder = $wrapper.find('.RapidFeaturedImageController__holder');

		if (undefined !== file_frame) {
			file_frame.open();
			return;
		}

		file_frame = wp.media.frames.file_frame = wp.media({
			title: RFIC.modalTitle,
			library: {
				type: 'image'
			},
			button: {
				text: RFIC.modalBtnTextNormal
			},
			multiple: false
		});

		file_frame.on('select', function () {
			var image_src,
				image_data = file_frame.state().get('selection').first().toJSON();

			if (image_data.id) {
				$.post(
					RFIC.ajaxurl,
					{
						action: 'rfic_add_featured_image',
						image_id: image_data.id,
						post_id: post_id,
						nonce: nonce
					}
				).done(function(res) {
					image_src = ( image_data.sizes.thumbnail ) ? image_data.sizes.thumbnail.url : image_data.url;
					$image_holder.html('<img class="RapidFeaturedImageController__image" src="' + image_src + '">');
					$add_button.attr({
						'data-image_id': image_data.id,
						'title': RFIC.btnTextEdit
					}).find('.screen-reader-text').text(RFIC.btnTextEdit);

					if ($delete_button.is(':hidden')) {
						$delete_button.show();
					}
				});
			}
		});

		file_frame.open();
	};

})(jQuery);
