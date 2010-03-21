/*
 * ImageCropper for Symphony CMS
 *
 */

;(function ($) {
	$.fn.imageCropper = function (options) {
		return this.each(function() {
			var el = this;
			var $el = $(this);
			options = options || {};
			var opts = $.extend({}, $.fn.imageCropper.defaults, options);
			var
				jcrop_api,
				$select_ratio = $('#imagecropper_'+opts.field_id+'_ratios'),
				aspect_ratio = null,
				image = null,
				field_name = opts.related_field_name,
				upload_field = $('input[name="fields['+field_name+']"]'),
				image_link = upload_field.prev(),
				box_width = options.box_width,
				crop_coords = [];
			var show_coords = function(c) {
				$('.imagecropper_cropped',el).val('yes');
				$('.imagecropper_x1',el).val(c.x);
				$('.imagecropper_y1',el).val(c.y);
				$('.imagecropper_x2',el).val(c.x2);
				$('.imagecropper_y2',el).val(c.y2);
				$('.imagecropper_width',el).val(c.w);
				$('.imagecropper_height',el).val(c.h);
				$('.imagecropper_free_ratio',el).val(Math.round(100 * c.w/c.h)/100);
			};
			
			crop_coords = [Number($('.imagecropper_x1',el).val()), Number($('.imagecropper_y1',el).val()), Number($('.imagecropper_x2',el).val()), Number($('.imagecropper_y2',el).val())];
			
			if (image_link.length) {
				box_width = $el.width();
				image = new Image();
				image.src = image_link.attr('href');
				
				aspect_ratio = opts.ratio;
				if (aspect_ratio == 'select') {
					if ($select_ratio.length) {
						var calculated_ratio = Math.round(100 * Number($('.imagecropper_width',el).val()) / Number($('.imagecropper_height',el).val()));
						aspect_ratio = $('option:selected',$select_ratio[0]).val();
					};
				};
				
				$(image).load(function() {
					if (crop_coords.toString() == '0,0,0,0') {
						$(image).appendTo($el);
						jcrop_api = $.Jcrop(image, {
							aspectRatio: aspect_ratio,
							boxWidth: box_width,
							minSize: opts.minSize,
							onChange: show_coords,
							onSelect: show_coords
						});
					} else {
						$(image).appendTo($el);
						jcrop_api = $.Jcrop(image, {
							aspectRatio: aspect_ratio,
							boxWidth: box_width,
							minSize: opts.minSize,
							setSelect: crop_coords,
							onChange: show_coords,
							onSelect: show_coords
						});
					};

					if ($select_ratio.length) {
						$select_ratio.change(function() {
							var selected_ratio = parseFloat($(this).val());
							jcrop_api.setOptions({
								aspectRatio: selected_ratio
							});
							jcrop_api.focus();
						});
					};

					$('.imagecropper_clear', el).click(function(e) {
						e.preventDefault();
						jcrop_api.release();
						$('.imagecropper_cropped',el).val('no');
						$('.imagecropper_x1',el).val('');
						$('.imagecropper_y1',el).val('');
						$('.imagecropper_x2',el).val('');
						$('.imagecropper_y2',el).val('');
						$('.imagecropper_width',el).val('');
						$('.imagecropper_height',el).val('');
						$('.imagecropper_free_ratio',el).val('');
					});

				});
			} else {
				$('.group', $el).hide();
				$el.append('No image found. Please upload an image and save entry.');
			};

		});
	};
	

	function debug($obj) {
		if (window.console && window.console.log)
			window.console.log($obj);
	};

	$.fn.imageCropper.defaults = {
	};
	
})(jQuery.noConflict());


// A handler to kill the action (from Jcrop demo)
function nothing(e) {
	e.stopPropagation();
	e.preventDefault();
	return false;
};
