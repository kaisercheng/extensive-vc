<?php

namespace ExtensiveVC\Shortcodes\EVCTestimonials;

use ExtensiveVC\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EVCTestimonials' ) ) {
	class EVCTestimonials extends Shortcodes\EVCShortcode {
		
		/**
		 * Singleton variables
		 */
		private static $instance;
		
		/**
		 * Constructor
		 */
		function __construct() {
			$this->setBase( 'evc_testimonials' );
			$this->setShortcodeName( esc_html__( 'Testimonials', 'extensive-vc' ) );
			$this->setShortcodeParameters( $this->shortcodeParameters() );
			
			// Parent constructor need to be loaded after setter's method initialization
			parent::__construct( array( 'isInCPT' => true ) );
			
			// Additional methods need to be loaded after parent constructor loaded if we used methods from the parent class
			if ( $this->getIsShortcodeEnabled() ) {
				add_action( 'extensive_vc_enqueue_additional_scripts_before_main_js', array( $this, 'enqueueShortcodeAdditionalScripts' ) );
				
				//Testimonials category filter
				add_filter( 'vc_autocomplete_evc_testimonials_category_callback', array( $this, 'testimonialsCategoryAutocompleteSuggester' ), 10, 1 ); // Get suggestion(find). Must return an array
				
				//Testimonials category render
				add_filter( 'vc_autocomplete_evc_testimonials_category_render', array( $this, 'testimonialsCategoryAutocompleteRender' ), 10, 1 ); // Get suggestion(find). Must return an array
			}
		}
		
		/**
		 * Get the instance of ExtensiveVCFramework
		 *
		 * @return self
		 */
		public static function getInstance() {
			if ( self::$instance == null ) {
				return new self;
			}
			
			return self::$instance;
		}
		
		/**
		 * Include necessary 3rd party scripts for this shortcode
		 */
		function enqueueShortcodeAdditionalScripts() {
			wp_enqueue_style( 'owl-carousel', EXTENSIVE_VC_ASSETS_URL_PATH . '/plugins/owl-carousel/owl.carousel.min.css' );
			wp_enqueue_script( 'owl-carousel', EXTENSIVE_VC_ASSETS_URL_PATH . '/plugins/owl-carousel/owl.carousel.min.js', array( 'jquery' ), false, true );
		}
		
		/**
		 * Set shortcode parameters for Visual Composer shortcodes options panel
		 */
		function shortcodeParameters() {
			$params = array(
				array(
					'type'        => 'textfield',
					'param_name'  => 'custom_class',
					'heading'     => esc_html__( 'Custom CSS Class', 'extensive-vc' ),
					'description' => esc_html__( 'Style particular content element differently - add a class name and refer to it in custom CSS', 'extensive-vc' )
				),
				array(
					'type'        => 'textfield',
					'param_name'  => 'number',
					'heading'     => esc_html__( 'Number of Testimonials', 'extensive-vc' ),
					'description' => esc_html__( 'Enter number of testimonials or leave empty for showing all testimonials', 'extensive-vc' )
				),
				array(
					'type'        => 'autocomplete',
					'param_name'  => 'category',
					'heading'     => esc_html__( 'Category', 'extensive-vc' ),
					'description' => esc_html__( 'Enter one category slug or leave empty for showing all categories', 'extensive-vc' )
				),
				array(
					'type'       => 'dropdown',
					'param_name' => 'carousel_loop',
					'heading'    => esc_html__( 'Enable Slider Loop', 'extensive-vc' ),
					'value'      => array_flip( extensive_vc_get_yes_no_select_array( false, true ) ),
					'group'      => esc_html__( 'Slider Settings', 'extensive-vc' )
				),
				array(
					'type'       => 'dropdown',
					'param_name' => 'carousel_autoplay',
					'heading'    => esc_html__( 'Enable Slider Autoplay', 'extensive-vc' ),
					'value'      => array_flip( extensive_vc_get_yes_no_select_array( false, true ) ),
					'group'      => esc_html__( 'Slider Settings', 'extensive-vc' )
				),
				array(
					'type'        => 'textfield',
					'param_name'  => 'carousel_speed',
					'heading'     => esc_html__( 'Slide Duration (ms)', 'extensive-vc' ),
					'description' => esc_html__( 'Speed of slide in milliseconds. Default value is 5000', 'extensive-vc' ),
					'group'       => esc_html__( 'Slider Settings', 'extensive-vc' )
				),
				array(
					'type'        => 'textfield',
					'param_name'  => 'carousel_speed_animation',
					'heading'     => esc_html__( 'Slide Animation Duration (ms)', 'extensive-vc' ),
					'description' => esc_html__( 'Speed of slide animation in milliseconds. Default value is 600', 'extensive-vc' ),
					'group'       => esc_html__( 'Slider Settings', 'extensive-vc' )
				),
				array(
					'type'       => 'dropdown',
					'param_name' => 'carousel_navigation',
					'heading'    => esc_html__( 'Enable Slider Navigation', 'extensive-vc' ),
					'value'      => array_flip( extensive_vc_get_yes_no_select_array( false, true ) ),
					'group'      => esc_html__( 'Slider Settings', 'extensive-vc' )
				),
				array(
					'type'       => 'dropdown',
					'param_name' => 'carousel_pagination',
					'heading'    => esc_html__( 'Enable Slider Pagination', 'extensive-vc' ),
					'value'      => array_flip( extensive_vc_get_yes_no_select_array( false, true ) ),
					'group'      => esc_html__( 'Slider Settings', 'extensive-vc' )
				)
			);
			
			return $params;
		}
		
		/**
		 * Renders shortcodes HTML
		 *
		 * @param $atts array - shortcode params
		 * @param $content string - shortcode content
		 *
		 * @return html
		 */
		function render( $atts, $content = null ) {
			$args   = array(
				'custom_class'             => '',
				'number'                   => '-1',
				'category'                 => '',
				'carousel_loop'            => 'yes',
				'carousel_autoplay'        => 'yes',
				'carousel_speed'           => '5000',
				'carousel_speed_animation' => '600',
				'carousel_navigation'      => 'yes',
				'carousel_pagination'      => 'yes'
			);
			$params = shortcode_atts( $args, $atts );
			
			$params['query_results']  = new \WP_Query( $this->getQueryParams( $params ) );
			$params['holder_classes'] = $this->getHolderClasses( $params );
			$params['slider_data']    = $this->getSliderData( $params );
			
			$html = extensive_vc_get_module_template_part( 'cpt', 'testimonials', 'templates/testimonials-holder', '', $params );
			
			return $html;
		}
		
		/**
		 * Get shortcode holder classes
		 *
		 * @param $params array - shortcode parameters value
		 *
		 * @return string
		 */
		private function getHolderClasses( $params ) {
			$holderClasses = array();
			
			$holderClasses[] = ! empty( $params['custom_class'] ) ? esc_attr( $params['custom_class'] ) : '';
			
			return implode( ' ', $holderClasses );
		}
		
		/**
		 * Get shortcode query parameters
		 *
		 * @param $params array - shortcode parameters value
		 *
		 * @return array
		 */
		private function getQueryParams( $params ) {
			$args = array(
				'post_status'    => 'publish',
				'post_type'      => 'testimonials',
				'posts_per_page' => $params['number'],
				'orderby'        => 'date',
				'order'          => 'ASC'
			);
			
			if ( $params['category'] != '' ) {
				$args['testimonials-category'] = $params['category'];
			}
			
			return $args;
		}
		
		/**
		 * Get shortcode slider data
		 *
		 * @param $params array - shortcode parameters value
		 *
		 * @return array
		 */
		private function getSliderData( $params ) {
			$data = array();
			
			$data['data-enable-loop']              = ! empty( $params['carousel_loop'] ) ? $params['carousel_loop'] : '';
			$data['data-enable-autoplay']          = ! empty( $params['carousel_autoplay'] ) ? $params['carousel_autoplay'] : '';
			$data['data-carousel-speed']           = ! empty( $params['carousel_speed'] ) ? $params['carousel_speed'] : '5000';
			$data['data-carousel-speed-animation'] = ! empty( $params['carousel_speed_animation'] ) ? $params['carousel_speed_animation'] : '600';
			$data['data-enable-navigation']        = ! empty( $params['carousel_navigation'] ) ? $params['carousel_navigation'] : '';
			$data['data-enable-pagination']        = ! empty( $params['carousel_pagination'] ) ? $params['carousel_pagination'] : '';
			
			return $data;
		}
		
		/**
		 * Filter shortcode categories
		 *
		 * @param $query
		 *
		 * @return array
		 */
		function testimonialsCategoryAutocompleteSuggester( $query ) {
			global $wpdb;
			
			$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT a.slug AS slug, a.name AS testimonials_category_title
				FROM {$wpdb->terms} AS a
				LEFT JOIN ( SELECT term_id, taxonomy  FROM {$wpdb->term_taxonomy} ) AS b ON b.term_id = a.term_id
				WHERE b.taxonomy = 'testimonials-category' AND a.name LIKE '%%%s%%'", stripslashes( $query ) ), ARRAY_A );
			
			$results = array();
			
			if ( is_array( $post_meta_infos ) && ! empty( $post_meta_infos ) ) {
				foreach ( $post_meta_infos as $value ) {
					$data          = array();
					$data['value'] = $value['slug'];
					$data['label'] = ( ( strlen( $value['testimonials_category_title'] ) > 0 ) ? esc_html__( 'Category', 'extensive-vc' ) . ': ' . $value['testimonials_category_title'] : '' );
					$results[]     = $data;
				}
			}
			
			return $results;
		}
		
		/**
		 * Find shortcode category by slug
		 * @since 4.4
		 *
		 * @param $query
		 *
		 * @return bool|array
		 */
		function testimonialsCategoryAutocompleteRender( $query ) {
			$query = trim( $query['value'] ); // get value from requested
			
			if ( ! empty( $query ) ) {
				// get portfolio category
				$testimonials_category = get_term_by( 'slug', $query, 'testimonials-category' );
				
				if ( is_object( $testimonials_category ) ) {
					$testimonials_category_slug  = $testimonials_category->slug;
					$testimonials_category_title = $testimonials_category->name;
					
					$testimonials_category_title_display = '';
					
					if ( ! empty( $testimonials_category_title ) ) {
						$testimonials_category_title_display = esc_html__( 'Category', 'extensive-vc' ) . ': ' . $testimonials_category_title;
					}
					
					$data          = array();
					$data['value'] = $testimonials_category_slug;
					$data['label'] = $testimonials_category_title_display;
					
					return ! empty( $data ) ? $data : false;
				}
				
				return false;
			}
			
			return false;
		}
	}
}

EVCTestimonials::getInstance();