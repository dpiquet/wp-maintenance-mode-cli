<?php

if(!defined('WP_CLI')) { return; }

/**
 * Control wp-maintenance-mode plugin
 *
 * @author dpiquet <piqudam@gmail.com>
 */

class wpMaintenanceMode extends WP_CLI_Command {

	protected $_pluginInstance = NULL;
	protected $_pluginInstanceAdmin = NULL;

	/**
	 * Turn maintenance mode on
	 *
	 *
	 */
	public function enable( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$networkWide = false;

		$settings = $this->_pluginInstance->get_plugin_settings();

		$settings['general']['status'] = 1;

		if(update_option('wpmm_settings', $settings)) {
			$this->_pluginInstanceAdmin->delete_cache();
			WP_CLI::success('Maintenance mode enabled');
		}
		else {
			WP_CLI::error('Maintenance mode could not be enabled');
		}
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

		$settings = $this->_pluginInstance->get_plugin_settings();
		$settings['general']['status'] = 0;

		if(update_option('wpmm_settings', $settings)) {
			$this->_pluginInstanceAdmin->delete_cache();
			WP_CLI::success('Maintenance mode disabled');
		}
		else {
			WP_CLI::error('Maintenance mode could not be disabled');
		}
	}

	/**
	 * Show wp-maintenance-mode settings
	 *
	 * @subcommand show-settings
	 */
	public function show_settings( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$settings = $this->_pluginInstance->get_plugin_settings();

		print_r($settings);

		foreach($settings['general'] as $key => $value) {
			if( is_array($value) || is_object($value)) {
				//continue;
			}

			WP_CLI::line($key. ' '.$value);
		}
	}

	/**
	 * Update maintenance nag design
	 *
	 * ## OPTIONS
	 *
	 * @synopsis [--title=<title>] [--heading=<heading>] [--heading-color=<color>] [--text=<text>] [--text-color=<color>]
	 *
	 * @subcommand update-design-option
	 */

	public function update_design( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$settings = $this->_pluginInstance->get_plugin_settings();

		$designSettings = Array(
			'title' => 'text',
			'heading' => 'text',
			'heading_color' => 'color',
			'text' => 'text',
			'text_color' => 'color',
			'bg_type' => 'boolean',
			'bg_color' => 'color',
			'bg_custom' => 'boolean',
			'bg_predefined' => 'text'
		);

		foreach($designSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$settings['design'][$setting] = $this->_filter_input($assoc_args[$setting], $type);
			}
		}

		if(update_option('wpmm_settings', $settings)) {
			$this->_pluginInstanceAdmin->delete_cache();
			WP_CLI::success('maintenance nag design updated');
		}
		else {
			WP_CLI::error('Could not update maintenance nag design');
		}
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
	 * @synopsis [--status=(0|1)] [--bypass_bots=(0|1)] [--backend_role=<cap>] [--frontend_role=<cap>] [--meta_robots=(0|1)] [--redirection=<url>] [--notice=(0|1)] [--admin_link=(0|1)]
	 *
	 * @subcommand update-general
	 */
	public function update_general( $args = Array(), $assoc_args = Array() ) {
		$this->_plugin_activated();

		$settings = $this->_pluginInstance->get_plugin_settings();

		$generalSettings = Array(
			'status' => 'boolean',
			'bypass_bots' => 'boolean',
			'backend_role' => 'role',
			'frontend_role' => 'role',
			'meta_robots' => 'boolean',
			'redirection' => 'text',
			'notice' => 'boolean',
			'admin_link' => 'boolean',
			'exclude' => 'exclude'
		);

		foreach($generalSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$settings['general'][$setting] = $this->_filter_input($assoc_args[$setting], $type);
			}
		}

		if(update_option('wpmm_settings', $settings)) {
			$this->_pluginInstanceAdmin->delete_cache();
			WP_CLI::success('maintenance mode general settings updated');
		}
		else {
			WP_CLI::error('Could not update maintenance mode general settings');
		}

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
		$settings = $this->_pluginInstance->get_plugin_settings();

		list($tab) = $args;

		switch($tab) {
			case 'general':
				$settings['general'] = $defaultSettings['general'];
				break;

			case 'design':
				$settings['design'] = $defaultSettings['design'];
				break;

			case 'all':	
				$settings['general'] = $defaultSettings['general'];
				$settings['design'] = $defaultSettings['design'];
				break;
			default:
				WP_CLI::error("Invalid value '$tab' for reset-settings tab !");
				break;
		}

		if(update_option('wpmm_settings', $settings)) {
			$this->_pluginInstanceAdmin->delete_cache();
			WP_CLI::success('settings reset');
		}
		else {
			WP_CLI::error('Could not reset settings');
		}

	}

	private function _plugin_activated() {
		if(!class_exists('WP_Maintenance_Mode')) {
			WP_CLI::error('wp-maintenance-mode plugin is not activated');
		}
		else {
			if($this->pluginInstance == NULL) {
				$this->_pluginInstance = WP_Maintenance_Mode::get_instance();
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
				if(version_compare(WP_Maintenance_Mode::VERSION, '2.0.4', '<')) {
					if(count($roles) > 1) {
						WP_CLI::warning("Multiple roles are not supported in this version. Using the first one only");
					}

					$sane = $sane[0];
				}

				break;
			case 'url':
				$sane = esc_url($value);
				break;

			case 'exclude':
				$excluded = explode('|', $value);
				$sane = Array();

				foreach($excluded as $exclude) {
					$sane[] = sanitize_text_field($exclude);
				}

				break;

			default:
				WP_CLI::error("Could not validate input of invalid type: $type !");
				break;
		}

		return $sane;
	}

}

WP_CLI::add_command('wp-maintenance-mode', 'wpMaintenanceMode');

?>
