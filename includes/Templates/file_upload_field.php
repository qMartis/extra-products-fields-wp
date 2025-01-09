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

$class_names = '';
if ( ! empty( $data['classNames'] ) ) {
	$class_names = Utils::convert_string( $data['classNames'], ',', ' ' );
}

$mine_types = Utils::get_mime_types();
$size_allow = size_format( wp_max_upload_size() );

// Output lable
echo '<div class="yayextra-option-field-wrap" data-option-field-id="' . esc_attr( $data['id'] ) . '" data-option-field-type="file_upload">';
Utils::get_template_part( $template_folder, 'label_field', array( 'data' => $data ) );
echo '<div>';
echo '<input type="file" class="' . esc_attr( $class_names ) . '" name="option_field_data[' . esc_attr( $opt_set_id ) . '][' . esc_attr( $data['id'] ) . ']" accept="' . esc_attr( implode( ',', $mine_types ) ) . '" />';
echo '<p class="yayextra-option-file_upload_des">' . sprintf( esc_html__( '( max file size % s )', 'yayextra' ), esc_attr( $size_allow ) ) . '</p>';
echo '</div>';
echo '</div>';



