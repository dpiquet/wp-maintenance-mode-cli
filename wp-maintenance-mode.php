<?php

if(!defined('WP_CLI')) { return; }

/**
 * Control wp-maintenance-mode plugin
 *
 * @package WP-CLI
 * @subpackage wp-maintenance-mode
 *
 * @author Damien PIQUET (doyousoft.com) <piqudam@gmail.com>
 * @licence GPLv2+
 */

class wpMaintenanceMode extends WP_CLI_Command {

	protected $_pluginInstance = NULL;
	protected $_pluginInstanceAdmin = NULL;
	protected $_settings = NULL;
	protected $_modifiedSettings = false;

	/**
	 * Turn maintenance mode on
	 *
	 *
	 */
	public function enable( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$networkWide = false;

		$this->_update_setting('general', 'status', 1);

		$successMsg = 'Maintenance mode enabled';
		$errMsg = 'could not enable maitenance mode';

		$this->_save_settings($successMsg, $errMsg);
	}

	/**
	 * Turn maintenance mode off
	 *
	 *
	 *
	 */
	public function disable( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$networkWide = false;

		$this->_update_setting('general', 'status', 0);
		$success = 'Maintenance mode disabled';
		$error = 'Maintenance mode could not be disabled';

		$this->_save_settings($successMsg, $errMsg);
	}

	/**
	 * Get a wp-maintenance-mode setting value
	 *
	 * @synopsis --tab=<tab> --value=<value>
	 * @subcommand get-setting
	 */
	public function get_setting( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		//$settings = $this->_pluginInstance->get_plugin_settings();

		//print_r($settings);
		
		if(!isset($assoc_args['tab'])) {
			WP_CLI::error('Missing tab argument for get-setting command');
		}

		$tab = $assoc_args['tab'];

		if(!isset($assoc_args['value'])) {
			WP_CLI:error('Missing value argument for get-setting command');
		}

		$value = $assoc_args['value'];

		if(!isset($this->_settings[$tab][$value])) {
			WP_CLI::error("Unknown option $value in $tab tab !");
		}

		if(is_array($this->_settings[$tab][$value])) {
			foreach($this->_settings[$tab][$value] as $line) {
				WP_CLI::line($line);
			}
		}
		else {
			WP_CLI::line($this->_settings[$tab][$value]);
		}
	}

	/**
	 * Update maintenance nag design
	 *
	 * ## OPTIONS
	 *
	 * @synopsis [--title=<title>] [--heading=<heading>] [--heading-color=<color>] [--text=<text>] [--text-color=<color>] [--bg_type=<bool>] [--bg_color=<color>] [--bg_custom=<boolean>] [--bg_predefined=<text>]
	 *
	 * @subcommand update-design-option
	 */

	public function update_design_option( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$designSettings = Array(
			'title' => 'text',
			'heading' => 'text',
			'heading_color' => 'color',
			'text' => 'text',
			'text_color' => 'color',
			'bg_type' => 'boolean',
			'bg_color' => 'color',
			'bg_custom' => 'boolean',
			'bg_predefined' => 'text',
			'custom_css' => 'text_array'
		);

		foreach($designSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$this->_update_setting('design', $setting, $this->_filter_input($assoc_args[$setting], $type));
			}
		}

		$successMsg = 'maintenance nag design updated';
		$errorMsg = 'Could not update maintenance nag design';
		$this->_save_settings($successMsg, $errorMsg);
	}	

	/**
	 * Get design option value
	 *
	 * ## OPTIONS
	 * <value>
	 * : The option value to get
	 *
	 * @subcommand get-design-option
	 */
	public function get_design_option( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$settings = $this->_pluginInstance->get_plugin_settings();

		foreach($args as $arg) {
			if(isset($settings['design'][$arg])) {
				WP_CLI::line($settings['design'][$arg]);
			}
		}
	}

	/**
	 * Update general option value
	 *
	 * ## OPTIONS
	 * 
	 * @synopsis [--status=<bool>] [--bypass_bots=<bool>] [--backend_role=<cap>] [--frontend_role=<cap>] [--meta_robots=<bool>] [--redirection=<url>] [--notice=<bool>] [--admin_link=<bool>] [--exclude=<urls>]
	 *
	 * @subcommand update-general-option
	 */
	public function update_general_option( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$generalSettings = Array(
			'status' => 'boolean',
			'status_date' => 'text',
			'bypass_bots' => 'boolean',
			'backend_role' => 'role',
			'frontend_role' => 'role',
			'meta_robots' => 'boolean',
			'redirection' => 'text',
			'notice' => 'boolean',
			'admin_link' => 'boolean',
			'exclude' => 'text_array'
		);

		foreach($generalSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$this->_update_setting('general', $setting, $this->_filter_input($assoc_args[$setting], $type));
			}
		}

		$successMsg = 'maintenance mode general settings updated';
		$errorMsg = 'Could not update maintenance mode general settings';

		$this->_save_settings($successMsg, $errorMsg);
	}

	/**
	 * Update modules option value
	 *
	 *
	 * @synopsis [--countdow_status=<bool>] [--countdown_start=<date>] [--countdown_details=<values>] [--countdown_color=<color>] [--subscribe_status=<bool>] [--subscribe_text=<text>] [--subscribe_text_color=<color>] [--social_status=<bool>] [--social_target=<bool>] [--social_github=<url>] [--social_dribbble=<url>] [--social_twitter=<url>] [--social_facebook=<url>] [--social_pinterest=<url>] [--social_googleplus=<url>] [--social_linkedin=<url>] [--contact_status=<boolean>] [--contact_email=<email>] [--contact_effects=<effect>] [--ga_status=<boolean>] [--ga_code=<text>]
	 *
	 * @subcommand update-modules-option
	 */
	public function update_modules_option( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$modulesSettings = Array(
			'countdown_status' => 'boolean',
			'countdown_start' => 'date',
			'countdown_details' => 'countdown_array',
			'countdown_color' => 'color',
			'subscribe_status' => 'boolean',
			'subscribe_text' => 'text',
			'subscribe_text_color' => 'color',
			'social_status' => 'boolean',
			'social_target' => 'boolean',
			'social_github' => 'text',
			'social_dribbble' => 'text',
			'social_twitter' => 'text',
			'social_facebook' => 'text',
			'social_pinterest' => 'text',
			'social_google+' => 'text',
			'social_linkedin' => 'text',
			'contact_status' => 'boolean',
			'contact_email' => 'email',
			'contact_effects' => 'text',
			'ga_status' => 'boolean',
			'ga_code' => 'text',
			'custom_css' => 'text_array'
		);

		foreach($modulesSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$this->_update_setting('modules', $setting, $this->_filter_input($assoc_args[$setting], $type));
			}
		}

		// social_google+ cannot be used a parameter, using social_googleplus instead
		if(isset($assoc_args['social_googleplus'])) {
			$this->_update_setting('modules', 'social_google+', $this->_filter_input($assoc_args['social_googleplus'], 'text'));
		}

		$successMsg = 'maintenance mode modules settings updated';
		$errorMsg = 'Could not update maintenance mode modules settings';

		$this->_save_settings($successMsg, $errorMsg);
	}

	/**
	 * Export subscribers in CSV format
	 *
	 * @subcommand export-subscribers
	 */
	public function export_subscribers($args = Array(), $assoc_args = Array()) {
		$this->_plugin_activated();

		$this->_pluginInstanceAdmin->subscribers_export();
	}

	/**
	 * Reset plugin settings
	 *
	 * #OPTIONS
	 * <tab>
	 * : Tab to reset (all|general|design)
	 *
	 * @subcommand reset-settings
	 */
	public function reset_settings($args = Array(), $assoc_args = Array()) {
		$this->_plugin_activated();

		$defaultSettings = $this->_pluginInstance->default_settings();

		list($tab) = $args;

		switch($tab) {
			case 'general':
				if($this->_settings['general'] != $defaultSettings['general']) {
					$this->_modifiedSettings = true;
					$this->_settings['general'] = $defaultSettings['general'];
				}
				break;

			case 'design':
				if($this->_settings['design'] != $defaultSettings['design']) {
					$this->_modifiedSettings = true;
					$this->_settings['design'] = $defaultSettings['design'];
				}
				break;

			case 'module':
				if($this->_settings['modules'] != $defaultSettings['modules']) {
					$this->_modifiedSettings = true;
					$this->_settings['modules'] = $defaultSettings['modules'];
				}
				break;

			case 'all':
				if($this->_settings['general'] != $defaultSettings['general']) {
					$this->_modifiedSettings = true;
					$this->_settings['general'] = $defaultSettings['general'];
				}

				if($this->_settings['design'] != $defaultSettings['design']) {
					$this->_modifiedSettings = true;
					$this->_settings['design'] = $defaultSettings['design'];
				}

				if($this->_settings['modules'] != $defaultSettings['modules']) {
					$this->_modifiedSettings = true;
					$this->_settings['modules'] = $defaultSettings['modules'];
				}

				break;
			default:
				WP_CLI::error("Invalid value '$tab' for reset-settings tab !");
				break;
		}

		$successMsg = $tab . ' settings reset';
		$errorMsg = 'Could not reset '.$tab.' settings';

		$this->_save_settings($successMsg, $errorMsg);
	}

	private function _plugin_activated() {
		if(!class_exists('WP_Maintenance_Mode')) {
			WP_CLI::error('wp-maintenance-mode plugin is not activated');
		}
		else {
			//compatible with wp-maintenance-mode from 2.0.0
			if(version_compare(WP_Maintenance_Mode::VERSION, '2.0.0', '<') == -1) {
				WP_CLI::error('You must have wp-maintenance-mode 2.0.0 or above to use this command !');
			}
			
			if($this->pluginInstance == NULL) {
				$this->_pluginInstance = WP_Maintenance_Mode::get_instance();
				$this->_settings = $this->_pluginInstance->get_plugin_settings();
			}

			if($this->_pluginInstanceAdmin == NULL) {
				if(!class_exists(WP_Maintenance_Mode_Admin)) {
					require_once(WPMM_CLASSES_PATH . 'wp-maintenance-mode-admin.php');
				}
				$this->_pluginInstanceAdmin = WP_Maintenance_Mode_Admin::get_instance();
			}
				
			return true;
		}
	}

	private function _filter_input($value, $type) {
		switch($type) {
			case 'date':
				$sane = sanitize_text($value);
				break;

			case 'html':
				$sane = sanitize_text_field($value);
				break;

			case 'text':
				$sane = sanitize_text_field($value);
				break;

			case 'color':
				$sane = sanitize_text_field($value);
				break;

			case 'boolean':
				if(!preg_match('/^(0|1)$/', $value)) {
					WP_CLI::error("Invalid value $value. Is not a boolean");
				}

				$sane = (int)$value;
				break;

			case 'role':
				//multiple roles can be given on cli (role1|role2)
				$roles = Array();
				$roles = explode('|', $value);
				$sane = Array();

				if(empty($roles)) {
					WP_CLI::error('no roles found from cli');
				}

				foreach($roles as $role) {
					if(!preg_match('/^[a-zA-Z0-9_]{1,}/', $role)) {
						WP_CLI::error("Invalid value $role. Is not a valid role name");
					}

					$sane[] = sanitize_text_field($role);
				}

				//multiple roles are only supported from 2.0.4 veresion
				if(version_compare(WP_Maintenance_Mode::VERSION, '2.0.4', '<') == -1) {
					if(count($roles) > 1) {
						WP_CLI::warning("Multiple roles are not supported in this version. Using the first one only");
					}

					$sane = $sane[0];
				}

				break;
			case 'url':
				$sane = esc_url($value);
				break;

			case 'countdown_array':
				$sane = Array();
				$fields = explode('|', $value);

				if(count($fields) != 3) {
					WP_CLI::error("Wrong parameter $value for countdown");
				}

				$sane['days'] = $this->_filter_input($fields[0], 'integer');
				$sane['hours'] = $this->_filter_input($fields[1], 'integer');
				$sane['minutes'] = $this->_filter_input($fields[2], 'integer');

				break;

			case 'text_array':
				$sane = Array();

				$fields = explode('|', $value);
				foreach($fields as $field) {
					$sane[] = $this->_filter_input($field, 'text');
				}

				break;

			case 'integer':
				$sane = preg_replace('[^0-9]', '', $value);
				break;

			default:
				WP_CLI::error("Could not validate input of invalid type: $type !");
				break;
		}

		return $sane;
	}

	//check if settings have changed before commiting to avoid false error reporting
	// update_option returns false if value did not change or failed
	private function _save_settings($successMsg, $errMsg) {
		if($this->_modifiedSettings == false) {
			WP_CLI::success($successMsg);
		}
		else {
			if(update_option('wpmm_settings', $this->_settings)) {
				$this->_pluginInstanceAdmin->delete_cache();

				WP_CLI::success($successMsg);
			}
			else {
				WP_CLI::error($errMsg);
			}
		}
	}

	private function _update_setting($tab, $key, $value) {

		if(!isset($this->_settings[$tab][$key])) {
			WP_CLI::error("Invalid option value $key for tab $tab");
		}

		// == operator works with values, arrays and instances	
		if($this->_settings[$tab][$key] == $value) {
			return;
		}

		$this->_settings[$tab][$key] = $value;
		$this->_modifiedSettings = true;
	}
}

WP_CLI::add_command('wp-maintenance-mode', 'wpMaintenanceMode');

?>
