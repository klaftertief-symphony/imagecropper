# Image Cropper

Image Cropper is a field extension for the Symphony CMS. It adds image cropping functionality to upload fields.

* Version: 1.0.1
* Date: 2011-06-07
* Author: Jonas Coch (jonas@klaftertief.de)
* Repository: <http://github.com/klaftertief/imagecropper>
* Requirements:
	* Symphony CMS 2.0.7 or newer: <http://github.com/symphony/symphony-2>
	* Modified JIT Image Manipulation extension for frontend and entry list output: <http://github.com/klaftertief/jit_image_manipulation/tree/jCrop>

This extension contains the following languages:

* English (default)
* German

## Installation

1. Upload the 'imagecropper' folder in this archive to your Symphony 'extensions' folder.

2. Enable it by selecting the "Field: Image Cropper", choose Enable from the with-selected menu, then click Apply.

3. You can now add the "Image Cropper" field to your sections with already existing upload fields. The section has to be saved with an upload field before you can add an imagecropper field.

4. Make sure you have [Modified JIT Image Manipulation extension](http://github.com/klaftertief/jit_image_manipulation/tree/jCrop) installed and activated.

## Documentation

### Frontend

The XML output is something like

	<thumbnail cropped="yes" x1="123" x2="723" y1="123" y2="523" width="600" height="400" ratio="1.5" />

The jCrop mode of JIT expects a url like `/image/4/crop_width/crop_height/crop_x/crop_y/resize_width/resize_height/path/to/image.jpg`. `resize_width` and `resize_height` should either equal `crop_width` and `crop_height` (no resizing) or one should be 0 and the other smaller than the crop value (proportional resize).

### Backend

There needs to be an upload field in the section before you can add an imagecropper field. The section has to be saved with an upload field before you can add an imagecropper field.

You can add a filter to your datasource. The syntax is like `width: >200`, `height: <=300`, `cropped: yes` and `ratio: >1`.
There is an optional thumbnail preview in entry overview tables.

## Change Log

### Version 1.0.1 - 2011-06-26

* Fix for issue when an imagecropper field is used in a subsection
* Fix for minimum dimension rounding issue

### Version 1.0.1 - 2011-06-07

* Updated to Jcrop 0.9.9
* Some styling fixes
* Fix for minimum dimension when image is shown scaled down, thanks matasoj

### Version 1.0 - 2011-02-11

* Symphony 2.2 compatibility
* "Create URL" functionality
* "Preview" functionality
* JIT to scale down large images
* Style fixes

### Version 1.0beta - 2010-03-21

* first public version

## Credits

* Thanks to all extension developers for inspirations.
* Image cropper uses [Jcrop » the jQuery Image Cropping Plugin](http://deepliquid.com/content/Jcrop.html)
