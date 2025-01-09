<?php

namespace YayExtra\Init;

use YayExtra\Helper\Utils;

defined( 'ABSPATH' ) || exit;
/**
 * Plugin activate/deactivate logic
 */
class CustomPostType {

	/**
	 * Add actions for init custom post type.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * Register custom post type
	 */
	public static function register_post_type() {
		$labels       = array(
			'name'               => __( 'Option Set', 'yayextra' ),
			'singular_name'      => __( 'Option Set', 'yayextra' ),
			'add_new'            => __( 'Add New Option Set', 'yayextra' ),
			'add_new_item'       => __( 'Add a new Option Set', 'yayextra' ),
			'edit_item'          => __( 'Edit Option Set', 'yayextra' ),
			'new_item'           => __( 'New Option Set', 'yayextra' ),
			'view_item'          => __( 'View Option Set', 'yayextra' ),
			'search_items'       => __( 'Search Option Set', 'yayextra' ),
			'not_found'          => __( 'No Option Set found', 'yayextra' ),
			'not_found_in_trash' => __( 'No Option Set currently trashed', 'yayextra' ),
			'parent_item_colon'  => '',
		);
		$capabilities = array();
		$args         = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => false,
			'query_var'           => true,
			'rewrite'             => true,
			'capability_type'     => 'yaye_option_set',
			'capabilities'        => $capabilities,
			'hierarchical'        => false,
			'menu_position'       => null,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'supports'            => array( 'title', 'author', 'thumbnail' ),
		);
		register_post_type( 'yaye_option_set', $args );
	}

	/**
	 * Get list option set with pagination or get all without pagination.
	 *
	 * @param array   $pagination Pagination information.
	 * @param boolean $force_all  Allow get all option sets.
	 *
	 * @return array
	 */
	public static function get_list_option_set( $pagination = array(), $force_all = false ) {
		global $wpdb;

		$result_query_all = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}posts p WHERE p.post_type in (%s) AND p.post_status in (%s)",
				'yaye_option_set',
				'publish'
			)
		);

		if ( $force_all ) {
			return $result_query_all;
		}

		$total_items = count( $result_query_all );
		$limit       = ! empty( $pagination['page_size'] ) && is_numeric( $pagination['page_size'] ) ? (int) $pagination['page_size'] : 10;
		$page        = ! empty( $pagination['current'] ) && is_numeric( $pagination['current'] ) ? (int) $pagination['current'] : 1;
		$offset      = ( $page - 1 ) * $limit;

		$query_result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}posts p WHERE (p.post_type in (%s) AND p.post_status in (%s)) ORDER BY p.post_date DESC LIMIT %d OFFSET %d",
				'yaye_option_set',
				'publish',
				$limit,
				$offset
			)
		);

		$option_set_list = array();
		if ( ! empty( $query_result ) ) {
			foreach ( $query_result as $post ) {
				$option_set_id     = $post->ID;
				$option_set_list[] = array(
					'id'          => $option_set_id,
					'name'        => get_post_meta( $option_set_id, '_yaye_name', true ),
					'description' => get_post_meta( $option_set_id, '_yaye_description', true ),
					'status'      => get_post_meta( $option_set_id, '_yaye_status', true ),
					'options'     => get_post_meta( $option_set_id, '_yaye_options', true ),
					'actions'     => get_post_meta( $option_set_id, '_yaye_actions', true ),
					'products'    => get_post_meta( $option_set_id, '_yaye_products', true ),
					'custom_css'  => get_post_meta( $option_set_id, '_yaye_custom_css', true ),
				);
			}
		}

		$result = array(
			'option_set_list' => $option_set_list,
			'current_page'    => $page,
			'total_items'     => $total_items,
		);

		return $result;
	}

	/**
	 * Get option set by id
	 *
	 * @param int $option_set_id Option set id.
	 *
	 * @return array
	 */
	public static function get_option_set( $option_set_id ) {

		$name        = get_post_meta( $option_set_id, '_yaye_name', true );
		$description = get_post_meta( $option_set_id, '_yaye_description', true );
		$status      = get_post_meta( $option_set_id, '_yaye_status', true );
		$options     = get_post_meta( $option_set_id, '_yaye_options', true );
		$actions     = get_post_meta( $option_set_id, '_yaye_actions', true );
		$products    = get_post_meta( $option_set_id, '_yaye_products', true );
		$custom_css  = get_post_meta( $option_set_id, '_yaye_custom_css', true );
		$result      = array(
			'id'          => $option_set_id,
			'name'        => $name ? $name : '',
			'description' => $description ? $description : '',
			'status'      => $status ? $status : 0,
			'options'     => $options ? $options : array(),
			'actions'     => $actions ? $actions : array(),
			'products'    => $products ? $products : array(),
			'custom_css'  => $custom_css ? $custom_css : '',
		);
		return $result;

	}

	/**
	 * Get option in option set by option_id.
	 *
	 * @param int $option_set_id Option set id.
	 * @param int $option_id     Option id.
	 *
	 * @return array
	 */
	public static function get_option( $option_set_id, $option_id ) {
		$options = get_post_meta( $option_set_id, '_yaye_options', true );
		if ( ! empty( $options ) ) {
			foreach ( $options as $option ) {
				if ( $option_id === $option['id'] ) {
					return $option;
				}
			}
		}
		return array();
	}

	/**
	 * Get list option set by list ids.
	 *
	 * @param int $option_set_ids List option set id.
	 *
	 * @return array
	 */
	public static function get_option_set_array( $option_set_ids = array() ) {
		$result = array();

		if ( ! empty( $option_set_ids ) ) {
			foreach ( $option_set_ids as $option_set_id ) {
				$id = (int) $option_set_id;

				$actions = get_post_meta( $id, '_yaye_actions', true );
				foreach ( $actions as $idx_action => $action ) {
					if ( ! empty( $action['subActions'] ) ) {
						foreach ( $action['subActions'] as $idx_subaction => $sub_action ) {
							$action_val = ! empty( $sub_action['subActionValue'] ) ? $sub_action['subActionValue'] : 0;
							$sub_action_val = Utils::get_price_from_yaycurrency( $action_val );
							$actions[ $idx_action ]['subActions'][ $idx_subaction ]['subActionValueYayCurrency'] = $sub_action_val;
						}
					}
				}

				$result[] = array(
					'id'          => $id,
					'name'        => get_post_meta( $id, '_yaye_name', true ),
					'description' => get_post_meta( $id, '_yaye_description', true ),
					'status'      => get_post_meta( $id, '_yaye_status', true ),
					'options'     => get_post_meta( $id, '_yaye_options', true ),
					'actions'     => $actions,
					'products'    => get_post_meta( $id, '_yaye_products', true ),
					'custom_css'  => get_post_meta( $id, '_yaye_custom_css', true ),
				);
			}
		}

		return $result;
	}

	/**
	 * Create new option set.
	 *
	 * @return int New option set id.
	 */
	public static function create_new_option_set() {
			$args      = array(
				'post_content' => '',
				// 'post_date'     => current_time( 'Y-m-d H:i:s' ),
				// 'post_date_gmt' => current_time( 'Y-m-d H:i:s' ),
				'post_type'    => 'yaye_option_set',
				'post_title'   => 'YayExtra Option Set',
				'post_status'  => 'publish',
			);
			$insert_id = wp_insert_post( $args );

			update_post_meta( $insert_id, '_yaye_name', 'Sample Option Set' );
			update_post_meta( $insert_id, '_yaye_description', 'Sample description' );
			update_post_meta( $insert_id, '_yaye_status', 0 );
			update_post_meta( $insert_id, '_yaye_options', array() );
			update_post_meta( $insert_id, '_yaye_actions', array() );
			update_post_meta(
				$insert_id,
				'_yaye_products',
				array(
					'product_filter_type'          => 1, // 1 : one by one (default), 2 : by conditions,
					'product_filter_one_by_one'    => array(),
					'product_filter_by_conditions' => array(
						'match_type' => array(
							'label' => 'Any',
							'value' => 'any',
						),
						'conditions' => array(),
					),
				)
			);
			update_post_meta( $insert_id, '_yaye_custom_css', '' );

			return $insert_id;
	}

	/**
	 * Duplicate option set.
	 *
	 * @param int $id Id of option set need to be duplicated.
	 *
	 * @return int New option set id.
	 */
	public static function duplicate_option_set( $id ) {
		if ( ! empty( $id ) ) {
			$args      = array(
				'post_content' => '',
				'post_type'    => 'yaye_option_set',
				'post_title'   => 'YayExtra Option Set',
				'post_status'  => 'publish',
			);
			$insert_id = wp_insert_post( $args );

			$option_set_org = self::get_option_set( (int) $id );
			$option_list    = $option_set_org['options'];
			$action_list    = $option_set_org['actions'];
			foreach($option_list as $key => $option) {
				$option_id_old = $option['id'];
				$option_id_new = Utils::gen_uuid();
				// New option id
				$option_list[$key]['id'] = $option_id_new;

				// Replace by New option id of Option Logics
				$option_list_clone = $option_list;
				foreach($option_list_clone as $key_clone => $option_clone) { 
					if ( $option_clone['id'] !== $option_id_new ) { // must be different the new option id
						$option_clone_logics = $option_clone['logics'];
						foreach($option_clone_logics as $logic_clone_key => $opt_clone_logic) {
							if( ! empty( $opt_clone_logic ) && ! empty ( $opt_clone_logic['option'] ) ) {
								if( $opt_clone_logic['option']['id'] === $option_id_old) { 
									$option_list[$key_clone]['logics'][$logic_clone_key]['option']['id'] = $option_id_new;
									$option_list[$key_clone]['logics'][$logic_clone_key]['option']['value'] = $option_id_new;
								}
							}
						}
					}
				}
			
				// Replace by New option id of Action Logics
				foreach($action_list as $action_key => $action) {
					$action_id_new = Utils::gen_uuid();
					// New option id
					$action_list[$action_key]['id'] = $action_id_new;
					if ( ! empty( $action['conditions'] ) ) {
						$action_conditions = $action['conditions'];
						foreach($action_conditions as $action_condition_key => $action_condition) {
							if( ! empty( $action_condition ) && ! empty ( $action_condition['optionId'] )) {
								if( $action_condition['optionId']['id'] === $option_id_old) {
									$action_list[$action_key]['conditions'][$action_condition_key]['optionId']['id']    = $option_id_new;
									$action_list[$action_key]['conditions'][$action_condition_key]['optionId']['value'] = $option_id_new;
								}
							}
						}
					}
			
				}
			}

			update_post_meta( $insert_id, '_yaye_name', $option_set_org['name'] );
			update_post_meta( $insert_id, '_yaye_description', $option_set_org['description'] );
			update_post_meta( $insert_id, '_yaye_status', $option_set_org['status'] );
			update_post_meta( $insert_id, '_yaye_options', $option_list );
			update_post_meta( $insert_id, '_yaye_actions', $action_list );
			update_post_meta( $insert_id, '_yaye_products', $option_set_org['products'] );
			update_post_meta( $insert_id, '_yaye_custom_css', $option_set_org['custom_css'] );

			return $insert_id;
		} else {
			return null;
		}
	}
	/**
	 * Create option set from data.
	 *
	 * @param int $data Data.
	 *
	 * @return int New option set id.
	 */
	public static function create_option_set_from_data( $data ) {
		if ( empty( $data ) ) {
			return null;
		}

		$args      = array(
			'post_content' => '',
			'post_type'    => 'yaye_option_set',
			'post_title'   => 'YayExtra Option Set',
			'post_status'  => 'publish',
		);
		$insert_id = wp_insert_post( $args );

		update_post_meta( $insert_id, '_yaye_name', $data['name'] );
		update_post_meta( $insert_id, '_yaye_description', $data['description'] );
		update_post_meta( $insert_id, '_yaye_status', $data['status'] );
		update_post_meta( $insert_id, '_yaye_options', $data['options'] );
		update_post_meta( $insert_id, '_yaye_actions', $data['actions'] );
		update_post_meta( $insert_id, '_yaye_products', $data['products'] );
		update_post_meta( $insert_id, '_yaye_custom_css', $data['custom_css'] );

		return $insert_id;
	}
}
