<?php
namespace YayExtra\Init;

use YayExtra\Helper\Utils;
use YayExtra\Init\CustomPostType;
use YayExtra\Classes\ProductPage;
/**
 * Ajax class
 */
class Ajax {

	/**
	 * Add actions for init class.
	 */

	public function __construct() {
		$this->add_ajax_event();
	}

	/**
	 * Get no-private events.
	 *
	 * @return array
	 */
	public function define_noprivate_events() {
		return array(
			// 'handle_image_upload',
			// 'handle_image_swatches_upload',
		);
	}

	/**
	 * Get private events.
	 *
	 * @return array
	 */
	public function define_private_events() {
		return array(
			'get_option_sets',
			'get_option_set',
			'add_new_option_set',
			'import_option_sets',
			'duplicate_option_set',
			'change_option_set_status',
			'delete_option_set',
			'delete_option_sets',
			'save_option_set',
			'get_product_list',
			'get_product_category_list',
			'get_product_tag_list',
			'filter_product_meta',
			// 'handle_image_upload',
			// 'handle_image_swatches_upload',
			'save_settings',
			'get_settings',
			'update_option_set_products_one_by_one',
		);
	}

	/**
	 * Add wp ajax events.
	 *
	 * @return void
	 */
	public function add_ajax_event() {

		// no-private events.
		$noprivate_events = $this->define_noprivate_events();
		foreach ( $noprivate_events as $event ) {
			add_action( 'wp_ajax_nopriv_yaye_' . $event, array( $this, $event ) );
		}

		// private events.
		$private_events = $this->define_private_events();
		foreach ( $private_events as $event ) {
			add_action( 'wp_ajax_yaye_' . $event, array( $this, $event ) );
		}
	}

	/**
	 * Ajax get option sets with pagination.
	 *
	 * @throws \Exception Exception when get data.
	 *
	 * @return void
	 */
	public function get_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$pagination = sanitize_text_field( wp_unslash( isset( $_POST['params'] ) ? $_POST['params'] : "" ) );
			$pagination = json_decode( $pagination, true );

			$data            = CustomPostType::get_list_option_set( $pagination );
			$list_option_set = $data['option_set_list'];
			wp_send_json_success(
				array(
					'list_option_set' => $list_option_set,
					'current_page'    => $data['current_page'],
					'total_items'     => $data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get option set by id.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id         = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$option_set = CustomPostType::get_option_set( $id );

			$filters = get_post_meta( $id, '_yaye_products', true );

			if ( 1 === $filters['product_filter_type'] ) {
				$params = array(
					'option_set_id' => $id,
					'current'       => 1,
					'page_size'     => 10,
					'product_type'  => 'all',
				);
				$data   = Utils::get_products_match( null, null, $params );
			} else {
				$params    = array(
					'current'   => 1,
					'page_size' => 10,
				);
				$apply     = $filters['product_filter_by_conditions']['match_type'];
				$condition = $filters['product_filter_by_conditions']['conditions'];
				$data      = Utils::get_products_match( $condition, $apply, $params );
			}

			wp_send_json_success(
				array(
					'option_set'     => $option_set,
					'total_products' => $data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax add new option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function add_new_option_set() {
		try {
			Utils::check_nonce();
			$option_set_id = CustomPostType::create_new_option_set();
			wp_send_json_success( array( 'option_set_id' => $option_set_id ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax duplicate option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function duplicate_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id            = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$option_set_id = CustomPostType::duplicate_option_set( $id );
			if ( ! empty( $option_set_id ) ) {
				wp_send_json_success( array( 'option_set_id' => $option_set_id ), 200 );
			} else {
				wp_send_json_error( array( 'msg' => __( 'Duplicate option set failed.', 'yayextra' ) ) );
			}
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax Import option sets.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function import_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$files = $_FILES;

			/**
			 * Check do not have JSON file.
			 */
			if ( empty( $files ) ) {
				wp_send_json_error( array( 'msg' => __( 'Import at least 1 JSON file', 'yayextra' ) ) );
			}

			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$count = 0;

			foreach ( $files as $file ) {

				/**
				 * If file type is not JSON then skip
				 */
				if ( 'application/json' !== $file['type'] ) {
					continue;
				}

				if ( empty( $file['tmp_name'] ) ) {
					continue;
				}

				/**
				 * Get file content
				 */
				$file_tmp_name = sanitize_text_field( $file['tmp_name'] );
				$file_content  = $wp_filesystem->get_contents( $file_tmp_name );
				$data          = json_decode( $file_content, true );
				$data          = $data['optionSets'];

				/**
				 * Get valid data from file
				 */
				// $data = Utils::check_valid_option_set_data( $data );

				if ( empty( $data ) ) {
					continue;
				}

				foreach ( $data as $option_set ) {
					/**
				 * Create option set from data
				 */
					$option_set_id = CustomPostType::create_option_set_from_data( $option_set );

					if ( ! empty( $option_set_id ) ) {
						$count ++;
					}
				}
			}

			wp_send_json_success( array( 'count' => $count ), 200 );

		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax change option set status.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function change_option_set_status() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id    = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			$value = sanitize_text_field( wp_unslash( isset( $_POST['value'] ) ? $_POST['value'] : null ) );
			update_post_meta( $id, '_yaye_status', $value );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax delete option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function delete_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$id = sanitize_text_field( wp_unslash( isset( $_POST['id'] ) ? $_POST['id'] : null ) );
			wp_delete_post( $id );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax delete option sets.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function delete_option_sets() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$ids = sanitize_text_field( wp_unslash( isset( $_POST['ids'] ) ? $_POST['ids'] : "" ) );
			$ids = json_decode( $ids, true );
			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					wp_delete_post( $id );
				}
			}
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax save option set.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function save_option_set() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$option_set = isset( $_POST['option_set'] ) ? sanitize_text_field( wp_unslash( $_POST['option_set'] ) ) : "";
			$option_set = json_decode( $option_set, true );

			$id = $option_set['id'];
			update_post_meta( $id, '_yaye_name', $option_set['name'] );
			update_post_meta( $id, '_yaye_description', $option_set['description'] );
			update_post_meta( $id, '_yaye_status', $option_set['status'] );
			update_post_meta( $id, '_yaye_options', $option_set['options'] );
			update_post_meta( $id, '_yaye_actions', $option_set['actions'] );
			update_post_meta( $id, '_yaye_products', $option_set['products'] );
			update_post_meta( $id, '_yaye_custom_css', $option_set['custom_css'] );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list category.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_category_list() {
		try {
			Utils::check_nonce();
			$result = Utils::get_product_categories();
			wp_send_json_success( array( 'product_category_list' => $result ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list product tag.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_tag_list() {
		try {
			Utils::check_nonce();
			$result = Utils::get_product_tags();
			wp_send_json_success( array( 'product_tag_list' => $result ), 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product list product tag.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_product_list() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$condition = sanitize_text_field( wp_unslash( isset( $_POST['condition'] ) ? $_POST['condition'] : "" ) );
			$condition = json_decode( $condition, true );
			$apply     = sanitize_text_field( wp_unslash( isset( $_POST['apply'] ) ? $_POST['apply'] : "" ) );
			$apply     = json_decode( $apply, true );

			$params = sanitize_text_field( wp_unslash( isset( $_POST['params'] ) ? $_POST['params'] : "" ) );
			$params = json_decode( $params, true );

			$option_set_id = $params['optionSetId'];

			$response_data = Utils::get_products_match( $condition, $apply, $params );

			wp_send_json_success(
				array(
					'list_product' => $response_data['product_list'],
					'current_page' => $response_data['current_page'],
					'total_items'  => $response_data['total_items'],
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get product by meta.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function filter_product_meta() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}
			$filter        = sanitize_text_field( wp_unslash( isset( $_POST['filter'] ) ? $_POST['filter'] : "" ) );
			$filter        = json_decode( $filter, true );
			$response_data = Utils::filter_product_meta( $filter );
			wp_send_json_success( $response_data, 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax get settings.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function get_settings() {
		try {
			Utils::check_nonce();
			$data = Utils::get_settings();
			wp_send_json_success(
				array(
					'settings' => $data,
				),
				200
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	/**
	 * Ajax save settings.
	 *
	 * @throws \Exception Exception when check nonce.
	 *
	 * @return void
	 */
	public function save_settings() {
		try {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'yaye_nonce' ) ) {
				throw new \Exception( __( 'Nonce is invalid', 'yayextra' ) );
			}

			$settings = ! empty( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : "";

			Utils::update_settings( json_decode( $settings, true ) );
			wp_send_json_success( 'success', 200 );
		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}

	public function update_option_set_products_one_by_one() {
		try {
			Utils::check_nonce();
			$object_data                     = ! empty( $_REQUEST['objectData'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['objectData'] ) ) : "";
			$decoded_object_data             = json_decode( $object_data, true );
			$yaye_product_post_meta          = get_post_meta( $decoded_object_data['optionSetID'], '_yaye_products' );
			$array_product_filter_one_by_one = $yaye_product_post_meta[0]['product_filter_one_by_one'];

			// Remove the assigned product if it doesn't exist
			$allow_check_products_is_exist = apply_filters( 'yayextra_allow_check_products_is_exist', false );
			if ( $allow_check_products_is_exist ) {
				foreach ( $array_product_filter_one_by_one as $idx => $prod_id ) {
					$product = wc_get_product( $prod_id );
					if ( $product ) {
						$product_status = $product->get_status();
						if ( 'publish' !== $product_status ) {
							unset( $array_product_filter_one_by_one[ $idx ] );
						}
					} else {
						unset( $array_product_filter_one_by_one[ $idx ] );
					}
				}				
			}
			
			if ( 'assign' === $decoded_object_data['type'] ) {
				$merged_array = array_merge( $decoded_object_data['productIdCheckedList'], $array_product_filter_one_by_one );
				$yaye_product_post_meta[0]['product_filter_one_by_one'] = $merged_array;
			} else {
				$filtered_array = array_values( array_diff( $array_product_filter_one_by_one, $decoded_object_data['productIdCheckedList'] ) );
				$yaye_product_post_meta[0]['product_filter_one_by_one'] = $filtered_array;
			}

			update_post_meta( $decoded_object_data['optionSetID'], '_yaye_products', $yaye_product_post_meta[0] );
			wp_send_json_success( 'success', 200 );

		} catch ( \Exception $ex ) {
			wp_send_json_error( array( 'msg' => $ex->getMessage() ) );
		} catch ( \Error $err ) {
			wp_send_json_error( array( 'msg' => $err->getMessage() ) );
		}
	}
}
