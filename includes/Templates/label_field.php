<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$data        = $params['data'];
$is_required = $data['isRequired'];
// $style_required = ! empty( $is_required ) ? 'color:red!important' : '';
$required_text = ! empty( $is_required ) ? '<span style="color: red;"> *</span>' : '';

if ( 'span' === $data['labelType']['value'] ) {
	echo '<div class="yayextra-option-field-name"><span>' . wp_kses_post( $data['name'] ) . '</span>' . wp_kses_post( $required_text ) . '</div>';
} elseif ( 'div' === $data['labelType']['value'] ) {
	echo '<div class="yayextra-option-field-name">' . wp_kses_post( $data['name'] ) . wp_kses_post( $required_text ) . '</div>';
}

