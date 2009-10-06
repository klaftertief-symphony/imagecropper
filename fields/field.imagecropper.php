<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldImageCropper extends Field {
	
		function __construct(&$parent) {
			parent::__construct($parent);
			$this->_name = __('Image Cropper');
			$this->_required = true;
			$this->_showcolumn = true;
		}
		
		function canFilter(){
			return true;
		}
		
		function getToggleStates(){
			return array('cropped' => __('Cropped'), 'not cropped' => __('Not cropped'));
		}

		function toggleFieldData($data, $newState){
			$data['value'] = $newState;
			return $data;
		}

		function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

			$existing_options = array('cropped', 'not cropped');

			if(is_array($existing_options) && !empty($existing_options)){
				$optionlist = new XMLElement('ul');
				$optionlist->setAttribute('class', 'tags');
				
				foreach($existing_options as $option) $optionlist->appendChild(new XMLElement('li', $option));
						
				$wrapper->appendChild($optionlist);
			}
					
		}

		
		function allowDatasourceParamOutput(){
			return true;
		}
		
		function displaySettingsPanel(&$wrapper, $errors=NULL) {
			parent::displaySettingsPanel($wrapper, $errors);
			
			// get current section id
			$section_id = Administration::instance()->Page->_context[1];
			
			// related field
			$label = Widget::Label(__('Related field'), NULL);
			$fieldManager = new FieldManager($this->_engine);
			$fields = $fieldManager->fetch(NULL, $section_id, 'ASC', 'sortorder', NULL, NULL, 'AND (type = "upload" OR type = "uniqueupload")');
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
			
			// min/max width
			$width = new XMLElement('div', NULL, array('class' => 'group'));
			$label = new XMLElement('label', __('Minimum width (integer) <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_width]', $this->get('min_width')?$this->get('min_width'):''));
			if(isset($errors['min_width'])) {
				$width->appendChild(Widget::wrapFormElementWithError($label, $errors['min_width']));
			} else { 
				$width->appendChild($label);
			};
			$label = new XMLElement('label', __('Maximum width (integer) <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][max_width]', $this->get('max_width')?$this->get('max_width'):''));
			if(isset($errors['max_width'])) {
				$width->appendChild(Widget::wrapFormElementWithError($label, $errors['max_width']));
			} else { 
				$width->appendChild($label);
			};
			$wrapper->appendChild($width);
			
			// min/max height
			$height = new XMLElement('div', NULL, array('class' => 'group'));
			$label = new XMLElement('label', __('Minimum height (integer) <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][min_height]', $this->get('min_height')?$this->get('min_height'):''));
			if(isset($errors['min_height'])) {
				$height->appendChild(Widget::wrapFormElementWithError($label, $errors['min_height']));
			} else { 
				$height->appendChild($label);
			};
			$label = new XMLElement('label', __('Maximum height (integer) <i>Optional</i>'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][max_height]', $this->get('max_height')?$this->get('max_height'):''));
			if(isset($errors['max_height'])) {
				$height->appendChild(Widget::wrapFormElementWithError($label, $errors['max_height']));
			} else { 
				$height->appendChild($label);
			};
			$wrapper->appendChild($height);
			
			$this->appendShowColumnCheckbox($wrapper);
			$this->appendRequiredCheckbox($wrapper);
		}
		
		function checkFields(&$errors, $checkForDuplicates=true) {
			// check if a related field has been selected
			if($this->get('related_field_id') == '') {
				$errors['related_field_id'] = __('This is a required field.');
			}
			
			// check if ratios content is well formed
			if($this->get('ratios')) {
				$validate = true; // TODO
				if(!$validate) {
					$errors['ratios'] = __('Ratios have to be well-formed. Please check your syntax.');
				}
			}
			
			// check if min/max fields are integers
			$min_max_fields = array('min_width', 'max_width', 'min_height', 'max_height');
			foreach ($min_max_fields as $field) {
				if ($this->get($field) != '' && !is_numeric($this->get($field))) {
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
				'max_width',
				'min_height',
				'max_height'
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
				`ratio` varchar(255) default NULL,
				`width` int(11) unsigned NOT NULL,
				`height` int(11) unsigned NOT NULL,
				`x1` int(11) unsigned NOT NULL,
				`x2` int(11) unsigned NOT NULL,
				`y1` int(11) unsigned NOT NULL,
				`y2` int(11) unsigned NOT NULL,
				PRIMARY KEY  (`id`),
				KEY `entry_id` (`entry_id`)
				);"
			);
		}
		
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL) {
			
			$assets_path = '/extensions/imagecropper/assets/';
			$this->_engine->Page->addStylesheetToHead(URL . $assets_path . 'css/jquery.Jcrop.css', 'screen', 120, false);
			$this->_engine->Page->addStylesheetToHead(URL . $assets_path . 'css/publish.css', 'screen', 130, false);
			$this->_engine->Page->addScriptToHead(URL . $assets_path . 'js/jquery.Jcrop.min.js', 430, false);
			$this->_engine->Page->addScriptToHead(URL . $assets_path . 'js/jquery.simplemodal.js', 440, false);
			$this->_engine->Page->addScriptToHead(URL . $assets_path . 'js/imagecropper.js', 450, false);
			
			$id = $this->get('id');
			$related_field_id = $this->get('related_field_id');
			$fieldname = 'fields['.$this->get('element_name').']';

			$ratios = explode(',',$this->get('ratios'));
			$imagecropper_ratios = NULL;
			$imagecropper_ratio = NULL;
			if(is_array($ratios)) {
				$number_of_ratios = count($ratios);
				switch ($number_of_ratios) {
					case 0:
						$imagecropper_ratio = NULL;
						break;
					case 1:
						$pattern = '/(\D*)(\d+)(\s*)(\/|x|\*)(\s*)(\d+)(\D*)/';
						// $imagecropper_ratio = round($ratios[0] / 1, 3) ? $ratios[0] / 1 > 0 : NULL;
						$dividend = preg_replace($pattern, '$2', $ratios[0]);
						$divisor = preg_replace($pattern, '$6', $ratios[0]);
						$imagecropper_ratio = round($dividend/$divisor,3);
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
						$imagecropper_ratios = Widget::Label(__('Ratios'), NULL, 'imagecropper_ratios');
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
			$actions_description = new XMLElement('div', __('Actions'));
			$actions_actions = new XMLElement('ul', NULL, array('class' => 'group'));
			$open_modal = new XMLElement('li');
			$open_modal->appendChild(Widget::Anchor(__('Crop in modal'), '#', __('Crop image in modal dialog'), 'imagecropper_modal'));
			$clear_values = new XMLElement('li');
			$clear_values->appendChild(Widget::Anchor(__('Reset'), '#', __('Reset all values'), 'imagecropper_clear'));
			$actions_actions->appendChild($open_modal);
			$actions_actions->appendChild($clear_values);
			$actions->appendChild($actions_description);
			$actions->appendChild($actions_actions);
			$group->appendChild($actions);

			if ($imagecropper_ratios) {
				$group->appendChild($imagecropper_ratios);
			}

			// $min_width = Widget::Input('', $this->get('min_width'), 'hidden', array('class' => 'imagecropper_min_width'));
			// $group->appendChild($min_width);
			// $max_width = Widget::Input('', $this->get('max_width'), 'hidden', array('class' => 'imagecropper_max_width'));
			// $group->appendChild($max_width);
			// $min_height = Widget::Input('', $this->get('min_height'), 'hidden', array('class' => 'imagecropper_min_height'));
			// $group->appendChild($min_height);
			// $max_height = Widget::Input('', $this->get('max_height'), 'hidden', array('class' => 'imagecropper_max_height'));
			// $group->appendChild($max_height);

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
			$wrapper->appendChild($imagecropper);

			$fieldManager = new FieldManager($this->_engine);
			$related_field = $fieldManager->fetch($this->get('related_field_id'));
			$script = new XMLElement('script');
			$function_call = '
				jQuery(document).ready(function ($) {
					$("#imagecropper_'.$id.'").imageCropper({
						field_id: '.$id.',
						related_field_id: '.$related_field->get('id').',
						related_field_name: "'.$related_field->get('element_name').'",
						ratio: "'.$imagecropper_ratio.'",
						minSize: ['.$this->get('min_width').','.$this->get('min_height').'],
						maxSize: ['.$this->get('max_width').','.$this->get('max_height').'],
					});
				});
			';
			$script->setAttributeArray(array('type' => 'text/javascript'));
			$script->setValue($function_call);
			$this->_engine->Page->addElementToHead($script, 460);
			
		}
		
		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;
			$result = array(
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
		
		function prepareTableValue($data, XMLElement $link=NULL, $entry_id){
			$entryManager = new EntryManager($this->_engine);
			$entries = $entryManager->fetch($entry_id, $this->get('parent_section'));
			
			
			$image = '<img src="' . URL . '/image/1/0/50' . $entries[0]->_data[$this->get('related_field_id')]['file'] .'" alt="'.$this->get('label').' of Entry '.$entry_id.'"/>';
			
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

			// create date and time element
			$imagecropper = new XMLElement($this->get('element_name'));

			$imagecropper->setAttributeArray(array(
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
