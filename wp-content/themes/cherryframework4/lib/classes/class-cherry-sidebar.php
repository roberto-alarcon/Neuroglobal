<?php
/**
 * Determines whether or not to display the sidebar based on an array of conditional tags or page templates.
 * If any of the is_* conditional tags or is_page_template(template_file) checks return true,
 * the sidebar will NOT be displayed.
 *
 * @package    Cherry_Framework
 * @subpackage Classes
 * @author     Cherry Team <support@cherryframework.com>
 * @copyright  Copyright (c) 2012 - 2015, Cherry Team
 * @link       http://www.cherryframework.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * Class for determines whether or not to display the sidebar.
 * Class is based on Sage Starter Theme by Roots.
 *
 * @author  Roots
 * @author  Cherry Team <support@cherryframework.com>
 * @link    https://roots.io/sage/
 * @license http://opensource.org/licenses/MIT
 * @since   4.0.0
 */
class Cherry_Sidebar {
	/**
	 * Stores the conditional tags.
	 *
	 * @since 4.0.0
	 * @var   array
	 */
	private $conditionals;

	/**
	 * Stores the templates filename with ext.
	 *
	 * @since 4.0.0
	 * @var   array
	 */
	private $templates;

	/**
	 * Display or not the sidebar?
	 *
	 * @since 4.0.0
	 * @var   boolean
	 */
	public $display = true;

	/**
	 * Initialize new Cherry_Sidebar's instance.
	 *
	 * @since 4.0.0
	 * @param array $conditionals List of conditional tags (http://codex.wordpress.org/Conditional_Tags)
	 * @param array $templates    List of page templates. These will be checked via is_page_template()
	 */
	public function __construct( $conditionals = array(), $templates = array() ) {
		$this->conditionals = $conditionals;
		$this->templates    = $templates;

		$conditionals = array_map( array( $this, 'check_conditional_tag' ), $this->conditionals );
		$templates    = array_map( array( $this, 'check_page_template' ), $this->templates );

		if ( in_array( true, $conditionals ) || in_array( true, $templates ) ) {
			$this->display = false;
		}
	}

	/**
	 * This function to execute conditional tag(s).
	 *
	 * @since  4.0.0
	 * @param  string|array $conditional_tag
	 * @return boolean
	 */
	private function check_conditional_tag( $conditional_tag ) {
		$conditional_arg = is_array( $conditional_tag ) ? $conditional_tag[1] : false;
		$conditional_tag = $conditional_arg ? $conditional_tag[0] : $conditional_tag;

		if ( !function_exists( $conditional_tag ) ) {
			return false;
		}

		return $conditional_arg ? call_user_func( $conditional_tag, $conditional_arg ) : call_user_func( $conditional_tag );
	}

	/**
	 * This function allows to determine if you are in any page template.
	 *
	 * @since  4.0.0
	 * @param  string $page_template Full template filename with ext
	 * @return boolean
	 */
	private function check_page_template( $page_template ) {
		return is_page_template( $page_template );
	}
}