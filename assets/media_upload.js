jQuery(document).ready(function($) {
	
	$('.media_upload_button').click(function(e) {
		e.preventDefault();
        var $input = $(this).parent().find('input[type=hidden]');
        var $image = $(this).parent().find('img');
		var image_frame;
		if (image_frame) {
			image_frame.open();
		}
		// Define image_frame as wp.media object
		image_frame = wp.media({
			title: 'Select Media',
			multiple: false,
			library: {
				type: 'image',
			}
		});

		image_frame.on('select', function () {
			// plus other AJAX stuff to refresh the image preview
			var attachment = image_frame.state().get('selection').first().toJSON();
            $input.val(attachment.url).data('id', attachment.id);
            $image.attr('src', attachment.url);
		});

		image_frame.on('open', function () {
			// On open, get the id from the hidden input
			// and select the appropiate images in the media manager
			var selection = image_frame.state().get('selection');
			var id = $input.data('id');
			if (!id) return false;
			attachment = wp.media.attachment(id);
			attachment.fetch();
			selection.add(attachment ? [attachment] : []);

		});

		image_frame.open();
    });
	
	$('.media_delete_button').click(function(e) {
		e.preventDefault();
        var $input = $(this).parent().find('input[type=hidden]');
        var $image = $(this).parent().find('img');
        $input.val('').removeData('id');
        $image.attr('src', '');
    });
    
});
