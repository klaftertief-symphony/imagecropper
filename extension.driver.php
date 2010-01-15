<?php

	Class Extension_ImageCropper extends Extension{

		public function about(){
			return array(
				'name' => 'Field: Image Cropper',
				'version' => '0.5',
				'release-date' => '2009-12-02',
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
			$this->_Parent->Configuration->remove('imagecropper');
			$this->_Parent->saveConfig();
		}
		
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__appendAssets'
				)
			);
		}
		
		
		public function __appendAssets($context) {
			$assets_path = '/extensions/imagecropper/assets/';
			if (Administration::instance()->Page instanceof contentPublish) {
				Administration::instance()->Page->addStylesheetToHead(URL . $assets_path . 'css/publish.css', 'screen', 130, false);
			}
		}
		
		
	}