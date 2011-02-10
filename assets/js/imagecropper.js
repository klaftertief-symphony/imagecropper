/*
 * ImageCropper for Symphony CMS
 *
 */
;(function ($) {

	$(document).ready(function() {
		Symphony.Language.add({
			'No image found. Please upload an image and save entry.': false,
			'This image is too small to get cropped with the current settings.': false
		});

		$('.imagecropper').each(function(index) {
			var $this = $(this),
				options = {
					field_name: $this.data('field_name'),
					field_id: $this.data('field_id'),
					related_field_id: $this.data('related_field_id'),
					ratio: $this.data('ratio'),
					minSize: $this.data('min_size')
				};
				
			$this.imageCropper(options);
		});
	});

	// imagecroppr plugin
	$.fn.imageCropper = function (options) {
		return this.each(function() {
			// initialize some variables
			var o = options || {},
				el = this,
				$el = $(this),
				$field = $el.closest('.field-imagecropper'),
				field_prefix = o.field_name.replace(/([\[\]])/g, '\\$1'),
				$cropped_input = $field.find('input[name="' + field_prefix + '\\[cropped\\]"]'),
				$x1_input = $field.find('input[name="' + field_prefix + '\\[x1\\]"]'),
				$y1_input = $field.find('input[name="' + field_prefix + '\\[y1\\]"]'),
				$x2_input = $field.find('input[name="' + field_prefix + '\\[x2\\]"]'),
				$y2_input = $field.find('input[name="' + field_prefix + '\\[y2\\]"]'),
				$width_input = $field.find('input[name="' + field_prefix + '\\[width\\]"]'),
				$height_input = $field.find('input[name="' + field_prefix + '\\[height\\]"]'),
				$ratio_input = $field.find('input[name="' + field_prefix + '\\[ratio\\]"]'),
				$ratio_select = $el.find('select[name="' + field_prefix + '\\[ratio\\]"]'),
				cropper,
				aspect_ratio = 0,
				image = null,
				upload_field = $('#field-' + o.related_field_id).find('input'),
				image_link = upload_field.prev(),
				remove_link = upload_field.next(),
				box_width = o.box_width,
				crop_coords = [Number($x1_input.val()), Number($y1_input.val()), Number($x2_input.val()), Number($y2_input.val())];
			
			if (image_link.length) {
				box_width = $el.width();
				image = new Image();
				image.src = image_link.attr('href');
				
				aspect_ratio = o.ratio;
				if (aspect_ratio == 'select') {
					if ($ratio_select.length) {
						aspect_ratio = $ratio_select.val();
					};
				};
				aspect_ratio = Number(aspect_ratio);
				
				$(image).load(function() {
					if (crop_coords.toString() == '0,0,0,0') {
						$(image).appendTo($el);
						cropper = $.Jcrop(image, {
							aspectRatio: aspect_ratio,
							boxWidth: box_width,
							minSize: o.minSize,
							onChange: showCoords,
							onSelect: showCoords
						});
					} else {
						$(image).appendTo($el);
						cropper = $.Jcrop(image, {
							aspectRatio: aspect_ratio,
							boxWidth: box_width,
							minSize: o.minSize,
							setSelect: crop_coords,
							onChange: showCoords,
							onSelect: showCoords
						});
					};

					if ($ratio_select.length) {
						$ratio_select.change(function() {
							aspect_ratio = parseFloat($(this).val());
							cropper.setOptions({
								aspectRatio: aspect_ratio
							});
							cropper.focus();
							checkMinDimension();
						});
					};

					$('.imagecropper_clear', el).click(function(e) {
						e.preventDefault();
						cropper.release();
						clear_coords();
					});

					checkMinDimension();
				});
			} else {
				$('.group', $el).hide();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
				clear_coords();
			};

			$(remove_link).click(function() {
				cropper.destroy();
				clearCoords();
				$('>.group',el).hide();
				$('>img',el).remove();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
			});
			
			// private methods
			function showCoords(c) {
				$cropped_input.val('yes');
				$x1_input.val(c.x);
				$y1_input.val(c.y);
				$x2_input.val(c.x2);
				$y2_input.val(c.y2);
				$width_input.val(c.w);
				$height_input.val(c.h);
				$ratio_input.val(Math.round(100 * c.w/c.h)/100);
			};
			
			function clearCoords(){
				$cropped_input.val('no');
				$x1_input.val('');
				$y1_input.val('');
				$x2_input.val('');
				$y2_input.val('');
				$width_input.val('');
				$height_inputval('');
				$ratio_input.val('');
			};
			function checkMinDimension(){
				var tooSmall;
				
				if (o.minSize[0] == 0 && o.minSize[1] == 0) {
					tooSmall = false;
				}
				else if (o.minSize[0] == 0) {
					tooSmall = (image.width < o.minSize[1] * aspect_ratio) || (image.height < o.minSize[1]);
				}
				else if (o.minSize[1] == 0) {
					tooSmall = (image.width < o.minSize[0]) || (image.height < (aspect_ratio == 0 ? 0 : o.minSize[0] / aspect_ratio));
				}
				else {
					tooSmall =  (image.width < o.minSize[0]) || (image.height < o.minSize[1]);
				};
				
				if (tooSmall) {
					if (!$('> .invalid', el).length) {
						$el.prepend('<div class="invalid">' + Symphony.Language.get('This image is too small to get cropped with the current settings.') + '</div>');
					};
					cropper.disable();
					cropper.release();
					clearCoords();
				}
				else {
					$('> .invalid', el).remove();
					cropper.enable();
				};
			};
		});
	};
	
})(jQuery.noConflict());
