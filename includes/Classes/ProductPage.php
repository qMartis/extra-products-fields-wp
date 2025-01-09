<?php

namespace YayExtra\Classes;

use YayExtra\Init\CustomPostType;
use YayExtra\Helper\Database;
use YayExtra\Helper\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Handle extra option processing.
 *
 * @class ProductPage
 */
class ProductPage {


	/**
	 * Store ProductPage object.
	 *
	 * @var object $instance ProductPage object.
	 */
	protected static $instance = null;

	/**
	 * Check whether process once
	 *
	 * @var array
	 */
	protected $is_processed = false;

	/**
	 * Function ensure only one instance created.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		// Check current role for show/hide.
		$settings         = get_option( 'yaye_settings' );
		$general_settings = $settings['general'];
		$show_for_roles   = $general_settings['show_for_roles'];
		$hide_for_roles   = $general_settings['hide_for_roles'];
		$user             = wp_get_current_user();

		if ( ! empty( $show_for_roles ) ) {
			// For YayExtra pro version.
		}

		if ( ! empty( $hide_for_roles ) ) {
			// For YayExtra pro version.
		}

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_options_field' ), 10, 0 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_option_fields' ), PHP_INT_MIN, 4 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_option_data' ), 10, 4 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 102 );
		add_filter( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_discount_by_action' ), 10, 1 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_option_on_cart_and_checkout' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'display_option_on_orders_and_emails' ), 10, 4 );

		/** Change quantity value in Edit mode */
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'before_add_to_cart_form' ), 1 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'after_add_to_cart_form' ), 9999 );

		/** Add Edit Option Field link in minicart */
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'add_link_edit_option_field_in_minicart' ), 10, 3 );

		add_filter( 'woocommerce_cart_item_class', array( $this, 'custom_cart_item_class' ), 10, 3 );
		add_filter( 'woocommerce_mini_cart_item_class', array( $this, 'custom_mini_cart_item_class' ), 10, 3 );

		add_action( 'woocommerce_cart_item_removed', array( $this, 'handle_after_cart_item_removed' ), 10, 2 );
		add_filter( 'woocommerce_after_cart_item_quantity_update', array( $this, 'handle_after_cart_item_quantity_update' ), 10, 3 );

		// add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'handle_cart_item_thumbnail' ), 60, 3 );
		// add_filter( 'woocommerce_order_item_thumbnail', array( $this, 'handle_order_item_thumbnail' ), 60, 2 );

		/** Global style - For YayExtra pro version. */
		add_action( 'wp_print_styles', array( $this, 'print_global_style' ) );

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_update_after_created_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_exception', array( $this, 'checkout_order_exception_after_created_order' ), 10, 1 );

		add_action( 'woocommerce_check_cart_items', array( $this, 'handle_check_cart_items' ), 10, 0 );
	}

	/**
	 * Add option field before add to cart button.
	 */
	public function add_options_field() {
		global $product;
		$current_prod_id         = $product->get_id();
		$product_price           = Utils::get_price_fixed_from_yaycurrency( $current_prod_id, $product->get_price('original'));
		// For YayExtra pro version.
		// $product_regular_price   = Utils::get_price_fixed_from_yaycurrency( $current_prod_id, $product->get_regular_price('original')); 
		$settings                = Utils::get_settings();

		$option_set_of_product = $this->get_option_set_of_product( $current_prod_id, $settings );

		if ( ! empty( $option_set_of_product ) ) {
			foreach ( $option_set_of_product as $opt_set_data ) {
				if ( ! empty( $opt_set_data['options'] ) ) {
					$opt_field_list = $opt_set_data['options'];

					foreach ( $opt_field_list as $opt_field ) {
						$template_folder = YAYE_PATH . 'includes/Templates';
						// Output field.
						Utils::get_template_part(
							$template_folder,
							$opt_field['type']['value'] . '_field',
							array(
								'opt_set_id'          => $opt_set_data['id'],
								'data'                => $opt_field,
								'product_price'       => $product_price,
								'is_edit_option_mode' => $this->is_edit_option_mode(),
								'settings'            => $settings,
							)
						);
					}
				}
			};

			wp_nonce_field( 'yayextra-opt-field-data-check-nonce', 'yayextra-opt-field-data-nonce' );

			if ( isset( $settings['general'] ) && true === $settings['general']['show_extra_subtotal'] ) {
				echo '<div class="yayextra-extra-subtotal-price">';
				echo '<span class="total-price-title">' . esc_html__( 'Extra subtotal:', 'yayextra' ) . '</span>';
				echo '<span class="total-price" data-token-replace="0" data-total-price="0">' . wp_kses_post( Utils::get_formatted_price_from_yaycurrency( 0 ) ) . '</span>';
				echo '</div>';
			}

			if ( isset( $settings['general'] ) && true === $settings['general']['show_total_price'] ) {
				echo '<div class="yayextra-total-price">';
				echo '<span class="total-price-title">' . esc_html__( 'Total price:', 'yayextra' ) . '</span>';
				echo '<span class="total-price" data-token-replace="' . esc_attr( $product_price ) . '" data-total-price="' . esc_attr( $product_price ) . '">' . wp_kses_post( Utils::get_formatted_price_from_yaycurrency( $product_price ) ) . '</span>';
				echo '</div>';
			}
		}

		if ( $this->is_edit_option_mode() && isset( $_GET['yaye_cart_item_key'] ) ) {
			$cart_item_key = sanitize_text_field( $_GET['yaye_cart_item_key'] );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_text' ), 999 );
			echo '<input type="hidden" name="yaye_cart_edit_key" value="' . esc_attr( $cart_item_key ) . '" />';
		}

		echo '<input type="hidden" name="yaye_visibility_option_list" value="" />';

		if ( isset( $settings['general'] ) && !empty($settings['general']['update_product_price']) && true === $settings['general']['update_product_price'] ) { 
			// For YayExtra pro version.
		}
	}

	/**
	 * Add Custom quantity input hook.
	 */
	public function before_add_to_cart_form() {
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'custom_quantity_input_args' ), 9999, 2 );
	}

	/**
	 * Remove Custom quantity input hook.
	 */
	public function after_add_to_cart_form() {
		remove_filter( 'woocommerce_quantity_input_args', array( $this, 'custom_quantity_input_args' ), 9999 );
	}

	/**
	 * Custom quantity input
	 *
	 * @param array  $args The wp parse args.
	 * @param object $product The product.
	 */
	public function custom_quantity_input_args( $args = '', $product = '' ) {
		if ( $this->is_edit_option_mode() && isset( $_GET['yaye_cart_item_key'] ) ) {
			$cart_item = WC()->cart->get_cart_item( sanitize_text_field( $_GET['yaye_cart_item_key'] ) );

			if ( isset( $cart_item['quantity'] ) ) {
				$args['input_value'] = $cart_item['quantity'];
			}
		}

		return $args;
	}

	/**
	 * Field validation
	 *
	 * @param boolean $passed Pass validate or not.
	 * @param int     $product_id product id.
	 * @param int     $quantity product quantity.
	 */
	public function validate_option_fields( $passed, $product_id, $quantity, $variation_id = 0 ) {
		// If all options are hiden then pass
		$visibility_option_id_list_post =  map_deep( wp_unslash( $_POST['yaye_visibility_option_list'] ), 'sanitize_text_field' );
		if( empty ( $visibility_option_id_list_post ) ) {
			return true;
		}

		$error_notice               = array();
		$cart_contents              = WC()->cart->cart_contents;
		$options_with_stock_in_cart = $this->get_options_with_stock_in_cart( $cart_contents );

		// Check require of 'checkbox', 'radio', 'button', 'dropdown', 'swatches', 'file_upload' option - start.
		$all_option = $this->get_all_option_of_product( $product_id );

		if ( ! empty( $all_option ) ) {
			foreach ( $all_option as $opt_set_id => $opt ) {
				foreach ( $opt as $opt_id => $opt_data ) {
					if ( ! empty( $opt_data['isRequired'] ) && in_array( $opt_data['type']['value'], array( 'checkbox', 'radio', 'button', 'button_multi', 'dropdown', 'swatches', 'swatches_multi', 'file_upload' ), true ) ) {
						if ( 'file_upload' === $opt_data['type']['value'] ) { // 'file_upload'.
							// Pro version
						} else { // 'checkbox', 'radio', 'button', 'button_multi', 'dropdown', 'swatches', 'swatches_multi'.
							if ( isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) ) {
								if ( empty( $_POST['option_field_data'] ) ) {
									$passed         = false;
									$error_notice[] = $opt_data['name'] . __( ' is a required field.', 'yayextra' );
									break 2;
								} else {
									$opt_field_data = Utils::sanitize_array( $_POST['option_field_data'] );
									$visibility_option_id_list = explode( ',', $visibility_option_id_list_post );

									if ( ! empty( $visibility_option_id_list ) && 
										in_array( $opt_id, $visibility_option_id_list, true ) 
										&& empty( $opt_field_data[ $opt_set_id ][ $opt_id ] ) 
									) {
										$passed         = false;
										$error_notice[] = $opt_data['name'] . __( ' is a required field.', 'yayextra' );
										break 2;
									}
								}
							}
						}
					}
				}
			}
		}

		if ( ! $passed ) {
			if ( ! empty( $error_notice ) ) {
				wc_add_notice( implode( ' < br > ', $error_notice ), 'error' );
			}
			return $passed;
		}
		// Check require of 'checkbox', 'radio', 'button', 'dropdown', 'swatches', 'file_upload' option - end.

		if ( ! empty( $_POST['option_field_data'] ) ) {
			$option_field_data = Utils::sanitize_array( $_POST['option_field_data'] );
			foreach ( $option_field_data as $option_set_id => $option ) {
				if ( ! empty( $option ) ) {
					foreach ( $option as $option_id => $option_val ) {
						$option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );
						if ( ! empty( $option_meta ) ) {
							$option_required = $option_meta['isRequired'];
							$option_name     = $option_meta['name'];

							if ( $option_required ) {
								if ( empty( $option_val ) ) {
									$passed         = false;
									$error_notice[] = $option_name . __( ' is a required field.', 'yayextra' );
									break 2;
								} else {
									if ( 'text' === $option_meta['type']['value'] ) {
										if ( ! empty( $option_meta['textFormat'] ) ) {
											if ( 'email' === $option_meta['textFormat']['value'] ) {
												if ( ! Utils::is_valid_email( $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid email format', 'yayextra' );
													break 2;
												}
											} elseif ( 'url' === $option_meta['textFormat']['value'] ) {
												if ( ! Utils::is_valid_url( $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid url format', 'yayextra' );
													break 2;
												}
											} elseif ( 'custom_format' === $option_meta['textFormat']['value'] && ! empty( $option_meta['regularExpression'] ) ) {
												if ( ! Utils::is_valid_custom_format( $option_meta['regularExpression'], $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid string format', 'yayextra' );
													break 2;
												}
											}
										}
									}
								}
							} else {
								if ( ! empty( $option_val ) ) {
									if ( 'text' === $option_meta['type']['value'] ) {
										if ( ! empty( $option_meta['textFormat'] ) ) {
											if ( 'email' === $option_meta['textFormat']['value'] ) {
												if ( ! Utils::is_valid_email( $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid email format', 'yayextra' );
													break 2;
												}
											} elseif ( 'url' === $option_meta['textFormat']['value'] ) {
												if ( ! Utils::is_valid_url( $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid url format', 'yayextra' );
													break 2;
												}
											} elseif ( 'custom_format' === $option_meta['textFormat']['value'] && ! empty( $option_meta['regularExpression'] ) ) {
												if ( ! Utils::is_valid_custom_format( $option_meta['regularExpression'], $option_val ) ) {
													$passed         = false;
													$error_notice[] = $option_name . __( ' is invalid string format', 'yayextra' );
													break 2;
												}
											}
										}
									}
								}
							}

							// Validate stock of option value - start.
							if ( ! empty( $option_val ) ) {
								if ( is_array( $option_val ) ) {
									foreach ( $option_val as $val ) {
										if ( ! empty( $option_meta['optionValues'] ) ) {
											foreach ( $option_meta['optionValues'] as $option_meta_el ) {
												if ( ! empty( $option_meta_el ) && ! empty( $option_meta_el['manageStock'] ) && ! empty( $option_meta_el['manageStock']['isEnabled'] ) && trim($val) === trim($option_meta_el['value']) ) {
													$stock_db = (int) $option_meta_el['manageStock']['quantity'];
													if ( ! empty( $options_with_stock_in_cart ) ) {
														foreach ( $options_with_stock_in_cart as $opt_stock_cart ) {
															if ( $opt_stock_cart['option_set_id'] === (int) $option_set_id &&
															$opt_stock_cart['option_id'] === $option_id &&
															trim($opt_stock_cart['option_val']) === trim($val) &&
															( $quantity + $opt_stock_cart['quantity'] ) > $stock_db ) {
																$passed       = false;
																$opt_val_name = $option_meta['name'] . ': ' . $option_meta_el['value'];
																$message      = sprintf(
																	'<a href="%s" class="button wc-forward">%s</a> %s',
																	wc_get_cart_url(),
																	__( 'View cart', 'woocommerce' ),
																	sprintf(
																	/* translators: &quot;%1$s&quot;: option value. */
																		__( 'You cannot add that amount of &quot;%1$s&quot; to the cart &mdash; we have %2$d in stock and you already have %3$d in your cart.', 'yayextra' ),
																		$opt_val_name,
																		$stock_db,
																		$opt_stock_cart['quantity']
																	)
																);

																$error_notice[] = $message;
																break 5;
															}
														}
													} else {
														if ( $quantity > $stock_db ) {
															$passed       = false;
															$opt_val_name = $option_meta['name'] . ': ' . $option_meta_el['value'];

															$message = sprintf(
															/* translators: &quot;%1$s&quot;: option value. */
																__( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock ( %2$d remaining ) . ', 'yayextra' ),
																$opt_val_name,
																$stock_db
															);

															$error_notice[] = $message;
															break 4;
														}
													}
												}
											}
										}
									}
								} else {
									if ( ! empty( $option_meta['optionValues'] ) ) {
										foreach ( $option_meta['optionValues'] as $option_meta_el ) {
											if ( ! empty( $option_meta_el ) && ! empty( $option_meta_el['manageStock'] ) && ! empty( $option_meta_el['manageStock']['isEnabled'] ) && trim($option_val) === trim($option_meta_el['value']) ) {
												$stock_db = (int) $option_meta_el['manageStock']['quantity'];
												if ( ! empty( $options_with_stock_in_cart ) ) {
													foreach ( $options_with_stock_in_cart as $opt_stock_cart ) {
														if ( $opt_stock_cart['option_set_id'] === (int) $option_set_id &&
														$opt_stock_cart['option_id'] === $option_id &&
														trim($opt_stock_cart['option_val']) === trim($option_val) &&
														( $quantity + $opt_stock_cart['quantity'] ) > $stock_db ) {
															$passed       = false;
															$opt_val_name = $option_meta['name'] . ': ' . $option_meta_el['value'];
																$message  = sprintf(
																	'<a href="%s" class="button wc-forward">%s</a> %s',
																	wc_get_cart_url(),
																	__( 'View cart', 'woocommerce' ),
																	sprintf(
																	/* translators: &quot;%1$s&quot;: option value. */
																		__( 'You cannot add that amount of &quot;%1$s&quot; to the cart &mdash; we have %2$d in stock and you already have %3$d in your cart.', 'yayextra' ),
																		$opt_val_name,
																		$stock_db,
																		$opt_stock_cart['quantity']
																	)
																);
															$error_notice[] = $message;

															break 4;
														}
													}
												} else {
													if ( $quantity > $stock_db ) {
														$passed       = false;
														$opt_val_name = $option_meta['name'] . ': ' . $option_meta_el['value'];
														$message      = sprintf(
														/* translators: &quot;%1$s&quot;: option value. */
															__( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock ( %2$d remaining ) . ', 'yayextra' ),
															$opt_val_name,
															$stock_db
														);

														$error_notice[] = $message;
														break 3;
													}
												}
											}
										}
									}
								}
							}
							// Validate stock of option value - end.
						}
					}
				}
			}

			if ( ! empty( $error_notice ) ) {
				wc_add_notice( implode( ' < br > ', $error_notice ), 'error' );
			}

			if ( $passed ) {
				if ( ! empty( $_REQUEST['yaye_cart_edit_key'] ) ) {
					// Update cart option line.
					$cart_edit_key = sanitize_text_field( $_REQUEST['yaye_cart_edit_key'] );
					$this->update_cart_option_item( $cart_edit_key, $product_id, $cart_contents, $variation_id );
				} else {
					// Add extra product.
					$this->add_extra_product( $product_id, $quantity );
				}
			}
		}

		return $passed;
	}

	/**
	 * Get option field data
	 *
	 * @param int $product_id product id.
	 */
	public function get_option_field_data( $product_id, $variation_id = 0 ) {
		$result = array();
		// Handle basic option fields.
		if ( isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) ) {
			if ( ! empty( $_POST['option_field_data'] ) ) {
				$product = wc_get_product( $product_id );

				if ( !empty( $variation_id ) ) {
					$product_variation = wc_get_product( $variation_id );
					$product_price = Utils::get_price_fixed_from_yaycurrency( $variation_id, $product_variation->get_price( 'original' ), true); 
				} else {
					$product_price = Utils::get_price_fixed_from_yaycurrency( $product_id, $product->get_price( 'original' ), true); 
				}

				$option_field_data = Utils::sanitize_array( $_POST['option_field_data'] );

				foreach ( $option_field_data as $option_set_id => $option ) {
					if ( ! empty( $option ) ) {
						foreach ( $option as $option_id => $option_val ) {
							  $option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );
							if ( ! empty( $option_meta ) ) {
								$option_name = $option_meta['name'];

								if ( ! empty( $option_val ) ) {
									$option_has_addtion_cost_list = array( 'checkbox', 'radio', 'button', 'button_multi', 'dropdown', 'swatches', 'swatches_multi' );
									if ( in_array( $option_meta['type']['value'], $option_has_addtion_cost_list, true ) ) {
										$addition_cost = Utils::get_addition_cost_by_option_static( $option_meta, $option_val, $product_price );

										$option_value_arr = array();
										if ( is_array( $option_val ) ) { // checkbox, swatches.
											foreach ( $option_val as $optval ) {
												$option_value_arr[] = array(
													'option_val' => $optval,
													'option_cost' => ! empty( $addition_cost ) && ! empty( $addition_cost[ $optval ] ) ? $addition_cost[ $optval ] : null,
												);
											}
										} else { // radio, dropdown, button.
											$option_value_arr[] = array(
												'option_val'  => $option_val,
												'option_cost' => ! empty( $addition_cost ) && ! empty( $addition_cost[ $option_val ] ) ? $addition_cost[ $option_val ] : null,
											);
										}

										$option_value_rst = $option_value_arr;
									} else {
										$option_value_rst = $option_val; // val is string.
									}

									$result[ $option_set_id ][ $option_id ] = array(
										'option_name'  => $option_name,
										'option_value' => $option_value_rst,
									);
								}
							}
						}
					}
				}
			}
		}

		// Handle file upload option fields.
		// if ( ! empty( $_FILES['option_field_data'] ) ) {
			// Pro version
		// }

		return $result;
	}

	/**
	 * Save as custom cart item data.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id Product id.
	 */
	public function add_cart_item_option_data( $cart_item_data, $product_id, $variation_id = 0, $quantity = 0 ) {
		if ( empty( $cart_item_data['yaye_add_extra_product'] ) ) {
			if ( isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) ) {
				if ( ! empty( $_POST['option_field_data'] ) ) {
					
					$product = wc_get_product( $product_id );

					if('variable' === $product->get_type() && !empty($variation_id)) {
						$product_variation = wc_get_product( $variation_id );
						$product_price     = Utils::get_price_fixed_from_yaycurrency( $variation_id, $product_variation->get_price( 'original' ), true); 
					} else {
						$product_price = Utils::get_price_fixed_from_yaycurrency( $product_id, $product->get_price( 'original' ), true); 
					}

					$option_field_data = Utils::sanitize_array( $_POST['option_field_data'] );

					foreach ( $option_field_data as $option_set_id => $option ) {
						if ( ! empty( $option ) ) {
							foreach ( $option as $option_id => $option_val ) {
								$option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );
								if ( ! empty( $option_meta ) ) {
									$option_name = $option_meta['name'];

									if ( ! empty( $option_val ) ) {
										$option_has_addtion_cost_list = array( 'checkbox', 'radio', 'button', 'button_multi', 'dropdown', 'swatches', 'swatches_multi' );
										if ( in_array( $option_meta['type']['value'], $option_has_addtion_cost_list, true ) ) {
											$addition_cost = Utils::get_addition_cost_by_option_static( $option_meta, $option_val, $product_price );

											$option_value_arr = array();
											if ( is_array( $option_val ) ) { // checkbox, swatches.
												foreach ( $option_val as $optval ) {
													$option_value_arr[] = array(
														'option_val'  => $optval,
														'option_cost' => ! empty( $addition_cost ) && ! empty( $addition_cost[ $optval ] ) ? $addition_cost[ $optval ] : null,
													);
												}
											} else { // radio, dropdown, button.
												$option_value_arr[] = array(
													'option_val'  => $option_val,
													'option_cost' => ! empty( $addition_cost ) && ! empty( $addition_cost[ $option_val ] ) ? $addition_cost[ $option_val ] : null,
												);
											}

											$option_value_rst = $option_value_arr;
										} else {
											$option_value_rst = $option_val; // val is string.
										}

										$cart_item_data['yaye_custom_option'][ $option_set_id ][ $option_id ] = array(
											'option_name'  => $option_name,
											'option_value' => $option_value_rst,
										);
									}
								}
							}
						}
					}

					// Calculate total option cost for Session
					if('variable' === $product->get_type() && !empty($variation_id)) {
						$product_variation = wc_get_product( $variation_id );
						$product_price_original = Utils::get_price_fixed_from_yaycurrency( $variation_id, $product_variation->get_price( 'original' ), true );
						$total_option_cost = Utils::cal_total_option_cost_on_cart_item_static( $cart_item_data['yaye_custom_option'], $product_price_original );

						$cart_item_data['yaye_product_price_original'] = $product_variation->get_price( 'original' );
					} else {
						$product_price_original = Utils::get_price_fixed_from_yaycurrency( $product_id, $product->get_price( 'original' ), true );
						$total_option_cost = Utils::cal_total_option_cost_on_cart_item_static( $cart_item_data['yaye_custom_option'], $product_price_original );

						$cart_item_data['yaye_product_price_original'] = $product->get_price( 'original' );
					}

					$cart_item_data['yaye_total_option_cost'] = $total_option_cost;

				}
			}
			// Handle file upload option fields.
			if ( ! empty( $_FILES['option_field_data'] ) ) {
				// Pro version
			}
		}

		return $cart_item_data;
	}

	/**
	 * Set addition cost into item price.
	 *
	 * @param object $cart_object Cart object.
	 */
	public function before_calculate_totals( $cart_object ) {
		if ( $this->is_processed ) {
			return;
		}

		$this->is_processed = true;

		foreach ( $cart_object->cart_contents as $cart_value ) {
			if( !empty($cart_value['data']) ) {
				$cost_total         = Utils::get_price_fixed_from_yaycurrency( $cart_value['product_id'], floatval( $cart_value['data']->get_price( 'original' ) ), true);
				$cost_regular_total = Utils::get_price_fixed_from_yaycurrency( $cart_value['product_id'], floatval( $cart_value['data']->get_regular_price( 'original' ) ), true); 
				
				if ( ! empty( $cart_value['yaye_custom_option'] ) ) {
					foreach ( $cart_value['yaye_custom_option'] as $option_set_id => $custom_option ) {
						foreach ( $custom_option as $option_id => $option ) {
							if ( ! empty( $option['option_value'] ) && is_array( $option['option_value'] ) ) {
								foreach ( $option['option_value'] as $opt_val ) {
									if ( ! empty( $opt_val['option_cost'] ) ) {
										$cost_total         += $opt_val['option_cost'];
										$cost_regular_total += $opt_val['option_cost'];
									}
								}
							}
						}
					}
				}
	
				add_filter(
					'yaye_check_adjust_price',
					function( $check_value ) {
						return true;
					},
					10
				);
	
				$cost_total         = apply_filters('yaye_cart_line_total', Utils::get_price_from_yaycurrency( $cost_total ), $cart_value);
				$cost_regular_total = apply_filters('yaye_cart_line_regular_total', Utils::get_price_from_yaycurrency( $cost_regular_total ), $cart_value);

				$cart_value['data']->set_price( $cost_total );
				$cart_value['data']->set_regular_price( $cost_regular_total );
			}
		}
	}

	/**
	 * Display in cart and checkout.
	 *
	 * @param array $cart_data Cart data.
	 * @param array $cart_item Cart item.
	 */
	public function display_option_on_cart_and_checkout( $cart_data, $cart_item ) {
		$_product          = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item['key'] );
		$product_permalink = null;
		if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item['key'] ) ) {
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item['key'] );
		}

		if ( ! empty( $cart_item['yaye_custom_option'] ) ) {
			$settings         = get_option( 'yaye_settings' );
			$general_settings = $settings['general'];

			$options_data = $cart_item['yaye_custom_option'];
			foreach ( $options_data as $option_set_id => $custom_option ) {
				foreach ( $custom_option as $option_id => $option ) {
					$option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );
			
					if ( ! empty( $option['option_value'] ) ) {
						if ( is_array( $option['option_value'] ) ) {
							$option_value = array();
							foreach ( $option['option_value'] as $val ) {
								if ( ! empty( $general_settings['show_additional_price'] ) && ! empty( $val['option_cost'] ) ) {

									$option_cost_org = 0; 
									$cost_type = null;
									if ( ! empty( $option_meta ) && ! empty( $option_meta['optionValues'] ) ) {
										foreach ( $option_meta['optionValues'] as $option_meta_value ) {
											if( trim($val['option_val']) == trim($option_meta_value['value']) ) {
												$additional_cost = $option_meta_value['additionalCost'];
												if( !empty( $additional_cost ) ) {
													$cost_type = $additional_cost['costType']['value'];
													$option_cost_org = floatval( $additional_cost['value'] );
													break;
												}
											}	
										}
									}

									$option_cost = apply_filters( 'yaye_option_cost_display_cart_checkout', Utils::get_price_from_yaycurrency( floatval( $val['option_cost'] ) ), $option_cost_org, $cost_type, $cart_item['yaye_product_price_original'], $_product->get_id());
									$val_string  = $val['option_val'] . ' ( + ' . wc_price( $option_cost ) . ' )';
								} else {
									$val_string = $val['option_val'];
								}
								array_push( $option_value, $val_string );
							}

							$cart_data[] = array(
								'name'  => $option['option_name'],
								'value' => implode( ', ', $option_value ),
							);
						} else {
							if ( isset( $option['option_type'] ) && 'file_upload' === $option['option_type'] ) {
								$cart_data[] = array(
									'name'  => $option['option_name'],
									'value' => '<a href="' . $option['option_value'] . '">' . $option['file_name'] . '</a>',
								);
							} else {
								$cart_data[] = array(
									'name'  => $option['option_name'],
									'value' => $option['option_value'],
								);
							}
						}
					}
				}
			}
		}

		// Add edit option field link with product has applied opiton field.
		if ( is_cart() && ! empty( $product_permalink ) ) {
			$current_prod_id = $_product->get_parent_id();
			if ( empty( $current_prod_id ) ) {
				$current_prod_id = $_product->get_id();
			}
			$has_edit_option_link = $this->has_edit_link_option_field( $current_prod_id );

			if ( $has_edit_option_link ) {
				$edit_link = add_query_arg(
					array(
						'yaye_cart_item_key' => $cart_item['key'],
						'_nonce'             => wp_create_nonce( 'yayextra-option-edit' ),
					),
					$product_permalink
				);

				$cart_data[] = array(
					'name'  => '<a href="' . $edit_link . '" class="yayextra-option-edit-link">' . esc_html__( 'Edit option field', 'yayextra' ) . '</a>',
					'value' => '',
				);
			}
		}

		return $cart_data;
	}

	/**
	 * Display on orders and email notifications (save as custom order item meta data).
	 *
	 * @param object $item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $values Cart item data.
	 * @param object $order Order.
	 */
	public function display_option_on_orders_and_emails( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['yaye_custom_option'] ) ) {
			$settings         = get_option( 'yaye_settings' );
			$general_settings = $settings['general'];
			$product_quantity = $item->get_quantity();
			$stock_data       = array();
			$options_data     = $values['yaye_custom_option'];

			$product_id  = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];

			foreach ( $options_data as $option_set_id => $custom_option ) {
				foreach ( $custom_option as $option_id => $option ) {
					$option_meta = CustomPostType::get_option( (int) $option_set_id, $option_id );

					if ( ! empty( $option['option_value'] ) ) {
						if ( is_array( $option['option_value'] ) ) {
							$option_meta  = CustomPostType::get_option( (int) $option_set_id, $option_id );
							$option_value = array();
							foreach ( $option['option_value'] as $val ) {
								if ( ! empty( $general_settings['show_additional_price'] ) && ! empty( $val['option_cost'] ) ) {

									$option_cost_org = 0; 
									$cost_type = null;
									if ( ! empty( $option_meta ) && ! empty( $option_meta['optionValues'] ) ) {
										foreach ( $option_meta['optionValues'] as $option_meta_value ) {
											if( trim($val['option_val']) == trim($option_meta_value['value']) ) {
												$additional_cost = $option_meta_value['additionalCost'];
												if( !empty( $additional_cost ) ) {
													$cost_type = $additional_cost['costType']['value'];
													$option_cost_org = floatval( $additional_cost['value'] );
													break;
												}
											}	
										}
									}

									$option_cost = apply_filters( 'yaye_option_cost_display_orders_and_emails', Utils::get_price_from_yaycurrency( floatval( $val['option_cost'] ) ), $option_cost_org, $cost_type, $values['yaye_product_price_original'], $product_id );
									$val_string  = $val['option_val'] . ' ( + ' . wc_price( $option_cost ) . ' )';
								} else {
									$val_string = $val['option_val'];
								}
								array_push( $option_value, $val_string );

								// Save stock of value option after checkout - start.
								// Check option value has stock ?
								$has_stock = false;
								if ( ! empty( $option_meta['optionValues'] ) ) {
									foreach ( $option_meta['optionValues'] as  $opt_val_meta ) {
										if ( ! empty( $opt_val_meta ) && trim($opt_val_meta['value']) === trim($val['option_val']) && ! empty( $opt_val_meta['manageStock'] ) && $opt_val_meta['manageStock']['isEnabled'] && (int) $opt_val_meta['manageStock']['quantity'] >= $product_quantity ) {
											$has_stock = true;
											break;
										}
									}
								}
								if ( $has_stock ) {
									$stock_data[0][] = array(
										'option_set_id' => $option_set_id,
										'option_id'     => $option_id,
										'quantity'      => $product_quantity,
										'option_val'    => $val['option_val'],
									);
								}
								// Save stock of value option after checkout - end.

							}

							$item->add_meta_data( $option['option_name'], implode( ', ', $option_value ) );
						} else {

							if ( isset( $option['option_type'] ) && 'file_upload' === $option['option_type'] ) {
								$option_value = '<a href="' . $option['option_value'] . '">' . $option['file_name'] . '</a>';
							} else {
								$option_value = $option['option_value'];
							}

							$item->add_meta_data( $option['option_name'], $option_value );

						}
					}
				}
			}

			if ( ! empty( $stock_data ) ) {
				$yaye_stock_db = get_option( 'yaye_stock_flag' );
				if ( ! empty( $yaye_stock_db ) && ! empty( $yaye_stock_db[0] ) ) {
					foreach ( $stock_data[0] as $stock_el ) {
						array_push( $yaye_stock_db[0], $stock_el );
					};
					update_option( 'yaye_stock_flag', $yaye_stock_db );
				} else {
					update_option( 'yaye_stock_flag', $stock_data );
				}
			}
		}
	}

	/**
	 * Update order id for stock flag.
	 *
	 * @param int   $order_id The order id.
	 * @param array $data The data.
	 */
	public function checkout_update_after_created_order( $order_id, $data ) {
		if ( ! empty( $order_id ) && ! empty( get_option( 'yaye_stock_flag' ) ) ) {
			$stocks = get_option( 'yaye_stock_flag' );
			if ( ! empty( $stocks[0] ) ) {
				$stock_collects = array();
				// Collect the same option value together.
				foreach ( $stocks[0] as $stock ) {
					if ( ! empty( $stock_collects ) ) {
						$has_same = false;
						foreach ( $stock_collects as $idx => $stock_collect ) {
							if ( $stock_collect['option_set_id'] === $stock['option_set_id'] &&
							$stock_collect['option_id'] === $stock['option_id'] &&
							trim($stock_collect['option_val']) === trim($stock['option_val']) ) {
								$stock_collects[ $idx ]['quantity'] += $stock['quantity'];
								$has_same                            = true;
								break;
							}
						}
						if ( ! $has_same ) {
							$stock_collects[ count( $stock_collects ) ] = array(
								'option_set_id' => $stock['option_set_id'],
								'option_id'     => $stock['option_id'],
								'option_val'    => $stock['option_val'],
								'quantity'      => $stock['quantity'],
							);
						}
					} else {
						$stock_collects[0] = array(
							'option_set_id' => $stock['option_set_id'],
							'option_id'     => $stock['option_id'],
							'option_val'    => $stock['option_val'],
							'quantity'      => $stock['quantity'],
						);
					}
				}

				// Update stock of option value - start.
				if ( ! empty( $stock_collects ) ) {
					foreach ( $stock_collects as $stock_el ) {
						if ( ! empty( $stock_el ) ) {
							$option_metas = get_post_meta( $stock_el['option_set_id'], '_yaye_options', true );
							if ( ! empty( $option_metas ) ) {
								foreach ( $option_metas as $index => $opt ) {
									if ( $stock_el['option_id'] === $opt['id'] ) {
										if ( ! empty( $opt['optionValues'] ) ) {
											foreach ( $opt['optionValues'] as $inx => $opt_val ) {
												if ( trim($stock_el['option_val']) === trim($opt_val['value']) ) {
													if ( ! empty( $opt_val['manageStock'] ) && ! empty( $opt_val['manageStock']['isEnabled'] ) && (int) $opt_val['manageStock']['quantity'] >= $stock_el['quantity'] ) {
														$option_metas[ $index ]['optionValues'][ $inx ]['manageStock']['quantity'] = (int) $opt_val['manageStock']['quantity'] - $stock_el['quantity'];
													}
												}
											}
										}
									}
								}
								$update_result = update_post_meta( $stock_el['option_set_id'], '_yaye_options', $option_metas );
							}
						}
					}
				}
				// Update stock of option value - end.

			}
		}
		// Remove stock flag option after stock of option value had updated.
		if ( ! empty( get_option( 'yaye_stock_flag' ) ) ) {
			$delete_stock_flag = delete_option( 'yaye_stock_flag' );
		}

	}

	/**
	 * Remove stock flag option if processing create order is fail.
	 *
	 * @param object $order The order.
	 */
	public function checkout_order_exception_after_created_order( $order ) {
		if ( ! empty( get_option( 'yaye_stock_flag' ) ) ) {
			$delete_stock_flag = delete_option( 'yaye_stock_flag' );
		}
	}

	/**
	 * Check all cart items for errors.
	 */
	public function handle_check_cart_items() {
		$error_notice  = array();
		$passed        = true;
		$cart_contents = WC()->cart->cart_contents;
		$this->remove_extra_product_in_cart( $cart_contents );
		$this->add_extra_product_in_cart( $cart_contents );
		$options_with_stock_in_cart = $this->get_options_with_stock_in_cart( $cart_contents );

		if ( ! empty( $options_with_stock_in_cart ) ) {
			foreach ( $options_with_stock_in_cart as $opt_stock_cart ) {
				$option_meta = CustomPostType::get_option( $opt_stock_cart['option_set_id'], $opt_stock_cart['option_id'] );

				if ( ! empty( $option_meta ) && ! empty( $option_meta['optionValues'] ) ) {
					foreach ( $option_meta['optionValues'] as $option_meta_el ) {
						if ( ! empty( $option_meta_el ) && ! empty( $option_meta_el['manageStock'] ) && ! empty( $option_meta_el['manageStock']['isEnabled'] ) && trim($opt_stock_cart['option_val']) === trim($option_meta_el['value']) ) {
							$stock_db = (int) $option_meta_el['manageStock']['quantity'];
							if ( $opt_stock_cart['quantity'] > $stock_db ) {
								$passed       = false;
								$opt_val_name = $option_meta['name'] . ': ' . $option_meta_el['value'];
								$message      = sprintf(
								/* translators: %1$s: option value. */
									__( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ),
									$opt_val_name,
									$stock_db
								);

								$error_notice[] = $message;
								break 2;
							}
						}
					}
				}
			}
		}

		if ( ! empty( $error_notice ) ) {
			wc_add_notice( implode( ' < br > ', $error_notice ), 'error' );
		}

		return $passed;
	}

	/**
	 * Add fee or discount.
	 *
	 * @param object $cart The cart.
	 */
	public function add_fee_discount_by_action( $cart ) {
		$fee_discount_arr       = array();
		$woocommerce_calc_taxes = get_option( 'woocommerce_calc_taxes' );
		foreach ( $cart->cart_contents as $cart_content ) {
			if ( ! empty( $cart_content['yaye_custom_option'] ) ) {
				$quantity          = $cart_content['quantity'];
				$fee_discount_list = $this->get_fee_discount_by_action( $cart_content['yaye_custom_option'] );
				if ( ! empty( $fee_discount_list ) ) {
					$tax_class           = Utils::get_tax_class( $cart_content["data"] );
					$tax_status_function = is_callable( array( $cart_content["data"], 'get_tax_status' ) ) ? $cart_content["data"]->get_tax_status() : $cart_content["data"]->tax_status;

					foreach ( $fee_discount_list as $action_id => $sub_actions ) {
						foreach ( $sub_actions as $sub_action ) {
							$sub_action_value = 0;
							if ( 'add_fee' === $sub_action['type'] ) {
								$sub_action_value = floatval( $sub_action['value'] ) * $quantity;
							} elseif ( 'add_discount' === $sub_action['type'] ) {
								$sub_action_value = floatval( -$sub_action['value'] ) * $quantity;
							}

							$sub_action_name = $sub_action['name'];
							$tax 			 = "yes" == $woocommerce_calc_taxes && "taxable" == $tax_status_function  ? TRUE : FALSE;
						
							if ( 0 !== $sub_action_value  ) {
								$fee_discount_arr[ $sub_action_name ]['tax'] 	   = $tax;
								$fee_discount_arr[ $sub_action_name ]['tax_class'] = $tax_class;
								if ( isset( $fee_discount_arr[ $sub_action_name ] ) && ! empty( $fee_discount_arr[ $sub_action_name ]['cost'] ) ) {
									$fee_discount_arr[ $sub_action_name ]['cost'] = $fee_discount_arr[ $sub_action_name ]['cost'] + $sub_action_value;
								} else {
									$fee_discount_arr[ $sub_action_name ]['cost'] = $sub_action_value;
								}
							}
						}
					}
				}
			};
		}

		$apply_tax_for_fee_hook = apply_filters( 'yayextra_tax_for_fee_apply', true );
        if ( ! empty( $fee_discount_arr ) ) {
            foreach ( $fee_discount_arr as $name => $fee_discount ) {
                $tax_cus       = $fee_discount['tax'];
                $tax_class_cus = $fee_discount['tax_class'];
                if ( true === $apply_tax_for_fee_hook ) {
                    $cart->add_fee( $name, $fee_discount['cost'],  $tax_cus, $tax_class_cus );
                } else {
                    $cart->add_fee( $name, $fee_discount['cost'] );
                }
            }
        }
	}

	/**
	 * Add fee or discount.
	 *
	 * @param array $options_cart_data The options cart data.
	 */
	public function get_fee_discount_by_action( $options_cart_data ) {
		$result = array();
		foreach ( $options_cart_data  as $option_set_id => $cart_option ) {
			$option_set_status = (int) get_post_meta( $option_set_id, '_yaye_status', true );
			if( 1 === $option_set_status ) {
				$action_list = get_post_meta( $option_set_id, '_yaye_actions', true );
				if ( ! empty( $action_list ) && ! empty( $cart_option ) ) {
					foreach ( $action_list as $action ) {
						if ( $this->check_logic_action( $action, $cart_option ) ) {
							$sub_actions = $action['subActions'];
							foreach ( $sub_actions as $sub_action ) {
								$result[ $action['id'] ][] = array(
									'type'  => $sub_action['subActionType']['value'],
									'name'  => $sub_action['subActionName'],
									'value' => $sub_action['subActionValue'],
								);
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Check logic action.
	 *
	 * @param array $action Action data.
	 * @param array $cart_option Cart option data.
	 */
	public function check_logic_action( $action, $cart_option ) {
		$conditions = $action['conditions'];
		$match_type = $action['matchType']['value'];

		if ( empty( $conditions ) ) {
			return false;
		}

		if ( 'any' === $match_type ) {
			foreach ( $conditions as $condition ) {
				$logic = $this->get_logic_action_result( $condition, $cart_option );
				if ( $logic ) {
					return true;
				}
			}
			return false;
		} else { // 'all' === $match_type.
			foreach ( $conditions as $condition ) {
				$logic = $this->get_logic_action_result( $condition, $cart_option );
				if ( ! $logic ) {
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * Get logic action result.
	 *
	 * @param array $condition The condition data.
	 * @param array $cart_option The cart option data.
	 *
	 * @return boolean
	 */
	public function get_logic_action_result( $condition, $cart_option ) {
		$option_id   = $condition['optionId']['id'];
		$option_type = $condition['type']['value'];
		$comparation = $condition['comparation']['value'];

		$option_value = ! empty( $condition['value'] ) ? $condition['value'] : '';
		
		if ( ! empty( $option_value ) && is_array( $option_value ) ) {
			if ( 'checkbox' === $option_type || 'button_multi' === $option_type || 'swatches_multi' === $option_type ) {
				$opt_val_temp = array();
				foreach ( $option_value as $opt_val ) {
					array_push( $opt_val_temp, $opt_val['value'] );
				}
				$option_value = $opt_val_temp;
			} elseif ( 'radio' === $option_type || 'button' === $option_type || 'dropdown' === $option_type || 'swatches' === $option_type ) {
				$opt_val_temp = ! empty( $option_value['value'] ) ? $option_value['value'] : $option_value[0]['value'];
				$option_value = $opt_val_temp;
			}

			if( [null] == $option_value ) {
				$option_value = '';
			}
		}

		if ( 'text' === $option_type || 'textarea' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( is_string( $option['option_value'] ) && is_string( $option_value ) ) {
						if ( 'match' === $comparation ) {
							if ( trim($option['option_value']) === trim($option_value) ) {
								return true;
							}
						} elseif ( 'not_match' === $comparation ) {
							if ( trim($option['option_value']) !== trim($option_value) ) {
								return true;
							}
						} elseif ( 'contains' === $comparation ) {
							if ( strpos( $option['option_value'], $option_value ) !== false ) {
								return true;
							}
						}
					} else {
						return false;
					}
				}
			}

			return false;
		} elseif ( 'number' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( 'equal' === $comparation ) {
						if ( floatval( $option['option_value'] ) === floatval( $option_value ) ) {
							return true;
						}
					} elseif ( 'less_than' === $comparation ) {
						if ( floatval( $option['option_value'] ) < floatval( $option_value ) ) {
							return true;
						}
					} elseif ( 'greater_than' === $comparation ) {
						if ( floatval( $option['option_value'] ) > floatval( $option_value ) ) {
							return true;
						}
					} elseif ( 'less_than_or_equal' === $comparation ) {
						if ( floatval( $option['option_value'] ) <= floatval( $option_value ) ) {
							return true;
						}
					} elseif ( 'greater_than_or_equal' === $comparation ) {
						if ( floatval( $option['option_value'] ) >= floatval( $option_value ) ) {
							return true;
						}
					}
				}
			}

			return false;
		} elseif ( 'checkbox' === $option_type || 'swatches_multi' === $option_type || 'button_multi' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( is_array( $option['option_value'] ) && is_array( $option_value ) ) {
						if ( 'is_one_of' === $comparation ) {
							foreach ( $option['option_value'] as $opt_cart_val ) {
								if ( in_array( $opt_cart_val['option_val'], $option_value, true ) ) {
									return true;
								};
							}
							return false;
						} elseif ( 'is_not_one_of' === $comparation ) {
							foreach ( $option['option_value'] as $opt_cart_val ) {
								if ( in_array( $opt_cart_val['option_val'], $option_value, true ) ) {
									return false;
								};
							}
							return true;
						}
					} else {
						return false;
					}
				}
			}
			return false;
		} elseif ( 'radio' === $option_type || 'button' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( is_array( $option['option_value'] ) ) {
						if ( 'is' === $comparation ) {
							if ( trim($option_value) === trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						} elseif ( 'is_not' === $comparation ) {
							if ( trim($option_value) !== trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		} elseif ( 'dropdown' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( is_array( $option['option_value'] ) ) {
						if ( 'is' === $comparation ) {
							if ( trim($option_value) === trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						} elseif ( 'is_not' === $comparation ) {
							if ( trim($option_value) !== trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		} elseif ( 'swatches' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					if ( is_array( $option['option_value'] ) ) {
						if ( 'is' === $comparation ) {
							if ( trim($option_value) === trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						} elseif ( 'is_not' === $comparation ) {
							if ( trim($option_value) !== trim($option['option_value'][0]['option_val']) ) {
								return true;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		} elseif ( 'date_picker' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					$start_date   = gmdate( 'Y-m-d', strtotime( $option_value['from_date'] ) );
					$end_date     = gmdate( 'Y-m-d', strtotime( $option_value['to_date'] ) );
					$current_date = gmdate( 'Y-m-d', strtotime( $option['option_value'] ) );

					if ( 'between' === $comparation ) {
						if ( $start_date <= $current_date && $current_date <= $end_date ) {
							return true;
						}
					} elseif ( 'not_between' === $comparation ) {
						if ( $start_date > $current_date || $current_date > $end_date ) {
							return true;
						}
					}
					return false;
				}
			}
			return false;
		} elseif ( 'time_picker' === $option_type ) {
			foreach ( $cart_option as $option_cart_id => $option ) {
				if ( $option_cart_id === $option_id ) {
					$start_time   = gmdate( 'H:i', strtotime( $option_value['from_time'] ) );
					$end_time     = gmdate( 'H:i', strtotime( $option_value['to_time'] ) );
					$current_time = gmdate( 'H:i', strtotime( $option['option_value'] ) );

					if ( 'between' === $comparation ) {
						if ( $start_time <= $current_time && $current_time <= $end_time ) {
							return true;
						}
					} elseif ( 'not_between' === $comparation ) {
						if ( $start_time > $current_time || $current_time > $end_time ) {
							return true;
						}
					}
					return false;
				}
			}
			return false;
		}

		return false;
	}

	/**
	 * Check is edit option mode ?
	 */
	public function is_edit_option_mode() {
		return ! empty( $_GET['yaye_cart_item_key'] ) && isset( $_GET['_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_nonce'] ), 'yayextra-option-edit' );
	}

	/**
	 * Change add to cart text.
	 */
	public function change_add_to_cart_text() {
		return esc_attr__( 'Update cart', 'yayextra' );
	}

	/**
	 * Add Edit Option Field link in minicart.
	 *
	 * @param string $cart_item_quantity_product_price_span The span class quantity sprintf s times s cart item quantity product price span.
	 * @param array  $cart_item The cart item.
	 * @param string $cart_item_key The cart item key.
	 */
	public function add_link_edit_option_field_in_minicart( $cart_item_quantity_product_price_span, $cart_item, $cart_item_key ) {
		if ( is_cart() ) return $cart_item_quantity_product_price_span;
		
		$_product          = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_permalink = null;
		if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
		}

		// Add edit option field link with product has applied opiton field.
		if ( ! empty( $product_permalink ) ) {
			$current_prod_id = $_product->get_parent_id();
			if ( empty( $current_prod_id ) ) {
				$current_prod_id = $_product->get_id();
			}
			$has_edit_option_link = $this->has_edit_link_option_field( $current_prod_id );
			if ( $has_edit_option_link ) {
				$edit_link = add_query_arg(
					array(
						'yaye_cart_item_key' => $cart_item['key'],
						'_nonce'             => wp_create_nonce( 'yayextra-option-edit' ),
					),
					$product_permalink
				);
				echo '<p><a href="' . esc_attr( $edit_link ) . '" class="yayextra-option-edit-link-minicart">' . esc_html__( 'Edit option field', 'yayextra' ) . '</a></p>';
			}
		}

		return $cart_item_quantity_product_price_span;
	}

	/**
	 * Check has edit link option field?
	 *
	 * @param int $current_prod_id The current product id.
	 */
	public function has_edit_link_option_field( $current_prod_id ) {
		$has_edit_option_link = false;
		$settings             = Utils::get_settings();
		$opt_sets             = $this->get_option_set_of_product( $current_prod_id, $settings );

		if ( ! empty( $opt_sets ) ) {
			foreach ( $opt_sets as $opt_set_data ) {
				if ( ! empty( $opt_set_data['options'] ) ) {
					$has_edit_option_link = true;
					break;
				}
				if ( $has_edit_option_link ) {
					break;
				}
			}
		}
		return $has_edit_option_link;
	}

	/**
	 * Add custom cart item class.
	 *
	 * @param string $class The class.
	 * @param array  $cart_item The cart item.
	 * @param string $cart_item_key The cart item key.
	 */
	public function custom_cart_item_class( $class, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['yaye_add_extra_product'] ) ) {
			$class .= ' yayextra-product-extra-opt';
		}
		return $class;
	}

	/**
	 * Add custom mini cart item class.
	 *
	 * @param string $class The class.
	 * @param array  $cart_item The cart item.
	 * @param string $cart_item_key The cart item key.
	 */
	public function custom_mini_cart_item_class( $class, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['yaye_add_extra_product'] ) ) {
			$class .= ' yayextra-product-extra-opt';
		}
		if ( ! empty( $cart_item['yaye_custom_option'] ) ) {
			$settings = Utils::get_settings();
			if ( isset( $settings['general'] ) && ! $settings['general']['show_value_mini_cart'] ) {
				$class .= ' yayextra-hide-extra-options-values-mini-cart';
			}
		}
		return $class;
	}

	/**
	 * Remove linked product where parent product had removed.
	 *
	 * @param string $cart_item_key The cart item key.
	 * @param object $cart The cart.
	 */
	public function handle_after_cart_item_removed( $cart_item_key, $cart ) {
		$cart_contents         = $cart->cart_contents;
		$cart_contents_removed = $cart->removed_cart_contents;
		if ( is_array( $cart_contents_removed ) &&
		! empty( $cart_contents_removed[ $cart_item_key ] ) &&
		! empty( $cart_contents_removed[ $cart_item_key ]['yaye_custom_option'] ) &&
		is_array( $cart_contents ) &&
		! empty( $cart_contents ) ) {

			$options_data = $cart_contents_removed[ $cart_item_key ]['yaye_custom_option'];

			foreach ( $cart_contents as $item_key => $cart_data ) {
				if ( ! empty( $cart_data['yaye_parent_product_id'] ) &&
				! empty( $cart_data['yaye_option_set_id_linked_product'] ) &&
				! empty( $cart_data['yaye_option_id_linked_product'] ) &&
				! empty( $cart_data['yaye_option_val_linked_product'] )
				) {
					foreach ( $options_data as $option_set_id => $custom_option ) {
						foreach ( $custom_option as $option_id => $option ) {
							if ( ! empty( $option['option_value'] ) ) {
								if ( is_array( $option['option_value'] ) ) {
									foreach ( $option['option_value'] as $val ) {
										if ( (int) $cart_data['yaye_parent_product_id'] === (int) $cart_contents_removed[ $cart_item_key ]['product_id']
										&& (int) $cart_data['yaye_option_set_id_linked_product'] === (int) $option_set_id
										&& $cart_data['yaye_option_id_linked_product'] === $option_id
										&& trim($cart_data['yaye_option_val_linked_product']) === trim($val['option_val']) ) {
											WC()->cart->remove_cart_item( $item_key );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Update quantity linked product where parent product had updated.
	 *
	 * @param string $cart_item_key The cart item key.
	 * @param int    $quantity The product quantity.
	 * @param int    $old_quantity The old product quantity.
	 */
	public function handle_after_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity ) {
		$cart_contents = WC()->cart->cart_contents;

		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) && ! empty( $cart_contents[ $cart_item_key ] ) ) {
			$item_updated = $cart_contents[ $cart_item_key ];

			if ( ! empty( $item_updated ) && ! empty( $item_updated['yaye_custom_option'] ) ) {
				$options_data = $item_updated['yaye_custom_option'];

				foreach ( $cart_contents as $item_key => $cart_data ) {
					if ( $item_key !== $cart_item_key ) {
						if ( ! empty( $cart_data['yaye_parent_product_id'] ) &&
						! empty( $cart_data['yaye_option_set_id_linked_product'] ) &&
						! empty( $cart_data['yaye_option_id_linked_product'] ) &&
						! empty( $cart_data['yaye_option_val_linked_product'] )
						) {
							foreach ( $options_data as $option_set_id => $custom_option ) {
								foreach ( $custom_option as $option_id => $option ) {
									if ( ! empty( $option['option_value'] ) ) {
										if ( is_array( $option['option_value'] ) ) {
											foreach ( $option['option_value'] as $val ) {
												if ( (int) $cart_data['yaye_parent_product_id'] === (int) $item_updated['product_id']
												&& (int) $cart_data['yaye_option_set_id_linked_product'] === (int) $option_set_id
												&& $cart_data['yaye_option_id_linked_product'] === $option_id
												&& trim($cart_data['yaye_option_val_linked_product']) === trim($val['option_val']) ) {
													if ( $quantity !== $cart_data['quantity'] ) {
														WC()->cart->set_quantity( $item_key, $quantity, true );
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Handle cart item thumbnail.
	 *
	 * @param string $image The image.
	 * @param array  $cart_item The cart item.
	 * @param string $cart_item_key The cart item key.
	 */
	public function handle_cart_item_thumbnail( $image = '', $cart_item = array(), $cart_item_key = '' ) {
		// For YayExtra pro version.
		return $image;
	}

	/**
	 * Hook into Order image (email).
	 *
	 * @param string $image The image.
	 * @param object $item The item.
	 */
	public function handle_order_item_thumbnail( $image, $item ) {
		// For YayExtra pro version.
		return $image;
	}

	/**
	 * Print global style.
	 */
	public function print_global_style() {
		$settings = Utils::get_settings();

		if ( ! empty( $settings['globalStyle'] ) ) {
			$css            = '.yayextra-option-field-wrap{ display: none;}';
			$style_settings = $settings['globalStyle'];

			// General style.
			if ( ! empty( $style_settings['general'] ) ) {
				$general_setts = $style_settings['general'];
				$css          .= '.yayextra-option-field-name, .yayextra-total-price .total-price-title, .yayextra-extra-subtotal-price .total-price-title {';
				if ( ! empty( $general_setts['label_font_size'] ) && '0px' !== $general_setts['label_font_size'] ) {
					$css .= 'font-size: ' . $general_setts['label_font_size'] . 'px !important;';
				}
				if ( ! empty( $general_setts['label_font_weight'] ) ) {
					$css .= 'font-weight: ' . $general_setts['label_font_weight']['value'] . ' !important;';
				}
			
				$css .= '}';

				$css .= '.yayextra-total-price .total-price, .yayextra-extra-subtotal-price .total-price {';
				if ( ! empty( $general_setts['total_price_font_size'] ) && '0px' !== $general_setts['total_price_font_size'] ) {
					$css .= 'font-size: ' . $general_setts['total_price_font_size'] . 'px !important;';
				}
				if ( ! empty( $general_setts['total_price_font_weight'] ) ) {
					$css .= 'font-weight: ' . $general_setts['total_price_font_weight']['value'] . ' !important;';
				}

				$css .= '}';
			}

			// Custom css.
			if ( ! empty( $style_settings['custom'] ) ) {
				$custom_setts = $style_settings['custom'];
				if ( ! empty( $custom_setts['custom_css'] ) ) {
					$css .= $custom_setts['custom_css'];
				}
			}

			echo '<style>' . wp_kses_post( $css ) . '</style>';
		}
	}

	/**
	 * Get options with stock in cart.
	 *
	 * @param array $cart_contents The cart contents data.
	 */
	public function get_options_with_stock_in_cart( $cart_contents ) {
		$stock_data = array();
		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
			foreach ( $cart_contents  as $cart_item => $cart_data ) {
				if ( ! empty( $cart_data ) && ! empty( $cart_data['yaye_custom_option'] ) ) {
					foreach ( $cart_data['yaye_custom_option'] as $option_set_id => $options ) {
						if ( ! empty( $options ) ) {
							foreach ( $options as $option_id => $option ) {
								if ( ! empty( $option ) && ! empty( $option['option_value'] ) && is_array( $option['option_value'] ) ) {
									$option_db = CustomPostType::get_option( (int) $option_set_id, $option_id );

									foreach ( $option['option_value'] as $opt_val ) {
										if ( ! empty( $option_db['optionValues'] ) ) {
											foreach ( $option_db['optionValues'] as $opt_db_val ) {
												if ( ! empty( $opt_db_val['manageStock'] ) && ! empty( $opt_db_val['manageStock']['isEnabled'] ) && trim($opt_val['option_val']) === trim($opt_db_val['value']) ) {
													$stock_data[] = array(
														'option_set_id' => $option_set_id,
														'option_id'     => $option_id,
														'quantity'      => $cart_data['quantity'],
														'option_val'    => $opt_db_val['value'],
													);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		// Collect the same option value together.
		$stock_collects = array();
		if ( ! empty( $stock_data ) ) {
			foreach ( $stock_data as $stock ) {
				if ( ! empty( $stock_collects ) ) {
					$has_same = false;
					foreach ( $stock_collects as $idx => $stock_collect ) {
						if ( $stock_collect['option_set_id'] === $stock['option_set_id'] &&
						$stock_collect['option_id'] === $stock['option_id'] &&
						trim($stock_collect['option_val']) === trim($stock['option_val']) ) {
							$stock_collects[ $idx ]['quantity'] += $stock['quantity'];
							$has_same                            = true;
							break;
						}
					}
					if ( ! $has_same ) {
						$stock_collects[ count( $stock_collects ) ] = array(
							'option_set_id' => $stock['option_set_id'],
							'option_id'     => $stock['option_id'],
							'option_val'    => $stock['option_val'],
							'quantity'      => $stock['quantity'],
						);
					}
				} else {
					$stock_collects[0] = array(
						'option_set_id' => $stock['option_set_id'],
						'option_id'     => $stock['option_id'],
						'option_val'    => $stock['option_val'],
						'quantity'      => $stock['quantity'],
					);
				}
			}
		}

		return $stock_collects;
	}

	/**
	 * Get product apply list.
	 *
	 * @param object $opt_set_data The option set data.
	 */
	private function get_product_applies( $opt_set_data ) {
		$prod_apply_list  = array();
		$prod_filter_type = ! empty( $opt_set_data['products']['product_filter_type'] ) ? (int) $opt_set_data['products']['product_filter_type'] : 1;
		if ( 1 === $prod_filter_type ) { // Choose product one by one.
			if ( ! empty( $opt_set_data['products'] ) && ! empty( $opt_set_data['products']['product_filter_by_conditions'] ) ) {
				$prod_apply_list = $opt_set_data['products']['product_filter_one_by_one'];
			}
		} elseif ( 2 === $prod_filter_type ) { // Choose product by conditions.
			if ( ! empty( $opt_set_data['products'] ) && ! empty( $opt_set_data['products']['product_filter_by_conditions'] ) ) {
				$prod_conditions      = $opt_set_data['products']['product_filter_by_conditions'];
				$database             = new Database();
				$prod_apply_meta_list = $database->get_product_match_option_set_list( $prod_conditions['conditions'], $prod_conditions['match_type'] );
				foreach ( $prod_apply_meta_list as $prod ) {
					array_push( $prod_apply_list, (int) $prod->id );
				}
			}
		}

		return $prod_apply_list;
	}

	/**
	 * Add extra product
	 *
	 * @param int $product_id The product id.
	 * @param int $quantity The product quantity.
	 */
	private function add_extra_product( $product_id, $quantity ) {
		if ( isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) ) {
			if ( ! empty( $_POST['option_field_data'] ) ) {
				$op_field_data = Utils::sanitize_array( $_POST['option_field_data'] );
				foreach ( $op_field_data as $op_set_id => $op ) {
					if ( ! empty( $op ) ) {
						foreach ( $op as $op_id => $op_val ) {
							if ( ! empty( $op_val ) ) {
								$option_meta = CustomPostType::get_option( (int) $op_set_id, $op_id );

								if ( is_array( $op_val ) ) {
									foreach ( $op_val as $val ) {
										if ( ! empty( $option_meta['optionValues'] ) ) {
											foreach ( $option_meta['optionValues'] as $opt_meta_value ) {
												if ( ! empty( $opt_meta_value ) && trim($opt_meta_value['value']) === trim($val) ) {
													if ( ! empty( $opt_meta_value['linkedProduct'] ) ) {
														$linked_product = $opt_meta_value['linkedProduct'];

														if ( $linked_product['isEnabled'] && ! empty( $linked_product['productId'] ) ) {
															$linked_product_id           = (int) $linked_product['productId']['value'];
															$yaye_add_extra_product_item = array(
																'yaye_add_extra_product'        => 1,
																'yaye_parent_product_id'        => $product_id,
																'yaye_option_set_id_linked_product' => $op_set_id,
																'yaye_option_id_linked_product' => $op_id,
																'yaye_option_val_linked_product' => $val,
															);
															if ( $quantity > 0 ) {
																WC()->cart->add_to_cart( $linked_product_id, $quantity, 0, array(), $yaye_add_extra_product_item );
															}
														}
													}
												}
											}
										};
									}
								} else {
									if ( ! empty( $option_meta['optionValues'] ) ) {
										foreach ( $option_meta['optionValues'] as $opt_meta_value ) {
											if ( ! empty( $opt_meta_value ) && trim($opt_meta_value['value']) === trim($op_val) ) {
												if ( ! empty( $opt_meta_value['linkedProduct'] ) ) {
													$linked_product = $opt_meta_value['linkedProduct'];

													if ( $linked_product['isEnabled'] && ! empty( $linked_product['productId'] ) ) {
														$linked_product_id           = (int) $linked_product['productId']['value'];
														$yaye_add_extra_product_item = array(
															'yaye_add_extra_product' => 1,
															'yaye_parent_product_id' => $product_id,
															'yaye_option_set_id_linked_product' => $op_set_id,
															'yaye_option_id_linked_product' => $op_id,
															'yaye_option_val_linked_product' => $op_val,
														);
														if ( $quantity > 0 ) {
															WC()->cart->add_to_cart( $linked_product_id, $quantity, 0, array(), $yaye_add_extra_product_item );
														}
													}
												}
											}
										}
									};
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Update cart option item.
	 *
	 * @param string $cart_edit_key The cart edit key.
	 * @param int    $product_id The product id.
	 * @param array  $cart_contents The cart contents data.
	 */
	private function update_cart_option_item( $cart_edit_key, $product_id, $cart_contents, $variation_id = 0 ) {
		$product = wc_get_product( $product_id );
		// Update product quantity.
		$product_quantity = 0;
		if ( isset( $_REQUEST['quantity'] ) ) {
			$product_quantity = (int) sanitize_text_field( $_REQUEST['quantity'] );
			WC()->cart->set_quantity( $cart_edit_key, $product_quantity, true );
		}

		$option_field_request_data = $this->get_option_field_data( $product_id, $variation_id );

		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
			if ( ! isset( $cart_contents[ $cart_edit_key ]['yaye_custom_option'] ) ) { // If Cart don't have yayextra custom option.
				if ( ! empty( $option_field_request_data ) ) {
					$data_opt_update = array();
					foreach ( $option_field_request_data  as $option_set_id => $custom_option ) {
						foreach ( $custom_option as $option_id => $option ) {
							if ( ! empty( $option ) ) {
								$data_opt_update[ $option_set_id ][ $option_id ] = $option;
							}
						}
					}
					if ( ! empty( $data_opt_update ) ) {
						WC()->cart->cart_contents[ $cart_edit_key ]['yaye_custom_option'] = $data_opt_update;
						WC()->cart->set_session();
					}
				}
			} else { // If Cart already has yayextra custom option.
				$cart_option = $cart_contents[ $cart_edit_key ]['yaye_custom_option'];

				if ( ! empty( $cart_option ) ) {
					foreach ( $cart_option as $opt_set_id => $custom_opt ) {
						foreach ( $custom_opt as $opt_id => $opt ) {
							if ( ! empty( $opt ) ) {
								if ( empty( $option_field_request_data ) && ! isset( $opt['option_type'] ) ) {
									unset( WC()->cart->cart_contents[ $cart_edit_key ]['yaye_custom_option'][ $opt_set_id ][ $opt_id ] );
								} else {
									if ( empty( $option_field_request_data[ $opt_set_id ][ $opt_id ] ) && ! isset( $opt['option_type'] ) ) {
										unset( WC()->cart->cart_contents[ $cart_edit_key ]['yaye_custom_option'][ $opt_set_id ][ $opt_id ] );
									} elseif ( ! empty( $option_field_request_data[ $opt_set_id ][ $opt_id ] ) && $option_field_request_data[ $opt_set_id ][ $opt_id ] !== $opt ) {
										WC()->cart->cart_contents[ $cart_edit_key ]['yaye_custom_option'][ $opt_set_id ][ $opt_id ] = $option_field_request_data[ $opt_set_id ][ $opt_id ];
										// Remove option that had update.
										unset( $option_field_request_data[ $opt_set_id ][ $opt_id ] );

									}
								}
							}
						}
					}

					// Update total option cost for Session
					if( ! empty( $variation_id ) ){
						$product_variation = wc_get_product( $variation_id );
						$total_option_cost = Utils::cal_total_option_cost_on_cart_item_static($cart_option, $product_variation->get_price( 'original' ));
					} else {
						$total_option_cost = Utils::cal_total_option_cost_on_cart_item_static($cart_option, $product->get_price( 'original' ));
					}
					
					WC()->cart->cart_contents[ $cart_edit_key ]['yaye_total_option_cost'] = $total_option_cost;
					WC()->cart->set_session();
				}

				// Update the other option_field_request_data that not have in cart.
				if ( ! empty( $option_field_request_data ) ) {
					foreach ( $option_field_request_data as $optset_id => $cust_opt ) {
						if ( ! empty( $cust_opt ) ) {
							foreach ( $cust_opt as $optid => $opt_data ) {
								if ( ! empty( $opt_data ) ) {
										WC()->cart->cart_contents[ $cart_edit_key ]['yaye_custom_option'][ $optset_id ][ $optid ] = $opt_data;
								}
							}
						}
					}
					WC()->cart->set_session();
				}
			}
		}

		$product_names = array();
		/* translators: %s: product title */
		$product_names[] = ( $product_quantity > 1 ? absint( $product_quantity ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), strip_tags( get_the_title( $product_id ) ) );
		/* translators: %s: product quantity */
		$added_text = sprintf( esc_html( _n( '%s has been updated.', '%s have been updated.', $product_quantity, 'yayextra' ) ), wc_format_list_of_items( array_filter( $product_names ) ) );
		wc_add_notice( $added_text, 'success' );

		$cart_redirect = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : WC()->cart->get_cart_url();
		wp_safe_redirect( $cart_redirect );
		exit;
	}

	/**
	 * Remove extra product in cart page.
	 *
	 * @param array $cart_contents The cart contents data.
	 */
	private function remove_extra_product_in_cart( $cart_contents ) {
		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
			foreach ( $cart_contents  as $cart_item => $cart_data ) {
				if ( ! empty( $cart_data ) && ! empty( $cart_data['yaye_add_extra_product'] ) ) {
					$key_linked_product_item      = $cart_item;
					$parent_product_id            = $cart_data['yaye_parent_product_id'];
					$option_set_id_linked_product = $cart_data['yaye_option_set_id_linked_product'];
					$option_id_linked_product     = $cart_data['yaye_option_id_linked_product'];
					$option_val_linked_product    = $cart_data['yaye_option_val_linked_product'];

					$flag_delete = true;
					foreach ( $cart_contents  as $cart_item_1 => $cart_data_1 ) {
						if ( ! empty( $cart_data_1['yaye_custom_option'] ) ) {
							if ( $cart_data_1['product_id'] === $parent_product_id &&
							! empty( $cart_data_1['yaye_custom_option'][ $option_set_id_linked_product ] ) &&
							! empty( $cart_data_1['yaye_custom_option'][ $option_set_id_linked_product ][ $option_id_linked_product ] ) &&
							! empty( $cart_data_1['yaye_custom_option'][ $option_set_id_linked_product ][ $option_id_linked_product ]['option_value'] )
							) {
								$option_value = $cart_data_1['yaye_custom_option'][ $option_set_id_linked_product ][ $option_id_linked_product ]['option_value'];
								foreach ( $option_value as $opt_val ) {
									if ( trim($opt_val['option_val']) === trim($option_val_linked_product) ) {
										$flag_delete = false;
										break 2;
									}
								}
							}
						}
					}

					if ( $flag_delete ) {
						WC()->cart->remove_cart_item( $key_linked_product_item );
					}
				}
			}
		}
	}

	/**
	 * Add extra product in cart page
	 *
	 * @param array $cart_contents The cart contents data.
	 */
	private function add_extra_product_in_cart( $cart_contents ) {
		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
			foreach ( $cart_contents  as $cart_item => $cart_data ) {
				if ( ! empty( $cart_data ) && ! empty( $cart_data['yaye_custom_option'] ) ) {
					foreach ( $cart_data['yaye_custom_option'] as $option_set_id => $options ) {
						if ( ! empty( $options ) ) {
							foreach ( $options as $option_id => $option ) {
								if ( ! empty( $option ) && ! empty( $option['option_value'] ) && is_array( $option['option_value'] ) ) {
									$option_db = CustomPostType::get_option( (int) $option_set_id, $option_id );

									foreach ( $option['option_value'] as $opt_val ) {
										$flag_add         = true;
										$product_id_extra = 0;
										if ( ! empty( $option_db['optionValues'] ) ) {
											foreach ( $option_db['optionValues'] as $opt_db_val ) {
												if ( ! empty( $opt_db_val['linkedProduct'] ) &&
												! empty( $opt_db_val['linkedProduct']['isEnabled'] ) &&
												trim($opt_val['option_val']) === trim($opt_db_val['value']) &&
												! empty( $opt_db_val['linkedProduct']['productId'] )
												) {
													$product_id_extra = (int) $opt_db_val['linkedProduct']['productId']['value'];

													foreach ( $cart_contents  as $cart_item_1 => $cart_data_1 ) {
														if ( ! empty( $cart_data_1 ) &&
														! empty( $cart_data_1['yaye_add_extra_product'] ) &&
														$cart_data['product_id'] === $cart_data_1['yaye_parent_product_id'] &&
														$option_set_id === $cart_data_1['yaye_option_set_id_linked_product'] &&
														$option_id === $cart_data_1['yaye_option_id_linked_product'] &&
														trim($opt_val['option_val']) === trim($cart_data_1['yaye_option_val_linked_product'])
														) {
															$flag_add = false;
															break 2;
														}
													}
												}
											}
										}

										// Add extra product.
										if ( $flag_add && ! empty( $product_id_extra ) ) {
											$yaye_add_extra_product_item = array(
												'yaye_add_extra_product'            => 1,
												'yaye_parent_product_id'            => $cart_data['product_id'],
												'yaye_option_set_id_linked_product' => $option_set_id,
												'yaye_option_id_linked_product'     => $option_id,
												'yaye_option_val_linked_product'    => $opt_val['option_val'],
											);
											$quantity                    = (int) $cart_data['quantity'];
											WC()->cart->add_to_cart( $product_id_extra, $quantity, 0, array(), $yaye_add_extra_product_item );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Get all extra option field of product
	 *
	 * @param int $product_id The product id.
	 */
	private function get_all_option_of_product( $product_id ) {
		$settings = Utils::get_settings();
		$opt_sets = $this->get_option_set_of_product( $product_id, $settings );

		$result = array();
		if ( ! empty( $opt_sets ) ) {
			foreach ( $opt_sets as $opt_set_data ) {
				if ( ! empty( $opt_set_data['options'] ) ) {
					$opt_field_list = $opt_set_data['options'];
					foreach ( $opt_field_list as $opt_field ) {
						$option_id                                   = $opt_field['id'];
						$result[ $opt_set_data['id'] ][ $option_id ] = $opt_field;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get all option sets of current product following setting (applied_option_set_type)
	 *
	 * @param int   $current_prod_id Product id.
	 * @param array $settings Settings of option sets.
	 */
	public function get_option_set_of_product( $current_prod_id, $settings ) {
		$result   = array();
		$opt_sets = CustomPostType::get_list_option_set( array(), true ); // get all.

		if ( ! empty( $opt_sets ) ) {
			foreach ( $opt_sets as $opt_set ) {
				$opt_set_id   = (int) $opt_set->ID;
				$opt_set_data = CustomPostType::get_option_set( $opt_set_id );

				if ( 1 === (int) $opt_set_data['status'] ) { // option set is enable.
					// Get all products that applied option sets.
					$prod_apply_list = $this->get_product_applies( $opt_set_data );

					if ( ! empty( $prod_apply_list ) ) {
						if ( in_array( $current_prod_id, $prod_apply_list, true ) ) {
							if ( isset( $settings['general'] ) && 'first_applicable' === $settings['general']['applied_option_sets']['value'] ) {
								return array( $opt_set_data );
							} else {
								array_push( $result, $opt_set_data );
							}
						}
					}
				}
			};
		}

		if ( ! empty( $result ) ) {
			if ( isset( $settings['general'] ) ) {
				if ( 'most_options' === $settings['general']['applied_option_sets']['value'] ) {
					$maxval = $result[0];
					foreach ( $result as $val ) {
						if ( count( $val['options'] ) > count( $maxval['options'] ) ) {
								$maxval = $val;
						}
					}
					return array( $maxval );
				} elseif ( 'least_options' === $settings['general']['applied_option_sets']['value'] ) {
					$minval = $result[0];
					foreach ( $result as $val ) {
						if ( count( $val['options'] ) < count( $minval['options'] ) ) {
								$minval = $val;
						}
					}
					return array( $minval );
				}
			}
		}

		return $result;
	}
}
