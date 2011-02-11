/*
 * ImageCropper for Symphony CMS
 *
 */
;(function ($) {

	$(document).ready(function() {
		Symphony.Language.add({
			'No image found. Please upload an image and save entry.': false,
			'This image is too small to get cropped with the current settings.': false,
			'Show URL': false,
			'Hide URL': false
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
				$clear_link = $el.find('a.imagecropper_clear'),
				$preview_toggle = $el.find('a.imagecropper_preview_toggle'),
				$preview_fieldset = $el.find('fieldset.imagecropper_preview'),
				$preview_url_input = $preview_fieldset.find('input[name="' + field_prefix + '\\[preview_url\\]"]'),
				$preview_scale = $preview_fieldset.find('.imagecropper_scale'),
				$preview_scale_input = $preview_fieldset.find('input[name="' + field_prefix + '\\[preview_scale\\]"]').hide(),
				$preview_scale_slider = $preview_fieldset.find('.imagecropper_scale_slider'),
				$preview_link = $preview_fieldset.find('a.imagecropper_preview_link'),
				$upload_field = $('#field-' + o.related_field_id).find('input'),
				$image_link = $upload_field.prev(),
				$remove_link = $upload_field.next(),
				cropper,
				aspect_ratio = 0,
				image_path = $upload_field.val(),
				image = null,
				box_width = o.box_width,
				crop_coords = [Number($x1_input.val()), Number($y1_input.val()), Number($x2_input.val()), Number($y2_input.val())];
			
			if ($image_link.length) {
				box_width = $el.width();
				image = new Image();
				image.src = $image_link.attr('href');
				
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

					checkMinDimension();
				});
			} else {
				$el.find('.group').hide();
				$el.find('fieldset').hide();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
				clearCoords();
			};

			$clear_link.click(function(e) {
				e.preventDefault();
				cropper.release();
				clearCoords();
			});

			$preview_toggle.toggle(function(e) {
				e.preventDefault();
				$preview_fieldset.slideDown(200);
				$(this).text(Symphony.Language.get('Hide URL'));
			}, function(e) {
				e.preventDefault();
				$preview_fieldset.slideUp(200);
				$(this).text(Symphony.Language.get('Show URL'));
			});
			
			$preview_link.click(function(e) {
				e.preventDefault();
				var scale = $preview_scale_input.val() / 100,
					width = $width_input.val() * scale,
					height = $height_input.val() * scale;
				
				window.open($preview_url_input.val(), 'imagecropper_preview', 'height=' + height + ',width=' + width);
			});
			
			$preview_scale_slider.slider({
				value: $preview_scale_input.val(),
				min: 0,
				max: 100,
				create: function() {
					$preview_scale.text($preview_scale_input.val() + '%');
				},
				slide: function(event, ui) {
					var c = cropper.tellSelect();
					
					$preview_scale_input.val(ui.value);
					$preview_scale.text(ui.value + '%');
					showCoords(c);
				}
			});
			
			$remove_link.click(function() {
				cropper.destroy();
				clearCoords();
				$el.find('.group').hide();
				$el.find('fieldset').hide();
				$el.find('img').remove();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
			});
			
			// private methods
			function showCoords(c) {
				var scale = $preview_scale_input.val() / 100,
					scaled_width = Math.round(c.w * scale),
					scaled_height = Math.round(c.h * scale);
				
				$cropped_input.val('yes');
				$x1_input.val(c.x);
				$y1_input.val(c.y);
				$x2_input.val(c.x2);
				$y2_input.val(c.y2);
				$width_input.val(c.w);
				$height_input.val(c.h);
				$ratio_input.val(Math.round(100 * c.w/c.h)/100);
				$preview_url_input.val(Symphony.Context.get('root') + '/image/4/' + c.w + '/' + c.h + '/' + c.x + '/' + c.y + '/' + scaled_width + '/' + scaled_height + image_path);
			};
			
			function clearCoords(){
				$cropped_input.val('no');
				$x1_input.val('');
				$y1_input.val('');
				$x2_input.val('');
				$y2_input.val('');
				$width_input.val('');
				$height_input.val('');
				$ratio_input.val('');
				$preview_url_input.val('');
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
					if (!$field.find('#error').length) {
						$el.wrap('<div id="error" class="invalid"></div>');
						$el.after('<p>' + Symphony.Language.get('This image is too small to get cropped with the current settings.') + '</p>');
					};
					cropper.disable();
					cropper.release();
					clearCoords();
				};
			};
			
			function nothing () {
				return false;
			}
		});
	};
	
})(jQuery.noConflict());
