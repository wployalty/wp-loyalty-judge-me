<?php

namespace Wljm\App\Helpers;

use Wljm\App\Models\Users;

class Woocommerce {
	public static $instance = null;
	protected static $options = array();
	protected static $banned_user = array();
	public static function hasAdminPrivilege() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function create_nonce( $action = - 1 ) {
		return wp_create_nonce( $action );
	}

	public static function verify_nonce( $nonce, $action = - 1 ) {
		if ( wp_verify_nonce( $nonce, $action ) ) {
			return true;
		} else {
			return false;
		}
	}

	function _log( $message ) {
		$options    = $this->getOptions( 'wlr_settings' );
		$debug_mode = is_array( $options ) && isset( $options['debug_mode'] ) && ! empty( $options['debug_mode'] ) ? $options['debug_mode'] : 'no';
		if ( $debug_mode == 'yes' && class_exists( 'WC_Logger' ) ) {
			$logger = new \WC_Logger();
			if ( $this->isMethodExists( $logger, 'add' ) ) {
				$logger->add( 'Loyalty', $message );
			}
		}
	}

	function isMethodExists( $object, $method_name ) {
		if ( is_object( $object ) && method_exists( $object, $method_name ) ) {
			return true;
		}

		return false;
	}

	function getOrder( $order = null ) {
		if ( isset( $order ) && is_object( $order ) ) {
			return $order;
		}
		if ( isset( $order ) && is_integer( $order ) && function_exists( 'wc_get_order' ) ) {
			return wc_get_order( $order );
		}

		return null;
	}

	function getOptions( $key = '', $default = '' ) {
		if ( empty( $key ) ) {
			return array();
		}
		if ( ! isset( self::$options[ $key ] ) || empty( self::$options[ $key ] ) ) {
			self::$options[ $key ] = get_option( $key, $default );
		}

		return self::$options[ $key ];
	}

	public static function getInstance( array $config = array() ) {
		if ( ! self::$instance ) {
			self::$instance = new self( $config );
		}

		return self::$instance;
	}

	function getActionTypes() {
		$earn_helper  = EarnCampaign::getInstance();
		$action_types = array(
			'point_for_purchase' => is_admin() ? __( 'Points For Purchase', 'wp-loyalty-rules' ) : sprintf( __( '%s For Purchase', 'wp-loyalty-rules' ), $earn_helper->getPointLabel( 3 ) ),
		);
		return apply_filters( 'wlr_action_types', $action_types );
	}

	function get_login_user_email() {
		$user       = get_user_by( 'id', get_current_user_id() );
		$user_email = '';
		if ( ! empty( $user ) ) {
			$user_email = $user->user_email;
		}

		return $user_email;
	}

	function isJson( $string ) {
		json_decode( $string );

		return ( json_last_error() == JSON_ERROR_NONE );
	}

	function isBannedUser( $user_email = "" ) {
		if ( empty( $user_email ) ) {
			$user_email = $this->get_login_user_email();
			if ( empty( $user_email ) ) {
				return false;
			}
		}
		if ( isset( static::$banned_user[ $user_email ] ) ) {
			return static::$banned_user[ $user_email ];
		}
		$user_modal = new Users();
		global $wpdb;
		$where = $wpdb->prepare( "user_email = %s AND is_banned_user = %d ", array( $user_email, 1 ) );
		$user  = $user_modal->getWhere( $where, "*", true );

		return static::$banned_user[ $user_email ] = ( ! empty( $user ) && is_object( $user ) && isset( $user->is_banned_user ) );
	}
}