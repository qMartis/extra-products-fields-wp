<?php

use YayExtra\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_folder     = YAYE_PATH . 'includes/Templates';
$general_settings    = $params['settings']['general'];
$data                = $params['data'];
$is_edit_option_mode = $params['is_edit_option_mode'];
$option_value_list   = $data['optionValues'];
$is_required         = $data['isRequired'];
$class_names         = '';
if ( ! empty( $data['classNames'] ) ) {
	$class_names = Utils::convert_string( $data['classNames'], ',', ' ' );
}

if ( ! empty( $is_required ) ) {
	if ( ! empty( $class_names ) ) {
		$class_names .= ' yayextra-field-required';
	} else {
		$class_names .= 'yayextra-field-required';
	}
}

$placeholder_value = ! empty( $data['placeholderValue'] ) ? $data['placeholderValue'] : '';

// Output label
echo '<div class="yayextra-option-field-wrap" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="dropdown">';
Utils::get_template_part( $template_folder, 'label_field', array( 'data' => $data ) );
$opt_set_id = $params['opt_set_id'];
if ( ! empty( $option_value_list ) ) {
	echo '<div class="">';
	echo '<select id="' . esc_attr( $data['id'] ) . '" class="' . esc_attr( $class_names ) . '" name="option_field_data[' . esc_attr( $opt_set_id ) . '][' . esc_attr( $data['id'] ) . ']">';

	if ( '' !== $placeholder_value ) {
		echo '<option data-addition-cost="0" value="" >' . wp_kses_post( $placeholder_value ) . '</option>';
	} else {
		echo '<option data-addition-cost="0" value="" >' . esc_html__( 'Select your option', 'yayextra' ) . '</option>';
	}

	foreach ( $option_value_list as $index => $opt ) {
		$id_opt 	 = $data['id'] . (string) $index;
		$price       = ( ! empty( $opt['additionalCost'] ) && ! empty( $opt['additionalCost']['value'] ) ) ? floatval( $opt['additionalCost']['value'] ) : 0;
		$is_selected = boolval( $opt['isDefault'] );

		if ( $is_edit_option_mode && isset( $_GET['yaye_cart_item_key'] ) ) {
			$cart_content  = WC()->cart->cart_contents;
			$cart_item_key = sanitize_text_field( $_GET['yaye_cart_item_key'] );
			if ( ! empty( $cart_content[ $cart_item_key ] ) && ! empty( $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ] ) ) {
				$cart_values = $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ]['option_value'];

				foreach ( $cart_values as $cart_value ) {
					if ( trim($cart_value['option_val']) === trim($opt['value']) ) {
						$is_selected = true;
						break;
					} else {
						$is_selected = false;
					}
				}
			}
		} elseif ( ! $is_edit_option_mode &&
		  isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) &&
		  wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) &&
		  ! empty( $_POST['option_field_data'] )
		) { // Revert value if Add to cart fail.
			$opt_field_data = Utils::sanitize_array( $_POST['option_field_data'] );
			if ( ! empty( $opt_field_data[ $opt_set_id ][ $data['id'] ] ) ) {
				$opt_field_val = $opt_field_data[ $opt_set_id ][ $data['id'] ];
				if ( trim($opt_field_val) === trim($opt['value']) ) {
					  $is_selected = true;
				} else {
					$is_selected = false;
				}
			}
		}

		$addition_cost = 0;
		if ( ! empty( $opt['additionalCost'] ) && ! empty( $opt['additionalCost']['isEnabled'] ) ) {
			$cost_type = $opt['additionalCost']['costType']['value'];
			if ( 'fixed' === $cost_type ) {
				$addition_cost = Utils::get_price_from_yaycurrency( floatval( $opt['additionalCost']['value'] ) );
			} else {
				if ( isset( $params['product_price'] ) && is_numeric( $params['product_price'] ) ) {
					$addition_cost = floatval( $opt['additionalCost']['value'] ) * floatval( $params['product_price'] ) / 100;
				}
			}
		}

		if ( ! empty( $general_settings['show_additional_price'] ) && ! empty( $addition_cost ) ) {
			$label = $opt['value'] . ' ( + ' . wc_price( $addition_cost ) . ' )';

			if ( isset($cost_type) && 'percentage' === $cost_type ) {
				echo '<option class="option-addition-percentage-cost" data-opt-val-id="' . esc_attr( $id_opt ) . '" data-option-org-cost-token-replace="' . $addition_cost . '" data-option-org-cost="' . floatval( $opt['additionalCost']['value'] ) . '" data-addition-cost="' . esc_attr( $addition_cost ) . '" value="' . esc_attr( $opt['value'] ) . '"' . ( $is_selected ? 'selected' : '' ) . '>' . wp_kses_post( $label ) . '</option>';
			} else {
				echo '<option data-addition-cost="' . esc_attr( $addition_cost ) . '" value="' . esc_attr( $opt['value'] ) . '"' . ( $is_selected ? 'selected' : '' ) . '>' . wp_kses_post( $label ) . '</option>';
			}
			
		} else {
			$label = $opt['value'];
			echo '<option data-addition-cost="' . esc_attr( $addition_cost ) . '" value="' . esc_attr( $opt['value'] ) . '"' . ( $is_selected ? 'selected' : '' ) . '>' . wp_kses_post( $label ) . '</option>';
		}
	}
	echo '</select>';

	// Output addition description
	foreach ( $option_value_list as $index => $opt ) {
		if ( ! empty( $opt['additionalDescription'] ) && ! empty( $opt['additionalDescription']['isEnabled'] ) && ! empty( $opt['additionalDescription']['description'] ) ) {
			$addition_description = $opt['additionalDescription']['description'];
			echo '<p class="yayextra-addition-des yayextra-addition-des-dropdown" data-opt-id="' . esc_attr( $data['id'] ) . '" data-opt-val="' . esc_attr( $opt['value'] ) . '">' . wp_kses_post( $addition_description ) . '</p>';
		}
	}
	echo '</div>';
}
echo '</div>';

