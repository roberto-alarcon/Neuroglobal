<?php
/**
 * Add cherry theme export sample content controllers
 *
 * @package   cherry_data_manager
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * main exporter class
 *
 * @since  1.0.0
 */
class cherry_dm_content_exporter {

	/**
	 * options array to export
	 *
	 * @since  1.0.0
	 */
	public $export_options = array();

	/**
	 * options with page ids (to rewrite on import)
	 *
	 * @since  1.0.0
	 */
	public $options_ids = array();

	function __construct() {

		include_once( ABSPATH . '/wp-admin/includes/class-pclzip.php' );
		require_once( ABSPATH . '/wp-admin/includes/export.php' );

		add_action( 'init', array( $this, 'set_options' ) );
		add_action( 'wp_ajax_cherry_data_manager_export', array( $this, 'export_handle' ) );

	}

	public function set_options() {

		$theme     = get_option( 'stylesheet' );
		$theme_opt = get_option( 'cherry-options' );

		if ( is_array( $theme_opt ) ) {
			$theme_opt          = $theme_opt['id'];
			$theme_opt_defaults = $theme_opt . '_defaults';
		} else {
			$theme_opt          = false;
			$theme_opt_defaults = false;
		}

		$this->export_options = apply_filters(
			'cherry_data_manager_export_options',
			array(
				'blogname',
				'blogdescription',
				'users_can_register',
				'posts_per_page',
				'date_format',
				'time_format',
				'thumbnail_size_w',
				'thumbnail_size_h',
				'thumbnail_crop',
				'medium_size_w',
				'medium_size_h',
				'large_size_w',
				'large_size_h',
				'theme_mods_' . $theme,
				'show_on_front',
				'page_on_front',
				'page_for_posts',
				'cherry-options',
				$theme . '_statics',
				$theme . '_statics_defaults',
				$theme_opt,
				$theme_opt_defaults
			)
		);

		$this->options_ids = apply_filters(
			'cherry_data_manager_options_ids',
			array( 'page_on_front', 'page_for_posts' )
		);
	}

	/**
	 * Export content handler
	 *
	 * @since 1.0.0
	 */
	public function export_handle() {

		global $cherry_data_manager;

		if ( !current_user_can( 'export' ) ) {
			die();
		}

		$exclude_files = apply_filters(
			'cherry_data_manager_exclude_files_from_export',
			array('xml', 'json')
		);

		$exclude_folder = apply_filters(
			'cherry_data_manager_exclude_folder_from_export',
			array( 'woocommerce_uploads', 'templates', 'wc-logs' )
		);

		$response = array(
			'what'   => 'status',
			'action' => 'export_content',
			'id'     => '1',
			'data'   => __( 'Export content done', $cherry_data_manager->slug ),
			'file'   => ''
		);

		$upload_dir      = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$zip_name = $upload_base_dir . '/sample_data.zip';

		// delete sample data zip if already exist
		$this->delete_file( $zip_name );

		$this->pack_templates( $upload_base_dir );

		if ( is_dir( $upload_base_dir ) ) {
			$file_string = $this->scan_dir( $upload_base_dir, $exclude_folder, $exclude_files );
		}

		if ( empty( $file_string ) ) {
			die();
		}

		// init zipper
		$zip    = new PclZip( $zip_name );
		$result = $zip->create( $file_string, PCLZIP_OPT_REMOVE_ALL_PATH );

		//export widgets
		$widgets_json_file = $this->do_export_widgets();

		//export options
		$options_json_files = $this->do_export_options();
		$base_options       = $options_json_files['options'];
		$ids_options        = $options_json_files['ids'];

		if ( is_wp_error($widgets_json_file) ) {
			$response['data'] = "Error : " . $widgets_json_file->get_error_message();
		} else {
			$zip->add($widgets_json_file, PCLZIP_OPT_REMOVE_ALL_PATH);
			$this->delete_file($widgets_json_file);
		}

		if ( is_wp_error($base_options) ) {
			$response['data'] = "Error : " . $base_options->get_error_message();
		} else {
			$zip->add($base_options, PCLZIP_OPT_REMOVE_ALL_PATH);
			$this->delete_file($base_options);
		}

		if ( is_wp_error($ids_options) ) {
			$response['data'] = "Error : " . $ids_options->get_error_message();
		} else {
			$zip->add($ids_options, PCLZIP_OPT_REMOVE_ALL_PATH);
			$this->delete_file($ids_options);
		}

		//export xml
		$xml_file = $this->do_export_xml();

		if( is_wp_error($xml_file) ) {
			$response['data'] = "Error : " . $xml_file->get_error_message();
		} else {
			$zip->add($xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
			$this->delete_file($xml_file);
		}

		// export tables
		$tables_file = $this->export_custom_tables();
		if ( $tables_file ) {
			$zip->add($tables_file, PCLZIP_OPT_REMOVE_ALL_PATH);
			$this->delete_file($tables_file);
		}

		$zip_name = $this->path_to_url( $zip_name );

		if ( $result == 0 ) {
			$response['data'] = "Error : " . $zip->errorInfo(true);
		} else {
			$response['file'] = $zip_name;
		}

		wp_send_json( $response );

	}

	/**
	 * Export custom database tables to SQL
	 *
	 * @since 1.0.0
	 */
	public function export_custom_tables() {

		global $wpdb;

		$tables_to_export = apply_filters(
			'cherry_dm_export_database_tables',
			array( 'mpsl_sliders', 'mpsl_slides' )
		);

		if ( ! is_array( $tables_to_export ) || empty( $tables_to_export ) ) {
			return false;
		}

		$tables = array();

		foreach ( $tables_to_export as $table ) {

			$full_name = $wpdb->prefix . $table;
			$data      = $wpdb->get_results( "SELECT * FROM $full_name" );
			$length    = count( $data );
			// do not import large tables - server falls while importing if we will
			if ( ! $data || $length > 500 ) {
				continue;
			}

			$tables[$table] = $data;
		}

		$upload_dir      = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];
		$json_dir        = $upload_base_dir . '/tables.json';

		$this->export_to_json_file( $json_dir, $tables );

		return $json_dir;
	}

	/**
	 * change file path to absolute url
	 *
	 * @since  1.0.0
	 */
	public function path_to_url( $path ) {
		//$home_url  = '/' . preg_quote( home_url('/'), '/' ) . '/';
		$home_path = '/' . preg_quote( ABSPATH, '/' ) . '/';
		return preg_replace( $home_path, home_url( '/' ), $path );
	}

	/**
	 * delete selected file by path
	 *
	 * @since  1.0.0
	 */
	public function delete_file( $file ) {

		if ( is_readable($file) ){
			unlink( $file );
			return 'file deleted';
		}

		return 'file is missing';
	}

	/**
	 * recursive function for grabbing all files
	 *
	 * @since  1.0.0
	 */
	public function scan_dir( $dir, $exceptions_folder, $exceptions_files ) {

		$exceptions_folder    = array_merge(array('.', '..'), $exceptions_folder);
		$scand_dir            = array_diff( scandir($dir), $exceptions_folder);
		$scan_dir_strings     = array();
		$extensionend_file    = "";
		$cropped_file_pattern = '/^.+-(\d+x\d+)\..+$/';

		if ( !is_array($scand_dir) ) {
			return;
		}

		foreach ( $scand_dir as $file ) {
			$scan_file         = $dir . '/' . $file;
			$file_extension    = explode(".", $scan_file);
			$extensionend_file = end( $file_extension );

			if ( is_dir( $scan_file ) ) {
				$scan_file = $this->scan_dir( $scan_file, $exceptions_folder, $exceptions_files );
			} elseif ( in_array( $extensionend_file, $exceptions_files ) ) {
				$scan_file = "";
			}

			// get only original size files (not cropped)
			if ( !preg_match( $cropped_file_pattern, $scan_file ) ) {
				array_push( $scan_dir_strings, $scan_file );
			}

		}

		return implode( ',', $scan_dir_strings );

	}

	/**
	 * Grab template files into own zip
	 *
	 * @since  1.0.0
	 */
	public function pack_templates( $upload_base_dir ) {

		$templates_dir = $upload_base_dir . '/templates';

		// Check if templates dir exist
		if ( ! file_exists( $templates_dir ) ) {
			return false;
		}

		// scan templates dir
		$templates = $this->scan_dir( $templates_dir, array(), array() );
		// pack templates to zip
		$zip_name = $upload_base_dir . '/templates.zip';
		// delete templates file if already exists
		$this->delete_file( $zip_name );

		$zip    = new PclZip( $zip_name );
		$result = $zip->create( $templates, PCLZIP_OPT_REMOVE_PATH, $templates_dir );

	}

	/**
	 * export content into XML file
	 *
	 * @since 1.0.0
	 */
	function do_export_xml() {

		ob_start();
		export_wp();
		$xml = ob_get_clean();
		$xml = iconv('utf-8', 'utf-8//IGNORE', $xml);
		$xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $xml);

		$upload_dir      = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$xml_dir = $upload_base_dir . '/sample_data.xml';
		file_put_contents( $xml_dir, $xml );

		return $xml_dir;
	}

	/**
	 * Export widgets into JSON file
	 *
	 * @since  1.0.0
	 */
	public function do_export_widgets() {

		$themename        = 'cherry';
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		$sidebar_export   = array_filter( $sidebars_widgets, array( $this, 'sort_widget_array' ) );

		$widgets = array();

		foreach ($sidebar_export as $sidebar_widgets => $sidebar_widget) {
			foreach ($sidebar_widget as $k) {
				$widgets[] = array(
					'type'       =>trim( substr( $k, 0, strrpos( $k, '-' ) ) ),
					'type-index' =>trim( substr( $k, strrpos( $k, '-' ) + 1 ) )
				);
			}
		}

		$widgets_array = array( );

		foreach ( $widgets as $widget ) {

			$widget_val                                            = get_option( 'widget_' . $widget['type'] );
			$multiwidget_val                                       = $widget_val['_multiwidget'];
			$widgets_array[$widget['type']][$widget['type-index']] = $widget_val[$widget['type-index']];

			if ( isset( $widgets_array[$widget['type']]['_multiwidget'] ) ) {
				unset( $widgets_array[$widget['type']]['_multiwidget'] );
			}

			$widgets_array[$widget['type']]['_multiwidget'] = $multiwidget_val;

			unset($widgets_array[$widget['type']][$widget['type-index']][$themename.'_widget_rules_type_'.$widget['type'].'-'.$widget['type-index']]);
			unset($widgets_array[$widget['type']][$widget['type-index']][$themename.'_widget_rules_'.$widget['type'].'-'.$widget['type-index']]);
			unset($widgets_array[$widget['type']][$widget['type-index']][$themename.'_widget_custom_class_'.$widget['type'].'-'.$widget['type-index']]);
			unset($widgets_array[$widget['type']][$widget['type-index']][$themename.'_widget_responsive_'.$widget['type'].'-'.$widget['type-index']]);
			unset($widgets_array[$widget['type']][$widget['type-index']][$themename.'_widget_users_'.$widget['type'].'-'.$widget['type-index']]);

			if ( isset($widgets_array[$widget['type']][$widget['type-index']]['nav_menu']) ) {
				$term = get_term_by( 'id', $widgets_array[$widget['type']][$widget['type-index']]['nav_menu'], 'nav_menu' );
				if ($term) {
					$widgets_array[$widget['type']][$widget['type-index']]['nav_menu_slug'] = $term->slug;
				}
			}
		}

		unset( $widgets_array['export'] );
		unset( $widgets_array['Widget-Settings'] );
		unset( $widgets_array[''] );

		$options_type = get_option( $themename . '_widget_rules_type' );
		$options      = get_option( $themename . '_widget_rules' );
		$custom_class = get_option( $themename . '_widget_custom_class' );
		$responsive   = get_option( $themename . '_widget_responsive' );
		$users        = get_option( $themename . '_widget_users' );

		if ( !empty($options_type) && is_array($options_type) ) {
			$rules_array['widget_rules_type'] = array($options_type);
		}
		if ( !empty($options) && is_array($options) ) {
			$rules_array['widget_rules'] = array($options);
		}
		if ( !empty($custom_class) && is_array($custom_class) ) {
			$rules_array['widget_custom_class'] = array($custom_class);
		}
		if ( !empty($responsive) && is_array($responsive) ) {
			$rules_array['widget_responsive'] = array($responsive);
		}
		if ( !empty($users) && is_array($users) ) {
			$rules_array['widget_users'] = array($users);
		}
		if ( !isset($rules_array)) {
			$rules_array = array();
		}

		$upload_dir      = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$export_array = array( $sidebar_export, $widgets_array, $rules_array );
		$json_dir     = $upload_base_dir . '/widgets.json';
		$this->export_to_json_file( $json_dir, $export_array );

		return $json_dir;

	}

	/**
	 * Sort widgets array
	 *
	 * @since 1.0.0
	 */
	function sort_widget_array( $array ) {
		return (!empty($array) && is_array($array));
	}


	/**
	 * Export options into JSON file
	 *
	 * @since  1.0.0
	 */
	public function do_export_options() {

		// prepare result arrays
		$export_array = array();
		$rewrite_ids  = array(
			'posts' => array(),
			'menus' => array()
		);

		// put posts(page) id's to exported array
		$export_array         = $this->prepare_options_to_export();
		$rewrite_ids['posts'] = $this->prepare_options_ids_to_export();

		// put nav menus to exported array
		$menus                = get_nav_menu_locations();
		$rewrite_ids['menus'] = array_flip($menus);

		// write all options
		$upload_dir      = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'];

		$json_dir = $upload_base_dir . '/options.json';
		$this->export_to_json_file( $json_dir, $export_array );

		// write mirrored options wich contain page ids
		$ids_json_dir = $upload_base_dir . '/rewrite-ids.json';
		$this->export_to_json_file( $ids_json_dir, $rewrite_ids );

		return array( 'options' => $json_dir, 'ids' => $ids_json_dir );

	}

	/**
	 * Put array into file in JSON format
	 *
	 * @since  1.0.0
	 *
	 * @param  string  $file  filename
	 * @param  array   $data  array to import
	 * @return void|bool false om failure
	 */
	function export_to_json_file( $file, $data ) {
		$json = json_encode( $data );
		$json = iconv('utf-8', 'utf-8//IGNORE', $json);
		$json = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $json);

		file_put_contents( $file, $json );
	}

	/**
	 * Grab options to export into array
	 * @since 1.0.0
	 */
	public function prepare_options_to_export() {

		$export_array = array();

		if ( !is_array($this->export_options) ) {
			return $export_array;
		}

		foreach ( $this->export_options as $option ) {
			$export_array[$option] = $val = get_option( $option );
		}

		return $export_array;

	}

	/**
	 * Grab options ID's to export into array
	 * @since 1.0.0
	 */
	public function prepare_options_ids_to_export() {

		$rewrite_ids = array();

		if ( !is_array( $this->options_ids ) ) {
			return $rewrite_ids;
		}

		foreach ( $this->options_ids as $option ) {

			$val = get_option( $option );

			if ( !$val ) {
				continue;
			}

			$rewrite_ids[$val][] = $option;
		}

		return $rewrite_ids;
	}

}

global $cherry_data_manager;
$cherry_data_manager->exporter = new cherry_dm_content_exporter();