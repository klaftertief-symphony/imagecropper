/*
 * ImageCropper for Symphony CMS
 *
 */

;(function ($) {
	var ver = '0.5';

	$.fn.imageCropper = function (options) {
		return this.each(function() {
			var $el = $(this);
			options = options || {};
			var opts = $.extend({}, $.fn.imageCropper.defaults, options);
			var
				jcrop_api,
				$select_ratio = $('#imagecropper_'+opts.field_id+'_ratios'),
				ratio = null,
				image = null,
				field_name = opts.related_field_name,
				upload_field = $('input[name="fields['+field_name+']"]'),
				image_link = upload_field.prev(),
				image_path,
				search = /[^(\/workspace\/)](\S)*/,
				slider,
				box_width = options.box_width,
				crop_coords = [];
			var show_coords = function(c) {
				var direction = $('.imagecropper_direction select', $el[0]).val();
				$('.imagecropper_cropped',$el[0]).val('yes');
				$('.imagecropper_x1',$el[0]).val(c.x);
				$('.imagecropper_y1',$el[0]).val(c.y);
				$('.imagecropper_x2',$el[0]).val(c.x2);
				$('.imagecropper_y2',$el[0]).val(c.y2);
				$('.imagecropper_width',$el[0]).val(c.w);
				$('.imagecropper_height',$el[0]).val(c.h);
				switch(direction){
				case 'no':
					set_url(c.w,c.h,c.x,c.y,c.w,c.h);
					break;
				case 'width':
					set_url(c.w,c.h,c.x,c.y,slider.slider('option', 'value'),0);
					break;
				case 'height':
					set_url(c.w,c.h,c.x,c.y,0,slider.slider('option', 'value'));
					break;
				default:
					set_url(c.w,c.h,c.x,c.y,c.w,c.h);
				};
			};
			var set_url = function(w,h,x,y,c_w,c_h){
				$('.imagecropper_url').val(Symphony.WEBSITE+'/image/4/'+w+'/'+h+'/'+x+'/'+y+'/'+c_w+'/'+c_h+'/'+image_path);
			};
			
			crop_coords = [Number($('.imagecropper_x1',$el[0]).val()), Number($('.imagecropper_y1',$el[0]).val()), Number($('.imagecropper_x2',$el[0]).val()), Number($('.imagecropper_y2',$el[0]).val())];
			
			if (image_link.length) {
				slider = $('.imagecropper_url_slider',$el[0]);
				box_width = $el.width();
				image = new Image();
				image.src = image_link.attr('href');
				image_path = search.exec(image_link.text())[0];
				
				ratio = opts.ratio;
				if (ratio == 'select') {
					if ($select_ratio.length) {
						var calculated_ratio = Math.round(100 * Number($('.imagecropper_width',$el[0]).val()) / Number($('.imagecropper_height',$el[0]).val()));
						ratio = $('option:selected',$select_ratio[0]).val();
					};
				} else if (ratio == 0) {
					ratio = null;
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

					slider.slider({
						slide: function(event, ui){
							var c = jcrop_api.tellSelect();
							var direction = $('.imagecropper_direction select', $el[0]).val();
							if (direction == 'width') {
								set_url(c.w,c.h,c.x,c.y,ui.value,0);
							};
							if (direction == 'height') {
								set_url(c.w,c.h,c.x,c.y,0,ui.value);
							};
						}
					});
					slider.slider('disable');
					$('.imagecropper_url_container', $el[0]).hide();

					$('.imagecropper_direction select', $el[0]).change(function(e) {
						var direction = $(this).val();
						var image_size = jcrop_api.getBounds();
						var c = jcrop_api.tellSelect();
						console.log(c.w);
						switch(direction){
						case 'no':
							slider.slider('disable');
							break;
						case 'width':
							slider.slider('enable');
							slider.slider('option', 'min', opts.minSize[0] ? opts.minSize[0] : 0);
							slider.slider('option', 'max', opts.maxSize[0] ? opts.maxSize[0] : image_size[0]);
							slider.slider('option', 'value', c.w ? c.w : opts.minSize[0]);
							break;
						case 'height':
							slider.slider('enable');
							slider.slider('option', 'min', opts.minSize[1] ? opts.minSize[1] : 0);
							slider.slider('option', 'max', opts.maxSize[1] ? opts.maxSize[1] : image_size[1]);
							slider.slider('option', 'value',  c.h ? c.h : opts.minSize[1]);
							break;
						default:
							slider.slider('disable');
						};
					});

					$('.imagecropper_url_toggle', $el[0]).click(function(e) {
						e.preventDefault();
						$('.imagecropper_url_container', $el[0]).toggle();
					});

					$('.imagecropper_clear', $el[0]).click(function(e) {
						e.preventDefault();
						jcrop_api.release();
						$('.imagecropper_cropped',$el[0]).val('no');
						$('.imagecropper_x1',$el[0]).val('');
						$('.imagecropper_y1',$el[0]).val('');
						$('.imagecropper_x2',$el[0]).val('');
						$('.imagecropper_y2',$el[0]).val('');
						$('.imagecropper_width',$el[0]).val('');
						$('.imagecropper_height',$el[0]).val('');
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

	$.fn.imageCropper.ver = function() { return ver; };

	$.fn.imageCropper.defaults = {
	};
	
})(jQuery.noConflict());


// A handler to kill the action (from Jcrop demo)
function nothing(e) {
	e.stopPropagation();
	e.preventDefault();
	return false;
};
