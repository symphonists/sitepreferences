<?php

	/**
	 * @package sitepreferences
	 */
	/**
	 * Site Preferences Extension
	 */
	Class extension_sitepreferences extends Extension {

		private $sitepreferences;

		public function __construct() {
			$this->sitepreferences = WORKSPACE . '/preferences.site.php';
		}

	/*-------------------------------------------------------------------------
		Installation
	-------------------------------------------------------------------------*/

		/**
		 * Create sample file on install, if none exists already.
		 */
		public function enable() {
			if(file_exists($this->sitepreferences)) return true;

			// Create sample settings
			$settings = array(
				array(
					'name' => 'Site Preferences',
					'sections' => array(),
					'fields' => array(
						array(
							'name' => 'textarea',
							'label' => 'This is a textarea',
							'type' => 'textarea',
							'value' => ''
						),
						array(
							'name' => 'input',
							'label' => 'This is an input field',
							'type' => 'input',
							'value' => ''
						),
						array(
							'name' => 'selectbox',
							'label' => 'Select something',
							'type' => 'select',
							'values' => 'this, is, a, selection, list',
							'value' => ''
						)
					)
				),
				array(
					'name' => 'More preferences',
					'sections' => array(),
					'fields' => array(
						array(
							'type' => 'help',
							'value' => 'This is just another group of settings added to the preference page. You can edit all fields and fieldset in the <code>preferences.site.php</code> file inside your <code>workspace</code>.'
						),
						array(
							'name' => 'checkbox',
							'label' => 'Yes, I\'ve read that!',
							'type' => 'checkbox',
							'value' => 'checked'
						),
					)
				)
			);

			// Store default site preferences
			return $this->saveSitePreferences($settings);
		}
		
	/*-------------------------------------------------------------------------
		Delegates
	-------------------------------------------------------------------------*/

		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => '__addSitePreferences'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'CustomActions',
					'callback' => '__savePreferences'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostCallback',
					'callback' => '__saveSectionPreferences'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendParamsResolve',
					'callback' => '__addParams'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> '__appendDrawer'
				)
			);
		}

		/**
		 * Add site preferences
		 */
		public function __addSitePreferences($context) {
			$this->createPreferences($context['wrapper']);
		}

		/**
		 * Save preferences
		 */
		public function __savePreferences() {
			$this->saveSitePreferences($_POST['settings']['sitepreferences']);
			unset($_POST['settings']['sitepreferences']);
		}

		/**
		 * Save section preferences
		 */
		public function __saveSectionPreferences() {
			if(isset($_POST['action']['savesitepreferences'])) {
				include($this->sitepreferences);

				foreach($_POST['settings']['sitepreferences'] as $new) {
					for($i = 0; $i < count($settings); $i++) { 
						if($settings[$i]['name'] == $new['name']) {
							$settings[$i] = $new;
						}
					}
				}

				$this->saveSitePreferences($settings);
				unset($_POST['settings']['sitepreferences']);
			}
		}

		/**
		 * Append Drawer
		 */
		public function __appendDrawer($context) {
			$callback = Symphony::Engine()->getPageCallback();

			if($callback['driver'] == 'publish' && $callback['context']['page'] == 'index') {
				$form = Widget::Form(null, 'post', 'sitepreferences');
				$this->createPreferences($form, $callback['context']['section_handle']);
				$fieldsets = $form->getChildren();
				$actions = new XMLElement('div', '<input name="action[savesitepreferences]" type="submit" value="' . __('Save Preferences') . '" />', array('class' => 'actions'));
				$form->appendChild($actions);

				if(!empty($fieldsets)) {
					Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/sitepreferences/assets/sitepreferences.publish.css', 'screen', 100);
					Administration::instance()->Page->insertDrawer(
						Widget::Drawer('sitepreferences', __('Preferences'), $form)
					);
				}
			}
		}

		/**
		 * Add params to parameter pool
		 */
		public function __addParams($context) {
			include($this->sitepreferences);

			// Get groups
			foreach($settings as $fieldset) {
				$prefix = 'sp-' . General::createHandle($fieldset['name']);

				// Get settings
				foreach($fieldset['fields'] as $field) {
					if($field['type'] != 'help') {

						// Checkbox states
						if($field['type'] == 'checkbox') {
							if($field['value'] == 'checked') {
								$field['value'] = 'Yes';
							}
							else {
								$field['value'] = 'No';
							}
						}

						// Generate output
						$id = $prefix . '-' . General::createHandle($field['name']);
						$context['params'][$id] = $field['value'];
					}
				}
			}
		}

	/*-------------------------------------------------------------------------
		Interface
	-------------------------------------------------------------------------*/

		/**
		 * Create preferences
		 */
		private function createPreferences(&$wrapper, $filter = array()) {
			include($this->sitepreferences);

			// Groups
			$count = 0;
			foreach($settings as $fieldset) {
				if(!empty($filter) && !in_array($filter, $fieldset['sections'])) continue;

				$prefix = 'settings[sitepreferences][' . $count . ']';

				// Add fieldset
				$group = new XMLElement('fieldset');
				$legend = new XMLElement('legend', $fieldset['name']);
				$name = Widget::Input($prefix . '[name]', $fieldset['name'], 'hidden');
				$sections = Widget::Input($prefix . '[sections]', implode($fieldset['sections'], ','), 'hidden');

				$group->appendChild($legend);
				$group->appendChild($name);
				$group->appendChild($sections);
				$group->setAttribute('class', 'settings');

				$wrapper->appendChild($group);

				// Add fields
				$position = 0;
				foreach($fieldset['fields'] as $field) {
					$field['value'] = htmlspecialchars_decode($field['value']);

					switch($field['type']) {

						// Text input
						case 'input':
							$item = new XMLElement('label', $field['label'] . ' <input name="' . $prefix . '[fields][' . $position . '][value]" value="' . $field['value'] . '" type="text" />');
							break;

						// Text area
						case 'textarea':
							$item = new XMLElement('label', $field['label'] . ' <textarea name="' . $prefix . '[fields][' . $position . '][value]" rows="10" cols="50">' . $field['value'] . '</textarea>');
							break;

						// Selectbox
						case 'select':
							$options = array('options' => null);
							foreach(explode(',', $field['values']) as $option) {
								$option = trim($option);
								$options[] = array($option, (strpos($field['value'], $option) !== false));
							}

							$item = new XMLElement('label', $field['label']);
							$item->appendChild(Widget::Select($prefix . '[fields][' . $position . '][value]', $options));
							break;

						// Checkbox
						case 'checkbox':
							$item = new XMLElement('label', '<input name="' . $prefix . '[fields][' . $position . '][value]" value="checked" type="checkbox" ' . ($field['value'] == 'checked' ? 'checked="checked" ' : '') . '/> ' . $field['label']);
							break;

						// Help text
						case 'help':
							$group->appendChild(new XMLElement('p', $field['value'], array('class' => 'help')));
							$item = Widget::Input($prefix . '[fields][' . $position . '][value]', $field['value'], 'hidden');
							break;
					}

					// Append field
					if(isset($field['name'])) $group->appendChild(Widget::Input($prefix . '[fields][' . $position . '][name]', $field['name'], 'hidden'));
					if(isset($field['label'])) $group->appendChild(Widget::Input($prefix . '[fields][' . $position . '][label]', $field['label'], 'hidden'));
					$group->appendChild(Widget::Input($prefix . '[fields][' . $position . '][type]', $field['type'], 'hidden'));
					if(isset($field['values'])) $group->appendChild(Widget::Input($prefix . '[fields][' . $position . '][values]', $field['values'], 'hidden'));
					$group->appendChild($item);

					$position++;
				}

				$count++;
			}
		}

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Save site preferences
		 */
		public function saveSitePreferences($settings) {
			$string  = "<?php\n\t\$settings = array(" . $this->plain($settings) . "\n\r\n\t);\n";
			return General::writeFile($this->sitepreferences, $string, Symphony::Configuration()->get('write_mode', 'file'));
		}

		/**
		 * Convert settings to plain string
		 */
		public function plain($settings) {
			if(empty($settings)) return;

			foreach($settings as $fieldset){
				$string .= "\r\n\r\n\t\t###### FIELDSET: " . General::sanitize(strtoupper($fieldset['name'])) . " ######";
				$string .= "\r\n\t\tarray(";
				$string .= "\r\n\t\t\t'name' => '" . addslashes(General::sanitize($fieldset['name'])) . "',";
				$string .= "\r\n\t\t\t'sections' => array(" . $this->wrapInQuotes($fieldset['sections']) . "),";
				$string .= "\r\n\t\t\t'fields' => array(";
				foreach($fieldset['fields'] as $fields){
					$string .= "\r\n\t\t\t\tarray(";
					foreach($fields as $key => $value) {
						$string .= "\r\n\t\t\t\t\t'" . General::sanitize($key) . "' => '" . addslashes(General::sanitize($value)) . "',";
					}
					$string .= "\r\n\t\t\t\t),";
				}
				$string .= "\r\n\t\t\t)";
				$string .= "\r\n\t\t),";
				$string .= "\r\n\t\t########";
			}

			return $string;
		}

		/**
		 * Wrap array values in quotes and create comma-separated string
		 */
		private function wrapInQuotes($values) {
			if(empty($values)) return;

			if(!is_array($values)) {
				$values = explode(',', $values);
			}

			for($i = 0; $i < count($values); $i++) { 
				$values[$i] = "'" . trim($values[$i]) . "'";
			}

			return implode(',', $values);
		}

	}
