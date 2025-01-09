<?php
namespace YayExtra;

defined( 'ABSPATH' ) || exit;

class I18n {
	protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'loadPluginTextdomain' ) );
	}

	public function loadPluginTextdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}
		unload_textdomain( 'yayextra' );
		load_textdomain( 'yayextra', YAYE_PATH . '/i18n/languages/yayextra-' . $locale . '.mo' );
		load_plugin_textdomain( 'yayextra', false, YAYE_PATH . '/i18n/languages/' );
	}

	public static function getTranslation() {
		$translation_array = array(
			'general' => array(
				'choose_color'             => __( 'Choose color', 'yayextra' ),
				'something_went_wrong'     => __( 'Oops! Something went wrong!', 'yayextra' ),
				'option_value_set_default' => __( 'An option value have to set as default.', 'yayextra' ),
				'upload_image'             => __( 'Upload Image', 'yayextra' ),
				'save_changes'             => __( 'Save changes', 'yayextra' ),
			),
			'setting' => array(
				'show_option_sets_for_roles'     => __( 'Show option sets for roles', 'yayextra' ),
				'show_option_sets_for_roles_des' => __( 'Choose specific roles to show option sets. If have no role, option sets are always shown.', 'yayextra' ),
				'hide_option_sets_for_roles'     => __( 'Hide option sets for roles', 'yayextra' ),
				'upload_image'                   => __( 'Upload Image', 'yayextra' ),
				'save_changes'                   => __( 'Save changes', 'yayextra' ),
			),
		);
		return $translation_array;
	}

}
