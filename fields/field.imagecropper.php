<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldImageCropper extends Field {

		const CROPPED = 0;
		const WIDTH = 1;
		const HEIGHT = 2;
		const RATIO = 3;
		const ERROR = 4;

		private $supported_upload_fields = array('upload', 'uniqueupload', 'signedfileupload', 'image_upload');

		function __construct() {
			parent::__construct();
			$this->_name = __('Image Cropper');
			$this->_required = false;
			$this->_showcolumn = true;
		}

		function canFilter(){
			return true;
		}

		function isSortable(){
			return true;
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`cropped` $order");
		}

		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation=false){

			$parsed = array();

			foreach ($data as $string) {
				$type = self::__parseFilter($string);

				if($type == self::ERROR) return false;

				if(!is_array($parsed[$type])) $parsed[$type] = array();

				$parsed[$type] = $string;
			}

			foreach($parsed as $type => $value){
				$value = trim($value);

				switch($type){

					case self::CROPPED:
						$field_id = $this->get('id');
						$this->_key++;
						$joins .= "
							LEFT JOIN
								`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
								ON (e.id = t{$field_id}_{$this->_key}.entry_id)
						";
						$where .= "
							AND t{$field_id}_{$this->_key}.cropped = '{$value}'
						";
						break;

					case self::WIDTH:
						$field_id = $this->get('id');
						$this->_key++;
						$joins .= "
							LEFT JOIN
								`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
								ON (e.id = t{$field_id}_{$this->_key}.entry_id)
						";
						$where .= "
							AND t{$field_id}_{$this->_key}.width {$value}
						";
						break;

					case self::HEIGHT:
						$field_id = $this->get('id');
						$this->_key++;
						$joins .= "
							LEFT JOIN
								`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
								ON (e.id = t{$field_id}_{$this->_key}.entry_id)
						";
						$where .= "
							AND t{$field_id}_{$this->_key}.height {$value}
						";
						break;

						case self::RATIO:
							$field_id = $this->get('id');
							$this->_key++;
							$joins .= "
								LEFT JOIN
									`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
									ON (e.id = t{$field_id}_{$this->_key}.entry_id)
							";
							$where .= "
								AND t{$field_id}_{$this->_key}.ratio {$value}
							";
							break;
				}
			}

			return true;
		}

		protected static function __parseFilter(&$string){

			$string = self::__cleanFilterString($string);

			if(preg_match('/^cropped:/i', $string)){
				$string = str_replace('cropped:', '', $string);
				return self::CROPPED;
			}

			if(preg_match('/^width:/i', $string)){
				$string = str_replace('width:', '', $string);
				return self::WIDTH;
			}

			if(preg_match('/^height:/i', $string)){
				$string = str_replace('height:', '', $string);
				return self::HEIGHT;
			}

			if(preg_match('/^ratio:/i', $string)){
				$string = str_replace('ratio:', '', $string);
				return self::RATIO;
			}
		}

		protected static function __cleanFilterString($string){
			$string = trim($string);

			return $string;
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if ($data['cropped'] == 'yes') {
				if($this->get('min_width') > $data['width']){
					$message = __('"%1$s" needs to have a width of at least %2$spx.', array($this->get('label'), $this->get('min_width')));
					return self::__INVALID_FIELDS__;
				}

				if($this->get('min_height') > $data['height']){
					$message = __('"%1$s" needs to have a height of at least %2$spx.', array($this->get('label'), $this->get('min_height')));
					return self::__INVALID_FIELDS__;
				}
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;
			$result = array(
				'cropped' => $data['cropped'],
				'ratio' => $data['ratio'],
				'x1' => $data['x1'],
				'x2' => $data['x2'],
				'y1' => $data['y1'],
				'y2' => $data['y2'],
				'width' => $data['width'],
				'height' => $data['height']
			);
			return $result;
		}

		function displaySettingsPanel(&$wrapper, $errors=NULL) {
			parent::displaySettingsPanel($wrapper, $errors);

			// get current section id
			$section_id = Administration::instance()->Page->_context[1];

			// related field
			$label = Widget::Label(__('Related upload field'), NULL);
			$fields = FieldManager::fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, sprintf("AND (type IN ('%s'))", implode("', '", $this->supported_upload_fields)));
			$options = array(
				array('', false, __('None Selected'), ''),
			);
			$attributes = array(
				array()
			);
			if(is_array($fields) && !empty($fields)) {
				foreach($fields as $field) {
					$options[] = array($field->get('id'), ($field->get('id') == $this->get('related_field_id')), $field->get('label'));
				}
			};
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][related_field_id]', $options));
			if(isset($errors['related_field_id'])) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['related_field_id']));
			} else {
				$wrapper->appendChild($label);
			};

			// ratios
			$label = Widget::Label(__('Aspect ratios <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][ratios]', $this->get('ratios')));
			if(isset($errors['ratios'])) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['ratios']));
			} else {
				$wrapper->appendChild($label);
			};
			$ratios = array('0','1/1','3/2','2/3','4/3','3/4','16/9');
			$filter = new XMLElement('ul', NULL, array('class' => 'tags'));
			foreach($ratios as $ratio) {
				$filter->appendChild(new XMLElement('li', $ratio));
			};
			$wrapper->appendChild($filter);
			$help = new XMLElement('p', __('Leave empty for free cropping or add <code>0</code> to add an option for free cropping.'), array('class' => 'help'));
			$wrapper->appendChild($help);

			// minimal dimension
			$min_dimension = new XMLElement('div', NULL, array('class' => 'two columns'));
			$label = Widget::Label(__('Minimum width <i>Optional</i>'));
			$label->addClass('column');
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_width]', $this->get('min_width')?$this->get('min_width'):''));
			if(isset($errors['min_width'])) {
				$min_dimension->appendChild(Widget::wrapFormElementWithError($label, $errors['min_width']));
			} else {
				$min_dimension->appendChild($label);
			};
			$label = Widget::Label(__('Minimum height <i>Optional</i>'));
			$label->addClass('column');
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_height]', $this->get('min_height')?$this->get('min_height'):''));
			if(isset($errors['min_height'])) {
				$min_dimension->appendChild(Widget::wrapFormElementWithError($label, $errors['min_height']));
			} else {
				$min_dimension->appendChild($label);
			};
			$wrapper->appendChild($min_dimension);
			$help = new XMLElement('p', __('Set minimum dimensions for the cropped image.'), array('class' => 'help'));
			$wrapper->appendChild($help);

			$column = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendShowColumnCheckbox($column);
			$wrapper->appendChild($column);
		}

		function checkFields(&$errors, $checkForDuplicates=true) {
			// check for presence of upload fields
			$section_id = Administration::instance()->Page->_context[1];
			$fields = FieldManager::fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, sprintf("AND (type IN ('%s'))", implode("', '", $this->supported_upload_fields)));
			if(empty($fields)) {
				$errors['related_field_id'] = __('There is no upload field in this section. You have to save the section with an upload field before you can add an image cropper field.');
			} else {
				// check if a related field has been selected
				if($this->get('related_field_id') == '') {
					$errors['related_field_id'] = __('This is a required field.');
				}
			};

			// check if ratios content is well formed
			if($this->get('ratios')) {
				$ratios = explode(',', $this->get('ratios'));
				$ratios = array_map('trim', $ratios);
				
				foreach ($ratios as $ratio) {
					if(!preg_match('/^(\d+\/\d+|0)$/', $ratio)) {
						$errors['ratios'] = __('Ratios have to be well formed.');
					}
				}
			}

			// check if min fields are integers
			$min_fields = array('min_width', 'min_height');
			foreach ($min_fields as $field) {
				$i = $this->get($field);
				if ($i != '' && !preg_match('/^\d+$/', $i)) {
					$errors[$field] = __('This has to be an integer.');
				}
			}

			return parent::checkFields($errors, $checkForDuplicates);
		}

		function commit() {

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			$all_fields = array(
				'related_field_id',
				'ratios',
				'min_width',
				'min_height'
			);
			foreach ($all_fields as $field) {
				$value = $this->get($field);
				if (!empty($value)) {
					$fields[$field] = $value;
				}
			}

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		function createTable(){
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`cropped` enum('yes','no') NOT NULL default 'no',
					`ratio` varchar(255) default NULL,
					`width` int(11) unsigned default NULL,
					`height` int(11) unsigned default NULL,
					`x1` int(11) unsigned default NULL,
					`x2` int(11) unsigned default NULL,
					`y1` int(11) unsigned default NULL,
					`y2` int(11) unsigned default NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id) {

			// append assets
			$assets_path = '/extensions/imagecropper/assets/';
			Administration::instance()->Page->addStylesheetToHead(URL . $assets_path . 'jquery.Jcrop.min.css', 'screen', 120, false);
			Administration::instance()->Page->addStylesheetToHead(URL . $assets_path . 'jquery-ui-1.8.20.custom.css', 'screen', 130, false);
			Administration::instance()->Page->addStylesheetToHead(URL . $assets_path . 'imagecropper.publish.css', 'screen', 140, false);
			Administration::instance()->Page->addScriptToHead(URL . $assets_path . 'jquery.Jcrop.min.js', 430, false);
			Administration::instance()->Page->addScriptToHead(URL . $assets_path . 'jquery-ui-1.8.20.custom.min.js', 440, false);
			Administration::instance()->Page->addScriptToHead(URL . $assets_path . 'imagecropper.publish.js', 450, false);

			// initialize some variables
			$id = $this->get('id');
			$related_field_id = $this->get('related_field_id');
			$fieldname = 'fields' . $fieldnamePrefix . '['. $this->get('element_name') . ']' . $fieldnamePostfix;

			// get info about the related field entry data
			if ($entry_id != 0) {
				$entry = EntryManager::fetch($entry_id);
				$imageData = $entry[0]->getData($related_field_id);
				$imageMeta = unserialize($imageData['meta']);
			}

			// main field label
			$label = new XMLElement('p', $this->get('label'), array('class' => 'label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

			// hidden inputs
			$cropped = Widget::Input($fieldname.'[cropped]', $data['cropped'] ? $data['cropped'] : 'no', 'hidden');
			$label->appendChild($cropped);
			$x1 = Widget::Input($fieldname.'[x1]', $data['x1'], 'hidden');
			$label->appendChild($x1);
			$x2 = Widget::Input($fieldname.'[x2]', $data['x2'], 'hidden');
			$label->appendChild($x2);
			$y1 = Widget::Input($fieldname.'[y1]', $data['y1'], 'hidden');
			$label->appendChild($y1);
			$y2 = Widget::Input($fieldname.'[y2]', $data['y2'], 'hidden');
			$label->appendChild($y2);
			$width = Widget::Input($fieldname.'[width]', $data['width'], 'hidden');
			$label->appendChild($width);
			$height = Widget::Input($fieldname.'[height]', $data['height'], 'hidden');
			$label->appendChild($height);

			$wrapper->appendChild($label);

			// main imagecropper container
			$imagecropper = new XMLElement('div', NULL, array('class' => 'inline frame imagecropper'));
			
			// group for action links and aspect ratio select box
			$group = new XMLElement('div', NULL, array('class' => 'two columns'));

			$actions = new XMLElement('div', __('Actions'), array('class' => 'column'));
			$list = new XMLElement('ul');
			$list_item = new XMLElement('li');
			$list_item->appendChild(Widget::Anchor(__('Reset'), '#', __('Reset all values'), 'imagecropper_clear'));
			$list->appendChild($list_item);
			$list_item = new XMLElement('li');
			$list_item->appendChild(Widget::Anchor(__('Preview'), '#', __('Open current detail of the image in a new window'), 'imagecropper_preview_link'));
			$list->appendChild($list_item);
			$list_item = new XMLElement('li');
			$list_item->appendChild(Widget::Anchor(__('Show URL'), '#', __('Show URL of the current detail'), 'imagecropper_preview_toggle'));
			$list->appendChild($list_item);
			$actions->appendChild($list);
			$group->appendChild($actions);

			$ratios = array_unique(explode(',',$this->get('ratios')));
			$aspect_ratio = Widget::Label(__('Aspect ratio'), null, 'column');
			if(is_array($ratios)) {
				$number_of_ratios = count($ratios);
				switch ($number_of_ratios) {
					case 0:
						$imagecropper_ratio = NULL;
						$aspect_ratio->appendChild(Widget::Input($fieldname.'[ratio]', NULL, 'hidden'));
						$aspect_ratio->appendChild(new XMLElement('p', __('Free cropping'), array('class' => 'help')));
					break;
					case 1:
						if (in_array(0,$ratios)) {
							$imagecropper_ratio = NULL;
							$aspect_ratio->appendChild(Widget::Input($fieldname.'[ratio]', NULL, 'hidden'));
							$aspect_ratio->appendChild(new XMLElement('p', __('Free cropping'), array('class' => 'help')));
							break;
						}
						$pattern = '/(\D*)(\d+)(\s*)(\/|x|\*)(\s*)(\d+)(\D*)/';
						$dividend = preg_replace($pattern, '$2', $ratios[0]);
						$divisor = preg_replace($pattern, '$6', $ratios[0]);
						$imagecropper_ratio = round($dividend/$divisor,3);
						$aspect_ratio->appendChild(Widget::Input($fieldname.'[ratio]', $imagecropper_ratio, 'hidden'));
						$aspect_ratio->appendChild(new XMLElement('p', __('Fixed at ').$ratios[0]));
					break;
					default:
						$imagecropper_ratio = 'select';
						$options = array();
						$pattern = '/(\D*)(\d+)(\s*)(\/|x|\*)(\s*)(\d+)(\D*)/';
						foreach ($ratios as $index => $ratio) {
							if ($ratio == 0) {
								$selected = ($ratio == $data['ratio']);
								$options[] = array($ratio, $selected, __('Free cropping'));
							} else {
								$dividend = preg_replace($pattern, '$2', $ratio);
								$divisor = preg_replace($pattern, '$6', $ratio);
								$ratio_float = round($dividend/$divisor,3);
								$selected = ($ratio_float == $data['ratio']);
								$options[] = array($ratio_float, $selected, $ratio);
							}
						}
						$aspect_ratio->appendChild(Widget::Select('', $options, array('name' => $fieldname.'[ratio]')));
					break;
				}
			}
			$group->appendChild($aspect_ratio);

			$imagecropper->appendChild($group);

			// URL of cropped image
			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'imagecropper_preview'));

			$group = new XMLElement('div', NULL, array('class' => 'two columns'));

			$label = Widget::Label(__('URL'), null, 'primary column');
			$label->appendChild(Widget::Input($fieldname.'[preview_url]'));
			$group->appendChild($label);

			$label = Widget::Label(__('Scale'), null, 'secondary column');
			$label->appendChild(new XMLElement('i', NULL, array('class' => 'imagecropper_scale')));
			$label->appendChild(Widget::Input($fieldname.'[preview_scale]', '100'));
			$label->appendChild(new XMLElement('span', NULL, array('class' => 'imagecropper_scale_slider')));
			$group->appendChild($label);

			$fieldset->appendChild($group);

			$help = new XMLElement('p', __('You can scale the image down before previewing it or copying its URL.'), array('class' => 'help'));
			$fieldset->appendChild($help);

			$imagecropper->appendChild($fieldset);

			// data for imagecropper JS options
			// (can't use a single JSON object because attribute values are always with double qoutes)
			$imagecropper->setAttributeArray(array(
				'data-field_name' => $fieldname,
				'data-field_id' => $id,
				'data-related_field_id' => $this->get('related_field_id'),
				'data-ratio' => $imagecropper_ratio,
				'data-min_size' => '['.$this->get('min_width').','.$this->get('min_height').']',
				'data-image_file' => $imageData['file'],
				'data-image_width' => $imageMeta['width'],
				'data-image_height' => $imageMeta['height'],
			));

			// appen field to wrapper
			if ($flagWithError != NULL) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($imagecropper, $flagWithError));
			}
			else {
				$wrapper->appendChild($imagecropper);
			}
		}

		function prepareTableValue($data, XMLElement $link=NULL, $entry_id = NULL){
			if (isset($entry_id) && $data['cropped'] == 'yes') {
				$entries = EntryManager::fetch($entry_id);
				
				$entryData = $entries[0]->getData();
				$image = '<img style="vertical-align: middle;" src="' . URL . '/image/5/'.$data['width'].'/'.$data['height'].'/'.$data['x1'].'/'.$data['y1'].'/0/40'. $entryData[$this->get('related_field_id')]['file'] .'" alt="'.$this->get('label').' of Entry '.$entry_id.'"/>';
			} else {
				return parent::prepareTableValue(NULL);
			}

			if($link){
				$link->setValue($image);
				return $link->generate();
			}

			else{
				$link = Widget::Anchor($image, URL . '/image/5/'.$data['width'].'/'.$data['height'].'/'.$data['x1'].'/'.$data['y1'].'/'.$data['width'].'/'.$data['height']. $entryData[$this->get('related_field_id')]['file']);
				return $link->generate();
			}

		}

		function preparePlainTextValue($data, $entry_id){
			if ($data['cropped'] == 'yes') {
				return "x1: {$data['x1']}px, y1: {$data['y1']}px, x2: {$data['x2']}px, y2: {$data['y2']}px";
			}
		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
			
			if ($data['cropped'] == 'yes') {
				$imagecropper = new XMLElement($this->get('element_name'));

				$imagecropper->setAttributeArray(array(
					'cropped' => $data['cropped'],
					'x1' => $data['x1'],
					'x2' => $data['x2'],
					'y1' => $data['y1'],
					'y2' => $data['y2'],
					'width' => $data['width'],
					'height' => $data['height'],
					'ratio' => $data['ratio']
				));

				$wrapper->appendChild($imagecropper);
			}

		}

	}
