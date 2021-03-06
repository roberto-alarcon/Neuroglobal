<?php
/**
 * Define callback functions for templater
 *
 * @package   Cherry_Services
 * @author    Cherry Team
 * @license   GPL-2.0+
 * @link      http://www.cherryframework.com/
 * @copyright 2015 Cherry Team
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * Callbcks for services shortcode templater
 *
 * @since  1.0.0
 */
class Cherry_Services_Template_Callbacks {

	/**
	 * Shortcode attributes array
	 * @var array
	 */
	public $atts = array();

	/**
	 * Current post services-related meta
	 * @var array
	 */
	public static $post_meta = array();

	function __construct( $atts ) {
		$this->atts = $atts;
	}

	/**
	 * Setup post meta varaiable
	 *
	 * @since 1.0.0
	 * @param array  $post_meta  existing post meta array
	 */
	static function setup_meta( $post_meta = null ) {

		if ( null != $post_meta ) {
			self::$post_meta = $post_meta;
			return $post_meta;
		}

		global $post;

		$post_meta = ( ! empty( self::$post_meta ) )
						? self::$post_meta
						: get_post_meta( $post->ID, CHERRY_SERVICES_POSTMETA, true );

		return $post_meta;

	}

	/**
	 * Get post title
	 * @since  1.0.0
	 */
	public function get_title( $tag = null ) {

		$format = '%3$s<a href="%2$s">%1$s</a>%4$s';

		if ( null !== $tag ) {
			$title_before = "<{$tag} class='cherry-services_title'>";
			$title_after  = "</{$tag}>";
		} else {
			$title_before = $this->atts['before_title'];
			$title_after  = $this->atts['after_title'];
		}

		return sprintf( $format, get_the_title(), get_permalink(), $title_before, $title_after );
	}

	/**
	 * Get post image
	 * @since  1.0.0
	 */
	public function get_image( $link = 'link' ) {

		global $post;

		$post_type = get_post_type( $post->ID );

		if ( ! post_type_supports( $post_type, 'thumbnail' ) ) {
			return;
		}

		if ( ! has_post_thumbnail() ) {
			return;
		}

		$post_meta = Cherry_Services_Template_Callbacks::setup_meta();

		if ( isset( $post_meta['show_thumb'] ) && 'no' == $post_meta['show_thumb'] ) {
			return;
		}
		if ( 'unlink' == $link ) {
			$format = '<figure class="cherry-services_thumb">%1$s</figure>';
		} else {
			$format = '<figure class="cherry-services_thumb"><a href="%2$s">%1$s</a></figure>';
		}

		$size = $this->atts['size'];

		if ( is_integer( $size ) ) {
			$size = array( $size, $size );
		} elseif ( ! is_string( $size ) ) {
			$size = 'thumbnail';
		}

		$image  = get_the_post_thumbnail(
			$post->ID,
			$size,
			array( 'alt' => get_the_title() )
		);

		return sprintf( $format, $image, get_permalink() );
	}

	/**
	 * Get post icon
	 * @since 1.0.0
	 */
	public function get_icon() {

		$post_meta = Cherry_Services_Template_Callbacks::setup_meta();

		if ( empty( $post_meta['font-icon'] ) ) {
			return;
		}

		$icon   = '<i class="' . esc_attr( $post_meta['font-icon'] ) . '"></i>';
		$format = '<div class="cherry-services_icon">%s</div>';

		return sprintf( $format, $icon );

	}

	/**
	 * Get post features
	 * @since 1.0.0
	 */
	public function get_features() {

		$post_meta = Cherry_Services_Template_Callbacks::setup_meta();

		if ( empty( $post_meta['fetures-text'] ) ) {
			return;
		}

		$format = '<div class="cherry-services_feauters">%s</div>';
		return sprintf( $format, htmlspecialchars_decode( $post_meta['fetures-text'] ) );

	}

	/**
	 * Get post exerpt
	 * @since 1.0.0
	 */
	public function get_excerpt() {

		global $post;

		$post_type = get_post_type( $post->ID );

		$excerpt = has_excerpt( $post->ID ) ? apply_filters( 'the_excerpt', get_the_excerpt() ) : '';

		if ( ! $excerpt ) {

			$excerpt_length = ( ! empty( $this->atts['excerpt_length'] ) )
								? $this->atts['excerpt_length']
								: 20;

			$content = get_the_content();
			$excerpt = strip_shortcodes( $content );
			$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
			$excerpt = wp_trim_words( $excerpt, $excerpt_length, '' );

		}

		$format = '<div class="cherry-services_excerpt">%s</div>';

		return sprintf( $format, $excerpt );

	}

	/**
	 * Get post content
	 * @since  1.0.0
	 */
	public function get_content() {

		$content = apply_filters( 'the_content', get_the_content() );

		if ( ! $content ) {
			return;
		}

		$format = '<div class="post-content">%s</div>';

		return sprintf( $format, $content );
	}

	/**
	 * Get post features
	 * @since 1.0.0
	 */
	public function get_price() {

		$post_meta = Cherry_Services_Template_Callbacks::setup_meta();

		if ( empty( $post_meta['price'] ) ) {
			return;
		}

		$format = '<div class="cherry-services_price">%s</div>';
		return sprintf( $format, htmlspecialchars_decode( $post_meta['price'] ) );

	}

	/**
	 * Get order button
	 * @since  1.0.0
	 */
	public function get_order_button( $class = 'cherry-btn cherry-btn-primary' ) {

		global $post;

		$format = '<a href="%2$s" class="%3$s">%1$s</a>';
		$text   = ! empty( $this->atts['order_button_text'] )
					? $this->atts['order_button_text']
					: __( 'Order', 'cherry-services' );
		$class  = esc_attr( $class );
		$meta   = Cherry_Services_Template_Callbacks::setup_meta();
		$url    = ! empty( $meta['order-url'] ) ? esc_url( $meta['order-url'] ) : get_permalink( $post->ID );

		return sprintf( $format, $text, $url, $class );

	}

	/**
	 * Get read more button
	 * @since  1.0.0
	 */
	public function get_more_button( $class = 'cherry-btn cherry-btn-primary' ) {

		$format = '<a href="%2$s" class="%3$s">%1$s</a>';
		$text   = $this->atts['button_text'];
		$class  = esc_attr( $class );
		$url    = get_permalink();

		return sprintf( $format, $text, $url, $class );

	}

}