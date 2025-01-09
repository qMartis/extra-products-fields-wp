<?php


use YayExtra\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_folder     = YAYE_PATH . 'includes/Templates';
$data                = $params['data'];
$opt_set_id          = $params['opt_set_id'];
$is_edit_option_mode = $params['is_edit_option_mode'];
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

// Set before value into field after validate.
$value = '';
if ( isset( $_REQUEST['yayextra-opt-field-data-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['yayextra-opt-field-data-nonce'] ), 'yayextra-opt-field-data-check-nonce' ) ) {
	if ( isset( $_POST['option_field_data'] ) ) {
		if ( ! empty( $_POST['option_field_data'][ $opt_set_id ][ $data['id'] ] ) ) {
			$value = sanitize_text_field( wp_unslash($_POST['option_field_data'][ $opt_set_id ][ $data['id'] ] ));
		}
	}
}

if ( $is_edit_option_mode && isset( $_GET['yaye_cart_item_key'] ) ) {
	$cart_content  = WC()->cart->cart_contents;
	$cart_item_key = sanitize_text_field( $_GET['yaye_cart_item_key'] );
	if ( ! empty( $cart_content[ $cart_item_key ] ) && ! empty( $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ] ) ) {
		$value = $cart_content[ $cart_item_key ]['yaye_custom_option'][ $opt_set_id ][ $data['id'] ]['option_value'];
	}
}

$placeholder = ! empty( $data['placeHolder'] ) ? $data['placeHolder'] : '';
$minlength   = ! empty( $data['minCharacter'] ) ? (int) $data['minCharacter'] : null;
$maxlength   = ! empty( $data['maxCharacter'] ) ? (int) $data['maxCharacter'] : null;
$text_format = ! empty( $data['textFormat'] ) ? $data['textFormat']['value'] : 'normal';

// Output lable
echo '<div class="yayextra-option-field-wrap" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="text">';
Utils::get_template_part( $template_folder, 'label_field', array( 'data' => $data ) );
echo '<div>';
echo '<input type="text" placeholder="' . esc_attr( $placeholder ) . '" minlength="' . esc_attr( $minlength ) . '" maxlength="' . esc_attr( $maxlength ) . '" data-text-format="' . esc_attr( $text_format ) . '" class="yayextra-text ' . esc_attr( $class_names ) . '" name="option_field_data[' . esc_attr( $opt_set_id ) . '][' . esc_attr( $data['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
echo '<div class="error-message-text" style="display:none"></div>';
echo '</div>';
echo '</div>';

