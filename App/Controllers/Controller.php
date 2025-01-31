<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wljm\App\Controllers;

use Wljm\App\Helpers\Input;
use Wljm\App\Helpers\Woocommerce as WC;
use Wlr\App\Helpers\EarnCampaign;

defined( 'ABSPATH' ) or die;

class Controller {
	/**
	 * Add plugin menu.
	 *
	 * @return void
	 */
	public static function addMenu() {
		if ( WC::hasAdminPrivilege() ) {
			add_menu_page( WLJM_PLUGIN_NAME, WLJM_PLUGIN_NAME, 'manage_woocommerce', WLJM_PLUGIN_SLUG, [
				self::class,
				'displayMainPage'
			], 'dashicons-megaphone', 57 );
		}
	}

	/**
	 * Main page.
	 *
	 * @return void
	 */
	public static function displayMainPage() {
		if ( ! WC::hasAdminPrivilege() ) {
			wp_die( esc_html( __( "Don't have access permission", 'wp-loyalty-judge-me' ) ) );
		}
		//it will automatically add new table column,via auto generate alter query
		if ( Input::get( 'page' ) != WLJM_PLUGIN_SLUG ) {
			return;
		}
		$path             = WLJM_PLUGIN_PATH . 'App/Views/Admin/main.php';
		$review_keys      = self::getReviewKeys();
		$webhooks         = self::getWebHooks();
		$main_page_params = [
			'webhook_list'     => $webhooks,
			'review_keys'      => $review_keys,
			'setting_nonce'    => wp_create_nonce( 'wljm-setting-nonce' ),
			//'settings' => get_option('wljm_settings', array()),
			'back_to_apps_url' => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
		];
		WC::renderTemplate( $path, $main_page_params );
	}

	/**
	 * Get review keys.
	 *
	 * @return array
	 */
	public static function getReviewKeys() {
		return [
			'review/created',
			'review/updated'
		];
	}

	/**
	 * Get webhooks.
	 *
	 * @return array
	 */
	public static function getWebHooks() {
		$domain         = constant( 'JGM_SHOP_DOMAIN' );
		$token          = get_option( 'judgeme_shop_token' );
		$api_url        = 'https://judge.me/api/v1/';
		$url            = $api_url . 'webhooks';
		$webhook_params = [
			'api_token'   => $token,
			'shop_domain' => $domain,
		];
		$response       = wp_remote_get( $url, [
			'body' => $webhook_params
		] );
		$return         = [];
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$logger        = wc_get_logger();
			$logger->add( 'WPLoyalty', json_encode( $error_message ) );

		} else {
			$review_keys = self::getReviewKeys();
			$body        = json_decode( $response['body'] );
			if ( is_object( $body ) && ! empty( $body->webhooks ) ) {
				foreach ( $body->webhooks as $webhook ) {
					if ( in_array( $webhook->key, $review_keys ) && ! isset( $return[ $webhook->key ] ) && in_array( $webhook->url, [
							self::getDomainUrl() . '/wp-json/wployalty/judgeme/v1/review/created',
							self::getDomainUrl() . '/wp-json/wployalty/judgeme/v1/review/updated'
						] ) ) {
						$return[ $webhook->key ] = $webhook;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Get domain url.
	 *
	 * @return string
	 */
	public static function getDomainUrl() {
		$domain      = 'https://';
		$domain_name = constant( 'JGM_SHOP_DOMAIN' );

		return apply_filters( 'wljm_domain_url', trim( $domain . $domain_name, '/' ) );
	}

	/**
	 * Enqueue admin js and css.
	 *
	 * @return void
	 */
	public static function adminScripts() {
		if ( ! WC::hasAdminPrivilege() ) {
			return;
		}

		if ( Input::get( 'page' ) != WLJM_PLUGIN_SLUG ) {
			return;
		}

		remove_all_actions( 'admin_notices' );

		wp_enqueue_style( WLJM_PLUGIN_SLUG . '-wljm-admin', WLJM_PLUGIN_URL . 'Assets/Admin/Css/wljm-admin.css', [], WLJM_PLUGIN_VERSION . '&t=' . time() );
		wp_enqueue_script( WLJM_PLUGIN_SLUG . '-wljm-admin', WLJM_PLUGIN_URL . 'Assets/Admin/Js/wljm-admin.js', [], WLJM_PLUGIN_VERSION . '&t=' . time() );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify.min.css', [], WLR_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify.min.js', [], WLR_PLUGIN_VERSION . '&t=' . time() );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-wlr-font', WLR_PLUGIN_URL . 'Assets/Site/Css/wlr-fonts.min.css', [], WLR_PLUGIN_VERSION );
		$localize = [
			'home_url'              => get_home_url(),
			'admin_url'             => admin_url(),
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'delete_nonce'          => wp_create_nonce( 'wljm_delete_nonce' ),
			'create_nonce'          => wp_create_nonce( 'wljm_create_nonce' ),
			'deleting_button_label' => __( 'Deleting...', 'wp-loyalty-judge-me' ),
			'delete_button_label'   => __( 'Delete', 'wp-loyalty-judge-me' ),
			'creating_button_label' => __( 'Creating...', 'wp-loyalty-judge-me' ),
			'create_button_label'   => __( 'Create', 'wp-loyalty-judge-me' ),
			'confirm_label'         => __( 'Are you sure?', 'wp-loyalty-judge-me' ),
			'saving_button_label'   => __( 'Saving...', 'wp-loyalty-judge-me' ),
			'saved_button_label'    => __( 'Save', 'wp-loyalty-judge-me' ),
		];
		wp_localize_script( WLJM_PLUGIN_SLUG . '-wljm-admin', 'wljm_localize_data', $localize );
	}

	/**
	 * Hide plugin menu.
	 *
	 * @return void
	 */
	public static function hideMenu() {
		?>
        <style>
            #toplevel_page_wp-loyalty-judge-me {
                display: none !important;
            }
        </style>
		<?php
	}

	/**
	 * Delete Webhook.
	 *
	 * @return void
	 */
	public static function deleteWebHook() {
		if ( ! WC::isBasicSecurityValid( 'wljm_delete_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic validation failed', 'wp-loyalty-judge-me' ) ] );
		}
		$review_keys = self::getReviewKeys();
		$webhook_key = (string) Input::get( 'webhook_key' );
		if ( empty( $webhook_key ) || ! in_array( $webhook_key, $review_keys ) ) {
			wp_send_json_error( [ 'message' => __( 'Webhook key invalid', 'wp-loyalty-judge-me' ) ] );
		}
		$response_code = self::deleteHook( $webhook_key );
		if ( $response_code >= 200 && $response_code <= 299 ) {
			wp_send_json_success( [ 'message' => __( 'Webhook deleted successfully', 'wp-loyalty-judge-me' ) ] );
		}
		wp_send_json_error( [ 'message' => __( 'Webhook delete failed', 'wp-loyalty-judge-me' ) ] );
	}

	/**
	 * Delete Hook Request.
	 *
	 * @param string $key Key.
	 *
	 * @return int|mixed
	 */
	protected static function deleteHook( $key ) {
		if ( empty( $key ) ) {
			return 0;
		}
		$domain         = constant( 'JGM_SHOP_DOMAIN' );
		$token          = get_option( 'judgeme_shop_token' );
		$api_url        = 'https://judge.me/api/v1/';
		$url            = $api_url . 'webhooks';
		$webhook_params = [
			'api_token'   => $token,
			'shop_domain' => $domain,
			'key'         => $key,
			'url'         => self::getDomainUrl() . '/wp-json/wployalty/judgeme/v1/' . $key
		];
		$response       = wp_remote_post( $url, [
			'method'  => 'DELETE',
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => json_encode( $webhook_params )
		] );

		if ( is_wp_error( $response ) ) {
			return 400;
		}

		return ! empty( $response['response']['code'] ) ? $response['response']['code'] : 0;
	}

	/**
	 * Create webhook.
	 *
	 * @return void
	 */
	public static function createWebHook() {
		if ( ! WC::isBasicSecurityValid( 'wljm_create_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic validation failed', 'wp-loyalty-judge-me' ) ] );
		}

		$review_keys = self::getReviewKeys();
		$webhook_key = (string) Input::get( 'webhook_key' );
		if ( empty( $webhook_key ) || ! in_array( $webhook_key, $review_keys ) ) {
			wp_send_json_error( [ 'message' => __( 'Webhook key invalid', 'wp-loyalty-judge-me' ) ] );
		}
		$response_code = self::createHook( $webhook_key );
		if ( $response_code >= 200 && $response_code <= 299 ) {
			wp_send_json_success( [ 'message' => __( 'Webhook created successfully', 'wp-loyalty-judge-me' ) ] );
		}
		wp_send_json_error( [ 'message' => __( 'Webhook create failed', 'wp-loyalty-judge-me' ) ] );
	}

	/**
	 * Create hook request.
	 *
	 * @param string $key Key.
	 *
	 * @return int|mixed
	 */
	protected static function createHook( $key ) {
		$domain         = constant( 'JGM_SHOP_DOMAIN' );
		$token          = get_option( 'judgeme_shop_token' );
		$api_url        = 'https://judge.me/api/v1/';
		$url            = $api_url . 'webhooks';
		$webhook_params = [
			'api_token'   => $token,
			'shop_domain' => $domain,
			'webhook'     => [
				'key' => $key,
				'url' => self::getDomainUrl() . '/wp-json/wployalty/judgeme/v1/' . $key
			]
		];

		$response = wp_remote_post( $url, [
			'method'  => 'POST',
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => json_encode( $webhook_params )
		] );
		if ( is_wp_error( $response ) ) {
			return 400;
		}

		return ! empty( $response['response']['code'] ) ? $response['response']['code'] : 0;
	}

	/**
	 * Register rest api.
	 *
	 * @return void
	 */
	public static function registerRestApi() {
		$namespace = 'wployalty/judgeme/v1';
		register_rest_route( $namespace, '/review/created', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handleCreatedAction' ],
			'permission_callback' => '__return_true', // authentication is handled in the callback
		] );
		register_rest_route( $namespace, '/review/updated', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handleUpdatedAction' ],
			'permission_callback' => '__return_true', // authentication is handled in the callback
		] );
	}

	/**
	 * Handle review create action.
	 *
	 * @param $data
	 *
	 * @return \WP_REST_Response
	 */
	public static function handleCreatedAction( $data ) {
		$token           = get_option( 'judgeme_shop_token' );
		$header_hashed   = $data->get_header( 'JUDGEME-HMAC-SHA256' );
		$internal_hashed = hash_hmac( 'sha256', $data->get_body(), $token, false );
		$response        = [];
		if ( hash_equals( $header_hashed, $internal_hashed ) ) {
			$body = $data->get_json_params();
			//$review_id      = $body['review']['id'];
			$reviewer_email = $body['review']['reviewer']['email'];
			$prod_id        = $body['review']['product_external_id'];
			//need to earn point after create review
			if ( $prod_id > 0 && ! empty( $reviewer_email ) && filter_var( $reviewer_email, FILTER_VALIDATE_EMAIL ) ) {
				$product_review_helper = new \Wlr\App\Premium\Helpers\ProductReview();
				$action_data           = [
					'user_email'         => $reviewer_email,
					'product_id'         => $prod_id,
					'is_calculate_based' => 'product',
					'product'            => function_exists( 'wc_get_product' ) ? wc_get_product( $prod_id ) : false,
					'review_has_images'  => ! empty( $body['review']['has_published_pictures'] ),
                    'review_has_videos'  => ! empty( $body['review']['has_published_videos'] )
				];
				$product_review_helper->applyEarnProductReview( $action_data );
			}
			$response['success'] = true;
			$response['message'] = __( 'Webhook received successfully', 'wp-loyalty-judge-me' );
		} else {
			$response['success'] = false;
			$response['message'] = __( 'Hash validation failed', 'wp-loyalty-judge-me' );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Handle review update action.
	 *
	 * @param $data
	 *
	 * @return \WP_REST_Response
	 */
	public static function handleUpdatedAction( $data ) {
		$token           = get_option( 'judgeme_shop_token' );
		$header_hashed   = $data->get_header( 'JUDGEME-HMAC-SHA256' );
		$internal_hashed = hash_hmac( 'sha256', $data->get_body(), $token, false );
		$response        = [];
		if ( hash_equals( $header_hashed, $internal_hashed ) ) {
			$body = $data->get_json_params();
			//$review_id      = $body['review']['id'];
			$reviewer_email = $body['review']['reviewer']['email'];
			$prod_id        = $body['review']['product_external_id'];
			//need to earn point after create review
			if ( $prod_id > 0 && ! empty( $reviewer_email ) && filter_var( $reviewer_email, FILTER_VALIDATE_EMAIL ) ) {
				$product_review_helper = new \Wlr\App\Premium\Helpers\ProductReview();
				$action_data           = [
					'user_email'         => $reviewer_email,
					'product_id'         => $prod_id,
					'is_calculate_based' => 'product',
					'product'            => function_exists( 'wc_get_product' ) ? wc_get_product( $prod_id ) : false,
					'review_has_images'  => ! empty( $body['review']['has_published_pictures'] ),
					'review_has_videos'  => ! empty( $body['review']['has_published_videos'] )
				];
				$product_review_helper->applyEarnProductReview( $action_data );
			}
			$response['success'] = true;
			$response['message'] = __( 'Webhook received successfully', 'wp-loyalty-judge-me' );
		} else {
			$response['success'] = false;
			$response['message'] = __( 'Hash validation failed', 'wp-loyalty-judge-me' );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Display product review message.
	 *
	 * @return void
	 */
	public static function displayProductReviewMessage() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		global $product;
		$post_id = is_object( $product ) && WC::isMethodExists( $product, 'get_id' ) ? $product->get_id() : 0;
		if ( $post_id <= 0 ) {
			return;
		}
		$earn_campaign    = EarnCampaign::getInstance();
		$cart_action_list = [
			'product_review'
		];
		$extra            = [
			'user_email'         => WC::getLoginUserEmail(),
			'product_id'         => $post_id,
			'is_calculate_based' => 'product',
			'product'            => function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false
		];
		$reward_list      = $earn_campaign->getActionEarning( $cart_action_list, $extra );
		$message          = '';
		foreach ( $reward_list as $action => $rewards ) {
			foreach ( $rewards as $key => $reward ) {
				if ( isset( $reward['messages'] ) && ! empty( $reward['messages'] ) ) {
					$message .= "<br/>" . $reward['messages'];
				}
			}
		}
		echo $message;
	}
}