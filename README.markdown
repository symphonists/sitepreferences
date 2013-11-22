# Site Preferences

Site preferences offer a way to manage custom front-end settings in the backend. You define all settings in a separate configuration file inside your `workspace`, `preferences.site.php`. This way all preferences can be tracked with Git in case you share your workspace with co-workers.

Site preferences allow the creation of multiple fieldsets shown on the preference page in the backend. Each fieldset can contain multiple instances of the following field types:

- input fields,
- text areas,
- select boxes,
- checkboxes and
- help texts

The extension will return all settings as parameters on the front-end.

# Section Preferences

As of version 1.3, it's possible to associate settings with sections by defining the section handle in the fieldset array. These preferences will additionally be editable in a custom drawer in the specified section's index. 

## Default Configuration:
	
	<?php
		$settings = array(
	
			###### FIELDSET: SITE PREFERENCES ######
			array(
				'name' => 'Site Preferences',
				'sections' => array('section-handle'),
				'fields' => array(
					array(
						'name' => 'textarea',
						'label' => 'This is a textarea',
						'type' => 'textarea',
						'value' => '',
					),
					array(
						'name' => 'input',
						'label' => 'This is an input field',
						'type' => 'input',
						'value' => '',
					),
					array(
						'name' => 'selectbox',
						'label' => 'Select something',
						'type' => 'select',
						'values' => 'this, is, a, selection, list',
						'value' => '',
					),
				)
			),
			########
	
			###### FIELDSET: MORE PREFERENCES ######
			array(
				'name' => 'More preferences',
				'sections' => array(),
				'fields' => array(
					array(
						'type' => 'help',
						'value' => 'This is just another group of settings added to the preference page. You can edit all fields and fieldset in the &lt;code&gt;preferences.site.php&lt;/code&gt; file inside your &lt;code&gt;workspace&lt;/code&gt;.',
					),
					array(
						'name' => 'checkbox',
						'label' => 'Yes, I\'ve read that!',
						'type' => 'checkbox',
						'value' => 'checked',
					),
				)
			),
			########
		);
		
## Example Parameter output
	
	$sp-site-preferences-textarea
	$sp-site-preferences-input
	$sp-site-preferences-selectbox
	$sp-more-preferences-checkbox
	
	<params>
		<sp-site-preferences-textarea />
		<sp-site-preferences-input />
		<sp-site-preferences-selectbox />
		<sp-more-preferences-checkbox>Yes</sp-more-preferences-checkbox>
	</params>
	

	
