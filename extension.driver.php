<?php

	Class Extension_ImageCropper extends Extension{

		public function about(){
			return array(
				'name' => 'Field: Image Cropper',
				'type' => 'Field, Interface',
				'version' => '1.0.1',
				'release-date' => '2011-06-07',
				'author' => array(
					'name' => 'Jonas Coch',
					'website' => 'http://klaftertief.de',
					'email' => 'jonas@klaftertief.de'
				),
				'description' => 'Adds image cropping functionality to upload fields.'
			);
		}
		
		public function install(){
			return Symphony::Database()->query(
				"CREATE TABLE `tbl_fields_imagecropper` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`related_field_id` int(11) unsigned NOT NULL,
					`min_width` int(11) unsigned NOT NULL,
					`min_height` int(11) unsigned NOT NULL,
					`ratios` text, 
					PRIMARY KEY  (`id`),
					KEY `field_id` (`field_id`)
				)"
			);
		}
			
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_imagecropper`");
		}
		
	}