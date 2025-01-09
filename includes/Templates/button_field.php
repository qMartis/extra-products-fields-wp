<?php


use YayExtra\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings                = $params['settings'];
$tooltip_possition_class = 'yayextra-tooltip-bottom';
if ( ! empty( $settings['globalStyle'] ) && ! empty( $settings['globalStyle']['button'] ) && ! empty( $settings['globalStyle']['button']['tooltip_position'] ) ) {
	$tooltip_possition_class = 'yayextra-tooltip-' . $settings['globalStyle']['button']['tooltip_position']['value'];
}

$template_folder     = YAYE_PATH . 'includes/Templates';
$general_settings    = $params['settings']['general'];
$data                = $params['data'];
$opt_set_id          = $params['opt_set_id'];
$is_edit_option_mode = $params['is_edit_option_mode'];
$option_value_list   = $data['optionValues'];
$is_required         = $data['isRequired'];

$class_names = '';
if ( ! empty( $data['classNames'] ) ) {
	$class_names = Utils::convert_string( $data['classNames'], ',', ' ' );
}

// Output lable.
echo '<div class="yayextra-option-field-wrap" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="button" data-multi-selectable="0">';
Utils::get_template_part( $template_folder, 'label_field', array( 'data' => $data ) );
echo '<div class="yayextra-opt-button-content">';
if ( ! empty( $option_value_list ) ) {

	// Check is checked - start
	$checked_arr    = array();
	$checked_defalt = '';
	foreach ( $option_value_list as $index => $opt ) {
		if ( $is_edit_option_mode && isset( $_GET['yaye_cart_item_key'] ) ) {
			$cart_content  = WC()->cart->cart_contents;
			$cart_item_key = sanitize_text_field( $_GET['yaye_cart_item_key'] );
			if ( ! empty( $cart_content[ $cart_item_key ] ) && ! empty( $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ] ) ) {
				$cart_values = $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ]['option_value'];
				foreach ( $cart_values as $cart_value ) {
					if ( trim($cart_value['option_val']) === trim($opt['value']) ) {
						array_push( $checked_arr, $opt['value'] );
						break;
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
				$opt_field_vals = $opt_field_data[ $opt_set_id ][ $data['id'] ];
				foreach ( $opt_field_vals as $opt_field_val ) {
					if ( trim($opt_field_val) === trim($opt['value']) ) {
						array_push( $checked_arr, $opt['value'] );
						break;
					}
				}
			}
		} else {
			if ( boolval( $opt['isDefault'] ) ) {
				$checked_defalt = $opt['value'];
			}
		}
	}

	$checked_results = array();
	if ( ! empty( $checked_arr ) ) {
		$checked_results = $checked_arr;
	} elseif ( empty( $checked_arr ) && ! empty( $checked_defalt ) ) {
		$checked_results = array( $checked_defalt );
	}
	// Check is checked - end

	foreach ( $option_value_list as $index => $opt ) {
		$id_opt        = $data['id'] . (string) $index;
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
			if ( isset($cost_type) && 'percentage' === $cost_type ) {
				$tooltip = '<strong class="option-addition-percentage-cost" data-opt-val-id="' . esc_attr( $id_opt ) . '" data-option-org-cost-token-replace="' . $addition_cost . '" data-option-org-cost="' . floatval( $opt['additionalCost']['value'] ) . '">' . $opt['value'] . ' ( + ' . wc_price( $addition_cost ) . ' )</strong>';
			} else {
				$tooltip = '<strong>' . $opt['value'] . ' ( + ' . wc_price( $addition_cost ) . ' )</strong>';
			}
		} else {
			$tooltip = '<strong>' . $opt['value'] . '</strong>';
		}

		echo '<div class="yayextra-opt-button yayextra-tooltip">';
		echo '<input id="' . esc_attr( $id_opt ) . '" name="option_field_data[' . esc_attr( $opt_set_id ) . '][' . esc_attr( $data['id'] ) . '][]" type="checkbox" data-opt-id="' . esc_attr( $data['id'] ) . '" data-addition-cost="' . esc_attr( $addition_cost ) . '" value="' . esc_attr( $opt['value'] ) . '"' . ( ! empty( $checked_results ) && in_array( $opt['value'], $checked_results, true ) ? 'checked' : '' ) . ' class="yayextra-option-button" style="display:none">';
		echo '<label class="yayextra-option-field-label yayextra-tooltip-wrap yayextra-option-button-label ' . esc_attr( $class_names ) . '">' . wp_kses_post( $opt['value'] ) . '</label>';
		echo '<span class="yayextra-tooltiptext ' . esc_attr( $tooltip_possition_class ) . '">' . wp_kses_post( $tooltip ) . '</span>';
		echo '</div>';
	}
}
echo '</div>';

// Output addition description.
foreach ( $option_value_list as $index => $opt ) {
	if ( ! empty( $opt['additionalDescription'] ) && ! empty( $opt['additionalDescription']['isEnabled'] ) && ! empty( $opt['additionalDescription']['description'] ) ) {
		$addition_description = $opt['additionalDescription']['description'];
		echo '<p class="yayextra-addition-des yayextra-addition-des-swatches-button yayextra-addition-des-button" data-opt-id="' . esc_attr( $data['id'] ) . '" data-opt-val="' . esc_attr( $opt['value'] ) . '">' . wp_kses_post( $addition_description ) . '</p>';
	}
}

echo '</div>';

