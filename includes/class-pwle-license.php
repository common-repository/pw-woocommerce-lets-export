<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PWLE_License' ) ) :

final class PWLE_License {

	public $error = '';

	private $_license_url = 'http://pimwick.com';
	private $_license_secret = '';
	private $_license_product = '';
	private $_license_option_name = '';
	private $_premium;

	function __construct() {
		$this->_license_secret = '588ba467a728d3.17738635';
		$this->_license_product = 'PW WooCommerce Let\'s Export';
		$this->_license_option_name = 'pw-lets-export-license';
	}

	public function premium() {
		if ( !isset( $this->_premium ) ) {
			$this->_premium = $this->check_license( get_option( $this->_license_option_name, '' ) );
		}

		return $this->_premium;
	}

	public function activate_license( $license_key ) {
		if ( $this->license_action( $license_key, 'slm_activate' ) ) {
			$this->_premium = true;
			update_option( $this->_license_option_name, $license_key, false );
			return true;
		} else {
			return false;
		}
	}

	public function deactivate_license() {
		$license_key = get_option( $this->_license_option_name, '' );
		if ( $this->license_action( $license_key, 'slm_deactivate' ) ) {
			$this->_premium = false;
			update_option( $this->_license_option_name, '', false );
			return true;
		} else {
			return false;
		}
	}

	private function check_license( $license_key ) {
		if ( empty( $license_key ) ) {
			return false;
		} else {
			return $this->license_action( $license_key, 'slm_check' );
		}
	}

	private function license_action( $license_key, $action ) {
		if ( empty( $license_key ) ) {
			return false;
		}

		$api_params = array(
			'slm_action' => $action,
			'secret_key' => $this->_license_secret,
			'license_key' => $license_key,
			'registered_domain' => $_SERVER['SERVER_NAME'],
			'item_reference' => urlencode( $this->_license_product ),
		);

		$query = esc_url_raw( add_query_arg( $api_params, $this->_license_url ) );
		$response = wp_remote_get( $query, array( 'timeout' => 20, 'sslverify' => true ) );
		$this->error = '';

		if ( !is_wp_error( $response ) ) {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $license_data->result == 'success' ) {
				if ( $license_data->status != 'expired' && $license_data->status != 'blocked' ) {
					return true;
				} else {
					$this->error = 'License is ' . $license_data->status;
				}
			} else if ( false !== strpos( $license_data->message, 'License key already in use on' ) ) {
				return true;
			} else {
				$this->error = 'Error: ' . $license_data->message;
			}
		} else {
			$this->error = 'Error while validating license: ' . $response->get_error_message();
		}

		return false;
	}
}

endif;

?>