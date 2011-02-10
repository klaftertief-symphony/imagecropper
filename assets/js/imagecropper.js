/*
 * ImageCropper for Symphony CMS
 *
 */
Symphony.Language.add({
		'No image found. Please upload an image and save entry.': false,
		'This image is too small to get cropped with the current settings.': false
	});

;(function ($) {

	$.fn.imageCropper = function (options) {
		return this.each(function() {
			var el = this;
			var $el = $(this);
			var $field = $el.parent();
			options = options || {};
			var opts = $.extend({}, $.fn.imageCropper.defaults, options);
			var
				jcrop_api,
				$select_ratio = $('#imagecropper_'+opts.field_id+'_ratios'),
				aspect_ratio = 0,
				image = null,
				field_name = opts.related_field_name,
				upload_field = $('input[name="fields['+field_name+']"]'),
				image_link = upload_field.prev(),
				remove_link = upload_field.next(),
				box_width = options.box_width,
				crop_coords = [];
			var show_coords = function(c) {
				$field.find('.imagecropper_cropped').val('yes');
				$field.find('.imagecropper_x1').val(c.x);
				$field.find('.imagecropper_y1').val(c.y);
				$field.find('.imagecropper_x2').val(c.x2);
				$field.find('.imagecropper_y2').val(c.y2);
				$field.find('.imagecropper_width').val(c.w);
				$field.find('.imagecropper_height').val(c.h);
				$field.find('.imagecropper_free_ratio').val(Math.round(100 * c.w/c.h)/100);
			};
			var clear_coords = function(){
				$field.find('.imagecropper_cropped').val('no');
				$field.find('.imagecropper_x1').val('');
				$field.find('.imagecropper_y1').val('');
				$field.find('.imagecropper_x2').val('');
				$field.find('.imagecropper_y2').val('');
				$field.find('.imagecropper_width').val('');
				$field.find('.imagecropper_height').val('');
				$field.find('.imagecropper_free_ratio').val('');
			};
			var checkMinDimension = function(){
				var tooSmall;
				
				if (opts.minSize[0] == 0 && opts.minSize[1] == 0) {
					tooSmall = false;
				}
				else if (opts.minSize[0] == 0) {
					tooSmall = (image.width < opts.minSize[1] * aspect_ratio) || (image.height < opts.minSize[1]);
				}
				else if (opts.minSize[1] == 0) {
					tooSmall = (image.width < opts.minSize[0]) || (image.height < (aspect_ratio == 0 ? 0 : opts.minSize[0] / aspect_ratio));
				}
				else {
					tooSmall =  (image.width < opts.minSize[0]) || (image.height < opts.minSize[1]);
				};
				
				if (tooSmall) {
					if (!$('> .invalid', el).length) {
						$el.prepend('<div class="invalid">' + Symphony.Language.get('This image is too small to get cropped with the current settings.') + '</div>');
					};
					jcrop_api.disable();
					jcrop_api.release();
					clear_coords();
				}
				else {
					$('> .invalid', el).remove();
					jcrop_api.enable();
				};
			};
			
			$(remove_link).click(function() {
				jcrop_api.destroy();
				clear_coords();
				$('>.group',el).hide();
				$('>img',el).remove();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
			});
			
			crop_coords = [Number($field.find('.imagecropper_x1').val()), Number($field.find('.imagecropper_y1').val()), Number($field.find('.imagecropper_x2').val()), Number($field.find('.imagecropper_y2').val())];
			
			if (image_link.length) {
				box_width = $el.width();
				image = new Image();
				image.src = image_link.attr('href');
				
				aspect_ratio = opts.ratio;
				if (aspect_ratio == 'select') {
					if ($select_ratio.length) {
						aspect_ratio = $select_ratio.val();
					};
				};
				aspect_ratio = Number(aspect_ratio);
				
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
							aspect_ratio = parseFloat($(this).val());
							jcrop_api.setOptions({
								aspectRatio: aspect_ratio
							});
							jcrop_api.focus();
							checkMinDimension();
						});
					};

					$('.imagecropper_clear', el).click(function(e) {
						e.preventDefault();
						jcrop_api.release();
						clear_coords();
					});

					checkMinDimension();
				});
			} else {
				$('.group', $el).hide();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
				clear_coords();
			};

		});
	};
	
	$.fn.imageCropper.defaults = {
	};
	
	$(document).ready(function() {
		$('.imagecropper').each(function(index) {
			var $this = $(this),
				options = {
					field_id: $this.data('field_id'),
					related_field_id: $this.data('related_field_id'),
					related_field_name: $this.data('related_field_name'),
					ratio: $this.data('ratio'),
					minSize: $this.data('minSize')
				};
				
			$this.imageCropper(options);
		});
	});

})(jQuery.noConflict());
