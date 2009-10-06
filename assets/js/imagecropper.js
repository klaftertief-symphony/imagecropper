/*
 * ImageCropper for Symphony CMS
 *
 */

;(function ($) {
	// Private class variables
	var ver = '0.1';

	// Chained function to create an imageCropper.
	$.fn.imageCropper = function (options) {
		return this.each(function() {
			var $el = $(this);
			// debug(options);
			options = options || {};
			var opts = $.extend({}, $.fn.imageCropper.defaults, options);
			
			// debug(opts);
			
			var
				jcrop_api,
				$select_ratio = $('#imagecropper_'+opts.field_id+'_ratios'),
				ratio = null,
				image = null,
				field_name = opts.related_field_name,
				upload_field = $('input[name="fields['+field_name+']"]'),
				image_link = upload_field.prev(),
				box_width = options.box_width,
				crop_coords = [];
				
			var show_coords = function (c) {
				$('.imagecropper_x1',$el[0]).val(c.x);
				$('.imagecropper_y1',$el[0]).val(c.y);
				$('.imagecropper_x2',$el[0]).val(c.x2);
				$('.imagecropper_y2',$el[0]).val(c.y2);
				$('.imagecropper_width',$el[0]).val(c.w);
				$('.imagecropper_height',$el[0]).val(c.h);
			};
			
			crop_coords = [Number($('.imagecropper_x1',$el[0]).val()), Number($('.imagecropper_y1',$el[0]).val()), Number($('.imagecropper_x2',$el[0]).val()), Number($('.imagecropper_y2',$el[0]).val())];
			

			if (image_link.length) {
				box_width = $el.width();
				image = new Image();
				image.src = image_link.attr('href');
				
				ratio = opts.ratio;
				if (opts.ratio == 'select') {
					if ($select_ratio.length) {
						var calculated_ratio = Math.round(100 * Number($('.imagecropper_width',$el[0]).val()) / Number($('.imagecropper_height',$el[0]).val()));
						// $('option',$select_ratio[0]).each(function() {
						// 	if (Math.round(100*$(this).val()) == calculated_ratio) {
						// 		$(this).attr('selected', 'selected');
						// 	};
						// });
						ratio = $('option:selected',$select_ratio[0]).val();
					};
				// } else if (isNaN(parseFloat(opts.ratio))) {
				// 	ratio = null;
				};

				$(image).load(function() {
					if (crop_coords.toString() == '0,0,0,0') {
						$(image).appendTo($el);
						jcrop_api = $.Jcrop(image, {
							boxWidth: box_width,
							aspectRatio: ratio,
							minSize: opts.minSize,
							maxSize: opts.maxSize,
							onChange: show_coords,
							onSelect: show_coords
						});
					} else {
						$(image).appendTo($el);
						jcrop_api = $.Jcrop(image, {
							boxWidth: box_width,
							aspectRatio: ratio,
							minSize: opts.minSize,
							maxSize: opts.maxSize,
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

					$('.imagecropper_modal', $el[0]).click(function (e) {
						e.preventDefault();
						$('<p>TODO</p>').modal();
					});

					$('.imagecropper_clear', $el[0]).click(function(e) {
						e.preventDefault();
						jcrop_api.release();
						$('.imagecropper_x1',$el[0]).val('');
						$('.imagecropper_y1',$el[0]).val('');
						$('.imagecropper_x2',$el[0]).val('');
						$('.imagecropper_y2',$el[0]).val('');
						$('.imagecropper_width',$el[0]).val('');
						$('.imagecropper_height',$el[0]).val('');
					});

				});
			};

		});
	};
	

	// private function for debugging
	function debug($obj) {
		if (window.console && window.console.log)
			window.console.log($obj);
	};

	$.fn.imageCropper.ver = function() { return ver; };

	 // ImageCropper default options
	$.fn.imageCropper.defaults = {
	};
	
})(jQuery.noConflict());


// A handler to kill the action (from Jcrop demo)
function nothing(e) {
	e.stopPropagation();
	e.preventDefault();
	return false;
};