<?php

	/**
	 * @package sitepreferences
	 */
	/**
	 * Site Preferences Extension
	 */
	Class extension_sitepreferences extends Extension {

		/**
		 * @see http://symphony-cms.com/learn/api/2.2.5/toolkit/extension/#about
		 */
		public function about() {
			return array(
				'name' => 'Site Preferences',
				'version' => '1.1',
				'release-date' => '2011-01-23',
				'author' => array(
					array(
						'name' => 'Büro für Web- und Textgestaltung',
						'website' => 'http://hananils.de',
						'email' => 'buero@hananils.de'
					),
					array(
						'name' => 'Nils Hörrmann',
						'website' => 'http://nilshoerrmann.de',
						'email' => 'post@nilshoerrmann.de'
					)
				),
				'description'   => 'Manage custom front-end settings in the backend.'
			);
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#getSubscribedDelegates
		 */
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
					'page' => '/frontend/',
					'delegate' => 'FrontendParamsResolve',
					'callback' => '__addParams'
				),
			);
		}

		/**
		 * Add site preferences
		 */
		public function __addSitePreferences($context) {
			include(WORKSPACE . '/preferences.site.php');

			// Groups
			$count = 0;
			foreach($settings as $fieldset) {
				$prefix = 'settings[sitepreferences][' . $count . ']';

				// Add fieldset
				$group = new XMLElement('fieldset', '<legend>' . $fieldset['name'] . '</legend><input name="' . $prefix . '[name]" value="' . $fieldset['name'] . '" type="hidden" />', array('class' => 'settings'));
				$context['wrapper']->appendChild($group);

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

		/**
		 * Save preferences
		 */
		public function __savePreferences() {
			$this->saveSitePreferences($_POST['settings']['sitepreferences']);
			unset($_POST['settings']['sitepreferences']);
		}

		/**
		 * Save site preferences
		 */
		public function saveSitePreferences($settings) {
			$string  = "<?php\n\t\$settings = " . $this->plain($settings) . ";\n";
			return General::writeFile(WORKSPACE . '/preferences.site.php', $string, Symphony::Configuration()->get('write_mode', 'file'));
		}

		/**
		 * Convert settings to plain string
		 */
		public function plain($settings) {
			$string = 'array(';
			foreach($settings as $fieldset){
				$string .= "\r\n\r\n\t\t###### FIELDSET: " . General::sanitize(strtoupper($fieldset['name'])) . " ######";
				$string .= "\r\n\t\tarray(";
				$string .= "\r\n\t\t\t'name' => '" . addslashes(General::sanitize($fieldset['name'])) . "',";
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
			$string .= "\r\n\t)";

			return $string;
		}

		/**
		 * Add params to parameter pool
		 */
		public function __addParams($context) {
			include(WORKSPACE . '/preferences.site.php');

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

		/**
		 * @see http://symphony-cms.com/learn/api/2.2.5/toolkit/extension/#install
		 */
		public function enable() {
			$settings = array(
				array(
					'name' => 'Site Preferences',
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

		/**
		 * @see http://symphony-cms.com/learn/api/2.2.5/toolkit/extension/#uninstall
		 */
		public function uninstall() {
			return General::deleteFile(WORKSPACE . '/preferences.site.php');
		}

	}
