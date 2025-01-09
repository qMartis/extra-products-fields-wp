<?php
namespace YayExtra\Helper;

defined( 'ABSPATH' ) || exit;

use YayExtra\Helper\Database;
use Yay_Currency\Helpers\FixedPriceHelper;
use Yay_Currency\Helpers\YayCurrencyHelper;
use YayExtra\Init\CustomPostType;
/**
 * Util class
 */
class Utils {

	/**
	 * Check nonce function
	 *
	 * @return void
	 */
	public static function check_nonce() {
		$nonce = sanitize_text_field( wp_unslash( isset( $_POST['nonce'] ) ? $_POST['nonce'] : '' ) );
		if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
			wp_send_json_error( array( 'mess' => __( 'Nonce is invalid', 'yayextra' ) ) );
		}
	}

	/**
	 * Custom sanitize array
	 *
	 * @param array $value Pass in array.
	 *
	 * @return array|string
	 */
	public static function sanitize_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'self::sanitize_array', $value );
		} else {
			return sanitize_text_field( wp_unslash( $value ));
		}
	}

	/**
	 * Get template.
	 *
	 * @param string $template_folder Template folder path.
	 *
	 * @param string $slug File name.
	 *
	 * @param array  $params Params.
	 *
	 * @return void
	 */
	public static function get_template_part( $template_folder, $slug = null, $params = array() ) {

		// Utils::get_template_part($templatePart, 'name-tpl', array()); .
		// $template_folder = YAYE_PATH . "includes/Templates"; .
		global $wp_query;
		$_template_file = $template_folder . "/{$slug}.php";
		// if ( is_array( $wp_query->query_vars ) ) {
		// extract( $wp_query->query_vars, EXTR_SKIP );
		// }
		// extract( $params, EXTR_SKIP );
		require $_template_file;
	}

	/**
	 * Get list product categories.
	 *
	 * @return array
	 */
	public static function get_product_categories() {
		$args           = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'parent',
			'hide_empty' => false,
		);
		$all_categories = get_categories( $args );
		$categories     = array();

		if ( empty( $all_categories ) ) {
			return array();
		}
		if ( is_wp_error( $all_categories ) ) {
			return array();
		}

		foreach ( $all_categories as $cat ) {
			array_push(
				$categories,
				array(
					'id'   => $cat->term_id,
					'name' => str_replace( '&amp;', '&', $cat->name ),
					'slug' => $cat->slug,
				)
			);
		}

		return $categories;
	}

	/**
	 * Get list product tag.
	 *
	 * @return array
	 */
	public static function get_product_tags() {
		$terms      = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'hide_empty' => false,
			)
		);
		$term_array = array();

		if ( empty( $terms ) ) {
			return array();
		}

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		foreach ( $terms as $term ) {
			array_push(
				$term_array,
				array(
					'id'   => $term->term_id,
					'name' => $term->name,
				)
			);
		}
		return $term_array;
	}

	/**
	 * Get list product by filter.
	 *
	 * @param array $filter Product filter.
	 *
	 * @return array
	 */
	public static function filter_product_meta( $filter ) {
		if ( 'prod_category' === $filter['filter_type'] ) {
			return self::filter_by_product_categories( $filter['filter_key'] );
		}

		if ( 'prod_tag' === $filter['filter_type'] ) {
			return self::filter_by_product_tag( $filter['filter_key'] );
		}

		if ( 'prod_name' === $filter['filter_type'] ) {
			return self::filter_by_product_name( $filter['filter_key'] );
		}
	}

	/**
	 * Get list product by categories.
	 *
	 * @param string $filter Product filter.
	 *
	 * @return array
	 */
	public static function filter_by_product_categories( $filter ) {
		$categories = array();

		$args           = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'parent',
			'hide_empty' => false,
		);
		$all_categories = get_categories( $args );
		foreach ( $all_categories as $cat ) {
			$cat_name = str_replace( '&amp;', '&', $cat->name );
			if ( false !== strpos( strtolower( $cat_name ), strtolower( $filter ) ) ) {
				$categories[] = array(
					'value' => $cat->term_id,
					'label' => $cat_name,
					'slug'  => $cat->slug,
				);
			}
		}
		return $categories;
	}

	/**
	 * Get list product by tags.
	 *
	 * @param string $filter Product filter.
	 *
	 * @return array
	 */
	public static function filter_by_product_tag( $filter ) {
		$tags         = array();
		$product_tags = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'hide_empty' => false,
			)
		);

		if ( empty( $product_tags ) ) {
			return array();
		}

		if ( is_wp_error( $product_tags ) ) {
			return array();
		}

		foreach ( $product_tags as $product_tag ) {
			if ( false !== strpos( strtolower( $product_tag->name ), strtolower( $filter ) ) ) {
				$tags[] = array(
					'value' => $product_tag->term_id,
					'label' => $product_tag->name,
				);
			}
		}

		return $tags;
	}

	/**
	 * Get list product by product name.
	 *
	 * @param string $filter Product filter.
	 *
	 * @return array
	 */
	public static function filter_by_product_name( $filter ) {
		$results        = array();
		$search_results = array();

		if ( '' === $filter ) {
			return array();
		}

		$product_data   = \WC_Data_Store::load( 'product' );
		$search_results = $product_data->search_products( $filter, '', false, false );
		if ( count( $search_results ) < 1 ) {
			return array();
		}

		foreach ( $search_results as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			if ( false !== strpos( strtolower( $product->get_name() ), strtolower( $filter ) ) ) {
				$results[] = array(
					'value' => $product_id,
					'label' => $product->get_name(),
				);
			}
		}

		return $results;
	}

	/**
	 * Get list product by filters.
	 *
	 * @param array  $filters Product filters.
	 *
	 * @param string $apply Match type.
	 *
	 * @param array  $params Params.
	 *
	 * @return array
	 */
	public static function get_products_match( $filters, $apply, $params ) {
		global $wpdb;
		$database            = new Database();
		$query_result        = $database->get_products( $filters, $apply, $params );
		$list_id             = array();
		$result              = array();
		$prod_list_result    = array();
		$stock_status_option = wc_get_product_stock_status_options();

		$product_list = $query_result['product_list'];
		$current_page = $query_result['current_page'];
		$total_items  = $query_result['total_items'];

		if ( ! empty( $product_list ) ) {
			foreach ( $product_list as $key => $item ) {
				$item_id = ! empty( $item->id ) ? (int) $item->id : (int) $item->ID;

				if ( in_array( $item_id, $list_id, true ) ) {
					continue;
				}
				$product = wc_get_product( $item_id );

				if( $product ) {
					$category = wp_get_post_terms( $product->get_id(), 'product_cat' ) ? wp_get_post_terms( $product->get_id(), 'product_cat' )[0]->name : '';

					$tags               = self::get_product_tags_by_id( $item_id );
					$prod_list_result[] = array(
						'id'             => $product->get_id(),
						'link'           => $product->get_permalink(),
						'image'          => wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' )[0] : wc_placeholder_img_src(),
						'name'           => $product->get_name(),
						'category'       => $category,
						'stock_status' => $product->get_stock_status(),
						'stock'          => $stock_status_option[ $product->get_stock_status() ],
						'stock_quantity' => ! empty( $item->stock_quantity ) && (int) $item->stock_quantity > 0 ? $item->stock_quantity : 0,
						// 'price'          => $product->get_price_html(),
						'tags'           => ! empty( $tags ) ? $tags : '',
					);
				}
				
				$list_id[]          = $item_id;
			}
		}

		$result = array(
			'product_list' => $prod_list_result,
			'current_page' => $current_page,
			'total_items'  => $total_items,
		);

		return $result;
	}

	/**
	 * Get product tags by product id.
	 *
	 * @param array $product_id Product id.
	 *
	 * @return array
	 */
	public static function get_product_tags_by_id( $product_id ) {
		$product_tags = array();
		// get an array of the WP_Term objects for a defined product ID.
		$product_tag_terms = wp_get_post_terms( $product_id, 'product_tag' );

		if ( count( $product_tag_terms ) > 0 ) {
			foreach ( $product_tag_terms as $term ) {
				$term_id   = $term->term_id; // Product tag Id.
				$term_name = $term->name; // Product tag Name.
				$term_slug = $term->slug; // Product tag slug.
				$term_link = get_term_link( $term, 'product_tag' ); // Product tag link.

				$product_tags[] = $term_name;
			}

			return implode( ', ', $product_tags );
		}
	}

	/**
	 * Check is ajax request.
	 *
	 * @return boolean
	 */
	public static function is_ajax_request() {
		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get mime type.
	 *
	 * @return array
	 */
	public static function get_mime_types() {
		$mime_types = wp_get_mime_types();
		$result     = array();
		foreach ( $mime_types as $key => $value ) {
			$value = explode( '|', $key );
			foreach ( $value as $i => $type ) {
				$type     = str_replace( '.', '', trim( $type ) );
				$type     = '.' . $type;
				$result[] = $type;
			}
		}

		return $result;

	}

	/**
	 * Get image mime type.
	 *
	 * @return array
	 */
	public static function get_mime_image_types() {
		$mime_types = wp_get_ext_types();
		$result     = array();
		foreach ( $mime_types['image'] as $type ) {
				$type     = str_replace( '.', '', trim( $type ) );
				$type     = '.' . $type;
				$result[] = $type;

		}
		return $result;
	}

	/**
	 * Convert price by YayCurrency.
	 *
	 * @param float $price Price value.
	 *
	 * @return array
	 */
	public static function get_price_from_yaycurrency( $price ) {
		if ( function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if (class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
				if ( method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'calculate_price_by_currency' ) && 
					method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'disable_fallback_option_in_checkout_page' )
				) {
					$converted_currency = YayCurrencyHelper::converted_currency();
					$apply_currency = YayCurrencyHelper::get_apply_currency( $converted_currency );
					if( !empty( $apply_currency ) ){
						if( YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency ) ) {
							return $price;
						}
						return YayCurrencyHelper::calculate_price_by_currency( $price, false, $apply_currency );
					}
				}
			}
		}
		return $price;
	}

	/**
	 * Convert price by YayCurrency cookie.
	 *
	 * @param float $price Price value.
	 *
	 * @return array
	 */
	public static function get_price_from_yaycurrency_cookie( $price ) {
		if ( function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if (class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
				if ( method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'calculate_price_by_currency_cookie' ) ) {
					return YayCurrencyHelper::calculate_price_by_currency_cookie( $price, false );
				}
			}
		}
		return $price;
	}

	/**
	 * Reverse price by YayCurrency.
	 *
	 * @param float $price Price value.
	 *
	 * @return array
	 */
	public static function get_reverse_price_from_yaycurrency( $price ) {
		if ( function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if (class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
				if ( method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'reverse_calculate_price_by_currency' ) ) {
					return YayCurrencyHelper::reverse_calculate_price_by_currency( $price );
				}
			}
		}
		return $price;
	}

	/**
	 * Format price by YayCurrency.
	 *
	 * @param float $price Price value.
	 *
	 * @return array
	 */
	public static function get_formatted_price_from_yaycurrency( $price ) {
		if ( function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if (class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
				if ( method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'format_price' ) ) {
					return YayCurrencyHelper::format_price( $price );
				}
			}
		}
		return wc_price( $price );
	}

		/**
	 * Get price fixed from yaycurrency
	 *
	 * @param object $product object.
	 * @param float $price Price value.
	 *
	 * @return array
	 */
	public static function get_price_fixed_from_yaycurrency( $product_id, $product_price, $force_original = false ) {
		if ( function_exists( 'Yay_Currency\\plugin_init' ) &&
			 class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) &&
			 method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'detect_current_currency' ) 
		) {
			$apply_currency = YayCurrencyHelper::detect_current_currency();
			if ( ! empty( $apply_currency ) ) {
				$product       = wc_get_product( $product_id );
				$product_price = self::get_price_from_yaycurrency( $product_price );
				if ( method_exists( 'Yay_Currency\Helpers\FixedPriceHelper', 'get_price_fixed_by_apply_currency' ) ) {
					$product_price = FixedPriceHelper::get_price_fixed_by_apply_currency( $product, $product_price, $apply_currency );
				}
				

				if ( $force_original ) {
					if ( method_exists( 'Yay_Currency\Helpers\YayCurrencyHelper', 'disable_fallback_option_in_checkout_page' ) && 	
						 YayCurrencyHelper::disable_fallback_option_in_checkout_page( $apply_currency ) ) 
					{
						return wc_get_product( $product_id )->get_price( 'original' );
					}
					return self::get_reverse_price_from_yaycurrency( $product_price );
				}

				return $product_price;
			}
		}

		return $product_price;
	}
	
	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return get_option( 'yaye_settings' );
	}

	/**
	 * Update settings.
	 *
	 * @param array $data Settings.
	 *
	 * @return void
	 */
	public static function update_settings( $data ) {
		if ( ! empty( $data ) ) {
			update_option( 'yaye_settings', $data );
		}
	}

	/**
	 * Convert classes string.
	 *
	 * @param string $string Classes string.
	 * @param string $separator_input Separator input.
	 * @param string $separator_output Separator output.
	 *
	 * @return string
	 */
	public static function convert_string( $string, $separator_input, $separator_output ) {
		$class_names = '';
		if ( ! empty( $string ) ) {
			$class_names_pieces      = explode( $separator_input, $string );
			$class_names_pieces_trim = array();
			foreach ( $class_names_pieces as $class_name ) {
				array_push( $class_names_pieces_trim, trim( $class_name ) );
			}
			$class_names = implode( $separator_output, $class_names_pieces_trim );
		}
		return $class_names;
	}

	/**
	 * Check valid email format.
	 *
	 * @param string $email Email string.
	 *
	 * @return boolean
	 */
	public static function is_valid_email( $email ) {
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return true;
		};
		return false;
	}

	/**
	 * Check valid url format.
	 *
	 * @param string $url Url string.
	 *
	 * @return boolean
	 */
	public static function is_valid_url( $url ) {
		if ( preg_match( '/^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i', $url ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check valid custom format.
	 *
	 * @param string $regular_expression Custom reg.
	 * @param string $string String for checking.
	 *
	 * @return boolean
	 */
	public static function is_valid_custom_format( $regular_expression, $string ) {
		if ( preg_match( base64_decode( $regular_expression ), $string ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Default option set
	 */
	public static function get_default_option_set() {
		return array(
			'actions'     => array(),
			'description' => 'Sample description',
			'id'          => null,
			'name'        => 'Sample Option Set',
			'options'     => array(),
			'products'    => array(
				'product_filter_type'          => 1,
				'product_filter_one_by_one'    => array(),
				'product_filter_by_conditions' => array(
					'match_type' => array(
						'label' => 'Any',
						'value' => 'any',
					),
					'conditions' => array(),
				),
			),
		);
	}

	/**
	 * Check valid option set data.
	 *
	 * @param array $data Option set data.
	 */
	public static function check_valid_option_set_data( $data ) {
		$diff_key = array_diff_key( self::get_default_option_set(), $data );
		if ( count( $diff_key ) > 0 ) {
			$data = array_merge( $diff_key, $data );
		}
		return $data;
	}

	/**
	 * Caculate total option cost on every cart item
	 *
	 * @param array $cart_option_sets Option sets data.
	 * @param float $product_price_org Original product price.
	 */
	public static function cal_total_option_cost_on_cart_item_static( $cart_option_sets, $product_price_org = '' ) {
		$total_option_cost = 0;

		if ( ! empty( $cart_option_sets ) ) {
			foreach ( $cart_option_sets as $option_set_id => $custom_option ) {
				foreach ( $custom_option as $option_id => $option ) {
					if ( ! empty( $option['option_value'] ) && is_array( $option['option_value'] ) ) {
						$option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );
						foreach ( $option['option_value'] as $opt_val ) {
							if ( ! empty( $opt_val['option_cost'] ) ) {
								$addition_cost = self::get_addition_cost_by_option_static( $option_meta, $opt_val['option_val'], $product_price_org );
								if(! empty( $addition_cost ) && ! empty( $addition_cost[ $opt_val['option_val'] ] )) {
									$total_option_cost += $addition_cost[$opt_val['option_val']];
								}
							}
						}
					}
				}
			}
		}

		return $total_option_cost;
	}


	/**
	 * Get addition cost by option.
	 *
	 * @param array $option_meta Option meta.
	 * @param array $option_cart_val Option cart val data.
	 * @param float $product_price Product price.
	 */
	public static function get_addition_cost_by_option_static( $option_meta = array(), $option_cart_val = null, $product_price = 0 ) {
		$result = array();
		if ( ! empty( $option_meta ) && ! empty( $option_cart_val ) ) {
			if ( ! empty( $option_meta['optionValues'] ) ) {
				foreach ( $option_meta['optionValues'] as $option_value ) {
					$additional_cost = $option_value['additionalCost'];

					if ( $additional_cost['isEnabled'] && ! empty( $additional_cost['value'] ) ) {
						$opt_value = $option_value['value'];

						$cost = 0;
						if ( 'fixed' === $additional_cost['costType']['value'] ) { // fixed.
							$cost = floatval( $additional_cost['value'] );
						} else { // percentage.
							if ( isset( $product_price ) && is_numeric( $product_price ) ) {
								$cost = floatval( $additional_cost['value'] ) * floatval( $product_price ) / 100;
							}
						}

						if ( is_array( $option_cart_val ) ) {
							foreach ( $option_cart_val as $opt_cart ) {
								if ( trim($opt_cart) === trim($opt_value) ) {
									$result[ $opt_cart ] = $cost;
								}
							}
						} else {
							if ( trim($option_cart_val) === trim($opt_value) ) {
								$result[ $option_cart_val ] = $cost;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * UUIDs generated below validates using OSSP UUID Tool, 
	 * and output for named-based UUIDs are exactly the same
	 */
	public static function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	/** Get tax class */
	public static function get_tax_class( $prod_class, $context = FALSE ) {
		if ( is_callable( array( $prod_class, 'get_tax_class' ) ) ) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
				return $prod_class->get_tax_class();
			} else {
				if ( FALSE !==  $context ) {
					return $prod_class->get_tax_class( $context );
				} else {
					return $prod_class->get_tax_class();
				}
			}
		}

		return $prod_class->tax_class;
	}

	static function transform_url_to_html_from_db($string) {
		// Define the regex pattern to match the {{ url('url', 'text') }} structure
		$pattern = '/{{\s*url\((\'[^\']+\'),\s*\'([^\']+)\'\)\s*}}/';
	
		// Perform the replacement
		$string = preg_replace_callback($pattern, function($matches) {
			// $matches[1] contains the URL (urlnuoroda)
			// $matches[2] contains the anchor text (text)
			$url = trim($matches[1], "'");
			$text = $matches[2];
	
			// Return the transformed HTML
			return '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
		}, $string);
	
		// Sanitize the final content using wp_kses_post() to allow safe HTML
		return wp_kses_post($string);
	}
}
