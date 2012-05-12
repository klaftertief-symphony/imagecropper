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
					min_size: $this.data('min_size'),
					image_width: $this.data('image_width'),
					image_height: $this.data('image_height'),
					image_file: $this.data('image_file')
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
				$preview_link = $el.find('a.imagecropper_preview_link'),
				$preview_fieldset = $el.find('fieldset.imagecropper_preview'),
				$preview_url_input = $preview_fieldset.find('input[name="' + field_prefix + '\\[preview_url\\]"]'),
				$preview_scale = $preview_fieldset.find('.imagecropper_scale'),
				$preview_scale_input = $preview_fieldset.find('input[name="' + field_prefix + '\\[preview_scale\\]"]').hide(),
				$preview_scale_slider = $preview_fieldset.find('.imagecropper_scale_slider'),
				$upload_field = $('#field-' + o.related_field_id).find('input'),
				$image_link = $upload_field.prev(),
				$remove_link = $upload_field.next(),
				cropper,
				aspect_ratio = o.ratio,
				$image,
				image_path,
				box_width = $el.width(),
				resize_width,
				resize_height,
				crop_coords = [Number($x1_input.val()), Number($y1_input.val()), Number($x2_input.val()), Number($y2_input.val())];

			if ($image_link.length) {
				createCropper();
			} else {
				hideCropper();
			};

			$ratio_select.change(function() {
				aspect_ratio = parseFloat($(this).val());
				cropper.setOptions({
					aspectRatio: aspect_ratio
				});
				cropper.focus();
				checkMinDimension();
			});

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
				destroyCropper();
			});

			// private methods
			function createCropper() {
				if (o.image_width > box_width) {
					resize_width = Math.floor(box_width / 50) * 50;
					resize_height = Math.round(resize_width * o.image_height / o.image_width);
					image_path = Symphony.Context.get('root') + '/image/1/' + resize_width + '/' + resize_height + o.image_file;
					$image = $('<img width="' + resize_width + '" height="' + resize_height + '" src="' + image_path + '"/>');
				} else {
					image_path = Symphony.Context.get('root') + '/workspace' + o.image_file;
					$image = $('<img width="' + o.image_width + '" height="' + o.image_height + '" src="' + image_path + '"/>');
				};

				$image.appendTo($el);

				if (aspect_ratio == 'select') {
					if ($ratio_select.length) {
						aspect_ratio = $ratio_select.val();
					};
				};
				aspect_ratio = Number(aspect_ratio);

				$image.load(function() {
					$image.Jcrop({
						aspectRatio: aspect_ratio,
						trueSize: [o.image_width, o.image_height],
						minSize: o.min_size,
						onChange: showCoords,
						onSelect: showCoords
					}, function(){
						cropper = this;

						if (crop_coords.toString() != '0,0,0,0') {
							cropper.setSelect(crop_coords);
						};

					});

					checkMinDimension();
				});
			}

			function hideCropper() {
				$el.find('.group').hide();
				$el.find('fieldset').hide();
				$el.append(Symphony.Language.get('No image found. Please upload an image and save entry.'));
				clearCoords();
			}

			function destroyCropper() {
				hideCropper();
				cropper.destroy();
				$el.find('img').remove();
			}

			function showCoords(c) {
				// fix for rounding issues
				c.w = Math.round(Math.max(c.w, o.min_size[0]));
				c.h = Math.round(Math.max(c.h, o.min_size[1]));
				c.x = Math.round(c.x);
				c.y = Math.round(c.y);
				c.x2 = Math.round(c.x2);
				c.y2 = Math.round(c.y2);

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
				$preview_url_input.val(Symphony.Context.get('root') + '/image/4/' + c.w + '/' + c.h + '/' + c.x + '/' + c.y + '/' + scaled_width + '/' + scaled_height + o.image_file);
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

				if (o.min_size[0] == 0 && o.min_size[1] == 0) {
					tooSmall = false;
				}
				else if (o.min_size[0] == 0) {
					tooSmall = (o.image_width < o.min_size[1] * aspect_ratio) || (o.image_height < o.min_size[1]);
				}
				else if (o.min_size[1] == 0) {
					tooSmall = (o.image_width < o.min_size[0]) || (o.image_height < (aspect_ratio == 0 ? 0 : o.min_size[0] / aspect_ratio));
				}
				else {
					tooSmall =  (o.image_width < o.min_size[0]) || (o.image_height < o.min_size[1]);
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
