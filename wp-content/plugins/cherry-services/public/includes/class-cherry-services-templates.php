<?php
/**
 * Cherry Services templates loader
 *
 * @package   Cherry_Services
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

/**
 * Class for including page templates.
 *
 * @since 1.0.0
 */
class Cherry_Services_Templater {

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Posts number per team archive and page template
	 *
	 * @since 1.0.0
	 * @var   integer
	 */
	public static $posts_per_archive_page = 6;

	/**
	 * The array of templates that this plugin tracks.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $templates;

	/**
	 * Sets up needed actions/filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->templates = array();

		// Set posts per archive services page
		add_action( 'pre_get_posts', array( $this, 'set_posts_per_archive_page' ) );

		// Add a filter to the page attributes metabox to inject our template into the page template cache.
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'register_templates' ) );

		// Add a filter to the save post in order to inject out template into the page cache.
		add_filter( 'wp_insert_post_data', array( $this, 'register_templates' ) );

		// Add a filter to the template include in order to determine if the page has our template assigned and return it's path.
		add_filter( 'template_include', array( $this, 'view_template' ) );

		// Add a filter to load a custom template for a given post.
		add_filter( 'single_template', array( $this, 'get_single_template' ) );

		add_filter( 'cherry_attr_post', array( $this, 'page_template_classes' ), 10, 2 );

		// Add your templates to this array.
		$this->templates = array(
			'template-services.php' => __( 'Services Page', 'cherry-services' ),
		);

		// Adding support for theme templates to be merged and shown in dropdown.
		$templates = wp_get_theme()->get_page_templates();
		$templates = array_merge( $templates, $this->templates );

		/**
		 * Filter posts per archive page value
		 * @var int
		 */
		self::$posts_per_archive_page = apply_filters(
			'cherry_services_posts_per_archive_page',
			self::$posts_per_archive_page
		);
	}

	function set_posts_per_archive_page( $query ){
		if( ! is_admin()
			&& ( $query->is_post_type_archive( CHERRY_SERVICES_NAME ) || $query->is_tax( 'group' ) )
			&& $query->is_main_query() ) {
				$query->set( 'posts_per_page', self::$posts_per_archive_page );
		}
	}

	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 *
	 * @since  1.0.0
	 * @param  array $atts The attributes for the page attributes dropdown.
	 * @return array $atts The attributes for the page attributes dropdown.
	 */
	public function register_templates( $atts ) {

		// Create the key used for the themes cache.
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list. If it doesn't exist, or it's empty prepare an array.
		$templates = wp_cache_get( $cache_key, 'themes' );

		if ( empty( $templates ) ) {
			$templates = array();
		}

		// Since we've updated the cache, we need to delete the old cache.
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing available templates.
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;
	}

	/**
	 * Checks if the template is assigned to the page.
	 *
	 * @since 1.0.0
	 */
	public function view_template( $template ) {

		global $post;

		// check if we need archive template to include
		if ( is_post_type_archive( CHERRY_SERVICES_NAME ) || is_tax( 'group' ) ) {

			$file = trailingslashit( CHERRY_SERVICES_DIR ) . 'templates/archive-services.php';

			if ( file_exists( $file ) ) {
				return $file;
			}

		}

		if ( ! is_page( $post ) ) {
			return $template;
		}

		$page_template_meta = get_post_meta( $post->ID, '_wp_page_template', true );

		if ( !isset( $this->templates[ $page_template_meta ] ) ) {
			return $template;
		}

		$file = trailingslashit( CHERRY_SERVICES_DIR ) . 'templates/' . $page_template_meta;

		// Just to be safe, we check if the file exist first.
		if ( file_exists( $file ) ) {
			return $file;
		}

		return $template;
	}

	/**
	 * Adds a custom single template for a 'Team' post.
	 *
	 * @since 1.0.0
	 */
	public function get_single_template( $template ) {
		global $post;

		if ( $post->post_type == CHERRY_SERVICES_NAME ) {
			$template = trailingslashit( CHERRY_SERVICES_DIR ) . 'templates/single-services.php';
		}

		return $template;
	}

	public function page_template_classes( $atts, $context ) {

		if ( 'services-template' != $context ) {
			return $atts;
		}

		$atts['class'] .= ' services-page';

		return $atts;
	}

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}
}

Cherry_Services_Templater::get_instance();