<?php

	Class Extension_ImageCropper extends Extension{

		public function install(){
			return Symphony::Database()->query("
				CREATE TABLE `tbl_fields_imagecropper` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`related_field_id` int(11) unsigned NOT NULL,
					`min_width` int(11) unsigned NOT NULL,
					`min_height` int(11) unsigned NOT NULL,
					`ratios` text, 
					PRIMARY KEY  (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_imagecropper`");
		}
		
	}