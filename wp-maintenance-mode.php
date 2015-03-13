<?php

if(!defined('WP_CLI')) { return; }

/**
 * Control wp-maintenance-mode plugin
 *
 * @author dpiquet <piqudam@gmail.com>
 */

class wpMaintenanceMode extends WP_CLI_Command {

	protected $_pluginInstance = NULL;

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
			'backend_role' => 'cap',
			'frontend_role' => 'cap',
			'meta_robots' => 'boolean',
			'redirection' => 'text',
			'notice' => 'boolean',
			'admin_link' => 'boolean'
		);

		foreach($generalSettings as $setting => $type) {
			if(isset($assoc_args[$setting])) {
				$settings['general'][$setting] = $this->_filter_input($assoc_args[$setting], $type);
			}
		}

		if(update_option('wpmm_settings', $settings)) {
			WP_CLI::success('maintenance mode general settings updated');
		}
		else {
			WP_CLI::error('Could not update maintenance mode general settings');
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
				
			return true;
		}
	}

	//todo
	private function _filter_input($value, $type) {
		switch($type) {
			case 'text':
				$sane = $value;
				break;

			case 'color':
				$sane = $value;
				break;

			case 'boolean':
				$sane = $value;
				break;

			case 'cap':
				$sane = $value;
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
