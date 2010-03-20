<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldImageCropper extends Field {

		const CROPPED = 0;
		const WIDTH = 1;
		const HEIGHT = 2;
		const RATIO = 3;
		const ERROR = 4;

		function __construct(&$parent) {
			parent::__construct($parent);
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
			$fieldManager = new FieldManager($this->_engine);
			$fields = $fieldManager->fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, 'AND (type = "upload" OR type = "uniqueupload" OR type="signedfileupload" OR type="advancedupload")');
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
			$label = new XMLElement('label', __('Aspect ratios <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][ratios]', $this->get('ratios')));
			if(isset($errors['ratios'])) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['ratios']));
			} else {
				$wrapper->appendChild($label);
			};
			$ratios = array('1/1','3/2','2/3','4/3','3/4','16/9');
			$filter = new XMLElement('ul', NULL, array('class' => 'tags'));
			foreach($ratios as $ratio) {
				$filter->appendChild(new XMLElement('li', $ratio));
			};
			$wrapper->appendChild($filter);

			// minimal dimension
			$min_dimension = new XMLElement('div', NULL, array('class' => 'group'));
			$label = new XMLElement('label', __('Minimum width <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_width]', $this->get('min_width')?$this->get('min_width'):''));
			if(isset($errors['min_width'])) {
				$min_dimension->appendChild(Widget::wrapFormElementWithError($label, $errors['min_width']));
			} else {
				$min_dimension->appendChild($label);
			};
			$label = new XMLElement('label', __('Minimum height <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_height]', $this->get('min_height')?$this->get('min_height'):''));
			if(isset($errors['min_height'])) {
				$min_dimension->appendChild(Widget::wrapFormElementWithError($label, $errors['min_height']));
			} else {
				$min_dimension->appendChild($label);
			};
			$wrapper->appendChild($min_dimension);

			$this->appendShowColumnCheckbox($wrapper);
		}

		function checkFields(&$errors, $checkForDuplicates=true) {
			// check for presence of upload fields
			$section_id = Administration::instance()->Page->_context[1];
			$fieldManager = new FieldManager($this->_engine);
			$fields = $fieldManager->fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, 'AND (type = "upload" OR type = "uniqueupload" OR type="signedfileupload")');
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
					if(!preg_match('/^\d+\/\d+$/', $ratio)) {
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

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
		}

		function createTable(){
			return $this->_engine->Database->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
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
				);"
			);
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL) {

			$assets_path = '/extensions/imagecropper/assets/';
			$this->_engine->Page->addStylesheetToHead(URL . $assets_path . 'css/jquery.Jcrop.css', 'screen', 120, false);
			$this->_engine->Page->addStylesheetToHead(URL . $assets_path . 'css/publish.css', 'screen', 140, false);
			$this->_engine->Page->addScriptToHead(URL . $assets_path . 'js/jquery.Jcrop.min.js', 430, false);
			$this->_engine->Page->addScriptToHead(URL . $assets_path . 'js/imagecropper.js', 460, false);

			$id = $this->get('id');
			$related_field_id = $this->get('related_field_id');
			$fieldname = 'fields['.$this->get('element_name').']';

			$ratios = array_unique(explode(',',$this->get('ratios')));
			$imagecropper_ratios = NULL;
			$imagecropper_ratio = NULL;
			if(is_array($ratios)) {
				$number_of_ratios = count($ratios);
				switch ($number_of_ratios) {
					case 0:
						$imagecropper_ratio = NULL;
						$imagecropper_ratios = new XMLElement('div', NULL, array('class' => 'label'));
						$label = new XMLElement('h3', __('Aspect ratio'), array('class' => 'label'));
						$imagecropper_ratios->appendChild($label);
						$imagecropper_ratios->appendChild(Widget::Input($fieldname.'[ratio]', NULL, 'hidden', array('class' => 'imagecropper_free_ratio')));
						$imagecropper_ratios->appendChild(new XMLElement('p', __('Free cropping'), array('class' => 'help')));
					break;
					case 1:
						if (in_array(0,$ratios)) {
							$imagecropper_ratio = NULL;
							$imagecropper_ratios = new XMLElement('div', NULL, array('class' => 'label'));
							$label = new XMLElement('h3', __('Aspect ratio'), array('class' => 'label'));
							$imagecropper_ratios->appendChild($label);
							$imagecropper_ratios->appendChild(Widget::Input($fieldname.'[ratio]', NULL, 'hidden', array('class' => 'imagecropper_free_ratio')));
							$imagecropper_ratios->appendChild(new XMLElement('p', __('Free cropping'), array('class' => 'help')));
							break;
						}
						$pattern = '/(\D*)(\d+)(\s*)(\/|x|\*)(\s*)(\d+)(\D*)/';
						$dividend = preg_replace($pattern, '$2', $ratios[0]);
						$divisor = preg_replace($pattern, '$6', $ratios[0]);
						$imagecropper_ratio = round($dividend/$divisor,3);
						$imagecropper_ratios = new XMLElement('div', NULL, array('class' => 'label'));
						$label = new XMLElement('h3', __('Aspect ratio'), array('class' => 'label'));
						$imagecropper_ratios->appendChild($label);
						$imagecropper_ratios->appendChild(Widget::Input($fieldname.'[ratio]', $imagecropper_ratio, 'hidden'));
						$imagecropper_ratios->appendChild(new XMLElement('p', __('Fixed at ').$ratios[0], array('class' => 'help')));
					break;
					default:
						$options = array();
						$pattern = '/(\D*)(\d+)(\s*)(\/|x|\*)(\s*)(\d+)(\D*)/';
						foreach ($ratios as $index => $ratio) {
							$dividend = preg_replace($pattern, '$2', $ratio);
							$divisor = preg_replace($pattern, '$6', $ratio);
							$ratio_float = round($dividend/$divisor,3);
							$selected = ($ratio_float == $data['ratio']);
							$options[] = array($ratio_float, $selected, $ratio);
						}
						$imagecropper_ratios = Widget::Label(__('Aspect Ratio'), NULL, 'imagecropper_ratios');
						$imagecropper_ratios->appendChild(Widget::Select(NULL, $options, array('name' => $fieldname.'[ratio]', 'id' => 'imagecropper_'.$id.'_ratios')));
						$imagecropper_ratio = 'select';
					break;
				}
			}

			$imagecropper = new XMLElement('div', NULL, array('class' => 'label'));
			$label = new XMLElement('h3', $this->get('label'), array('class' => 'label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$imagecropper->appendChild($label);

			$span = new XMLElement('span', NULL, array('id' => 'imagecropper_' . $id));
			$group = new XMLElement('div', NULL, array('class' => 'group'));

			$actions = new XMLElement('div', NULL, array('class' => 'imagecropper_actions'));
			$actions_description = new XMLElement('h3', __('Actions'), array('class' => 'label'));
			$actions_actions = new XMLElement('ul', NULL, array('class' => 'group'));
			$clear_values = new XMLElement('li');
			$clear_values->appendChild(Widget::Anchor(__('Reset'), '#', __('Reset all values'), 'imagecropper_clear'));
			$actions_actions->appendChild($clear_values);
			$actions->appendChild($actions_description);
			$actions->appendChild($actions_actions);
			$group->appendChild($actions);
			$group->appendChild($imagecropper_ratios);

			$cropped = Widget::Input($fieldname.'[cropped]', $data['cropped'] ? $data['cropped'] : 'no', 'hidden', array('class' => 'imagecropper_cropped'));
			$group->appendChild($cropped);

			$x1 = Widget::Input($fieldname.'[x1]', $data['x1'], 'hidden', array('class' => 'imagecropper_x1'));
			$group->appendChild($x1);

			$x2 = Widget::Input($fieldname.'[x2]', $data['x2'], 'hidden', array('class' => 'imagecropper_x2'));
			$group->appendChild($x2);

			$y1 = Widget::Input($fieldname.'[y1]', $data['y1'], 'hidden', array('class' => 'imagecropper_y1'));
			$group->appendChild($y1);

			$y2 = Widget::Input($fieldname.'[y2]', $data['y2'], 'hidden', array('class' => 'imagecropper_y2'));
			$group->appendChild($y2);

			$width = Widget::Input($fieldname.'[width]', $data['width'], 'hidden', array('class' => 'imagecropper_width'));
			$group->appendChild($width);

			$height = Widget::Input($fieldname.'[height]', $data['height'], 'hidden', array('class' => 'imagecropper_height'));
			$group->appendChild($height);

			$span->appendChild($group);

			$imagecropper->appendChild($span);

			if ($flagWithError != NULL) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($imagecropper, $flagWithError));
			}
			else {
				$wrapper->appendChild($imagecropper);
			}

			$fieldManager = new FieldManager($this->_engine);
			$related_field = $fieldManager->fetch($this->get('related_field_id'));
			$script = new XMLElement('script');
			$options = '
				field_id: '.$id.',
				related_field_id: '.$this->get('related_field_id').',
				related_field_name: "'.$related_field->get('element_name').'",
				ratio: "'.$imagecropper_ratio.'",
				minSize: ['.$this->get('min_width').','.$this->get('min_height').'],
			';
			$function_call = '
				jQuery(document).ready(function ($) {
					$("#imagecropper_'.$id.'").imageCropper({'
						.$options.
					'});
				});
			';
			$script->setAttributeArray(array('type' => 'text/javascript'));
			$script->setValue($function_call);
			$this->_engine->Page->addElementToHead($script, 460);

		}

		function prepareTableValue($data, XMLElement $link=NULL, $entry_id){
			if ($data['cropped'] == 'yes') {
				$entryManager = new EntryManager($this->_engine);
				$entries = $entryManager->fetch($entry_id);

				$image = '<img src="' . URL . '/image/4/'.$data['width'].'/'.$data['height'].'/'.$data['x1'].'/'.$data['y1'].'/0/75'. $entries[0]->_data[$this->get('related_field_id')]['file'] .'" alt="'.$this->get('label').' of Entry '.$entry_id.'"/>';
			} else {
				return parent::prepareTableValue(NULL);
			}

			if($link){
				$link->setValue($image);
				return $link->generate();
			}

			else{
				$link = new XMLElement('span', $image);
				return $link->generate();
			}

		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false) {

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
