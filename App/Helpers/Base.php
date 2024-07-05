<?php

namespace Wljm\App\Helpers;

use Wljm\App\Models\EarnCampaignTransactions;
use Wljm\App\Models\Logs;
use Wljm\App\Models\PointsLedger;
use Wljm\App\Models\UserRewards;
use Wljm\App\Models\Users;

class Base {
	public static $woocommerce_helper, $user_model, $earn_campaign_transaction_model, $user_by_email;
	public static $user_reward_by_coupon = array();

	public function __construct( $config = array() ) {
		self::$woocommerce_helper              = empty( self::$woocommerce_helper ) ? Woocommerce::getInstance() : self::$woocommerce_helper;
		self::$user_model                      = empty( self::$user_model ) ? new Users() : self::$user_model;
		self::$earn_campaign_transaction_model = empty( self::$earn_campaign_transaction_model ) ? new EarnCampaignTransactions() : self::$earn_campaign_transaction_model;
	}

	public function getPointLabel( $point, $label_translate = true ) {
		$setting_option = get_option( 'wlr_settings', '' );
		$singular       = ( isset( $setting_option['wlr_point_singular_label'] ) && ! empty( $setting_option['wlr_point_singular_label'] ) ) ? $setting_option['wlr_point_singular_label'] : 'point';
		if ( $label_translate ) {
			$singular = __( $singular, 'wp-loyalty-judge-me' );
		}
		$plural = ( isset( $setting_option['wlr_point_label'] ) && ! empty( $setting_option['wlr_point_label'] ) ) ? $setting_option['wlr_point_label'] : 'points';
		if ( $label_translate ) {
			$plural = __( $plural, 'wp-loyalty-judge-me' );
		}
		$point_label = ( $point == 0 || $point > 1 ) ? $plural : $singular;

		return apply_filters( 'wlr_get_point_label', $point_label, $point );
	}

	function isEligibleForEarn( $action_type, $extra = array() ) {
		return apply_filters( 'wlr_is_eligible_for_earning', true, $action_type, $extra );
	}

	function getTotalEarning( $action_type = '', $ignore_condition = array(), $extra = array(), $is_product_level = false ) {
		$earning = array();
		if ( ! $this->is_valid_action( $action_type ) || ! $this->isEligibleForEarn( $action_type, $extra ) || self::$woocommerce_helper->isBannedUser() ) {
			return $earning;
		}
		$campaign_helper     = EarnCampaign::getInstance();
		$earn_campaign_table = new \Wljm\App\Models\EarnCampaign();
		$campaign_list       = $earn_campaign_table->getCampaignByAction( $action_type );

		if ( ! empty( $campaign_list ) ) {
			$action_data = array(
				'action_type'      => $action_type,
				'ignore_condition' => $ignore_condition,
				'is_product_level' => $is_product_level,
			);
			if ( ! empty( $extra ) && is_array( $extra ) ) {
				foreach ( $extra as $key => $value ) {
					$action_data[ $key ] = $value;
				}
			}
			$action_data = apply_filters( 'wlr_before_rule_data_process', $action_data, $campaign_list );
			$order_id    = isset( $action_data['order'] ) && ! empty( $action_data['order'] ) ? $action_data['order']->get_id() : 0;
			self::$woocommerce_helper->_log( 'getTotalEarning Action data:' . json_encode( $action_data ) );
			$social_share = $this->getSocialActionList();
			foreach ( $campaign_list as $campaign ) {
				$processing_campaign = $campaign_helper->getCampaign( $campaign );
				$campaign_id         = isset( $processing_campaign->earn_campaign->id ) && $processing_campaign->earn_campaign->id > 0 ? $processing_campaign->earn_campaign->id : 0;
				if ( $campaign_id && $order_id ) {
					self::$woocommerce_helper->_log( 'getTotalEarning Action:' . $action_type . ',Campaign id:' . $campaign_id . ', Before check user already earned' );
					if ( $this->checkUserEarnedInCampaignFromOrder( $order_id, $campaign_id ) ) {
						continue;
					}
				}
				$action_data['campaign_id'] = $campaign_id;
				$campaign_earning           = array();
				if ( isset( $processing_campaign->earn_campaign->campaign_type ) && 'point' === $processing_campaign->earn_campaign->campaign_type ) {
					//campaign_id and order_id
					self::$woocommerce_helper->_log( 'getTotalEarning Action:' . $action_type . ',Campaign id:' . $campaign_id . ', Before earn point:' . json_encode( $action_data ) );
					$campaign_earning['point']         = $processing_campaign->getCampaignPoint( $action_data );
					$earning[ $campaign->id ]['point'] = $campaign_earning['point'];
				} elseif ( isset( $processing_campaign->earn_campaign->campaign_type ) && 'coupon' === $processing_campaign->earn_campaign->campaign_type ) {
					self::$woocommerce_helper->_log( 'getTotalEarning Action:' . $action_type . ',Campaign id:' . $campaign_id . ', Before earn coupon:' . json_encode( $action_data ) );
					$earning[ $campaign->id ]['rewards'][] = $campaign_earning['rewards'][] = $processing_campaign->getCampaignReward( $action_data );
				}
				$earning[ $campaign->id ]['messages'] = $this->processCampaignMessage( $action_type, $processing_campaign, $campaign_earning );
				if ( in_array( $action_type, $social_share ) ) {
					$earning[ $campaign->id ]['icon'] = isset( $processing_campaign->earn_campaign->icon ) && ! empty( $processing_campaign->earn_campaign->icon ) ? $processing_campaign->earn_campaign->icon : '';
				}
			}
			self::$woocommerce_helper->_log( 'getTotalEarning Action:' . $action_type . ', Total earning:' . json_encode( $earning ) );
		}

		return $earning;
	}

	function processCampaignMessage( $action_type, $rule, $earning ) {
		$messages = array();
		if ( ! empty( $action_type ) && $action_type === $rule->earn_campaign->action_type ) {
			if ( isset( $rule->earn_campaign->point_rule ) && ! empty( $rule->earn_campaign->point_rule ) ) {
				if ( self::$woocommerce_helper->isJson( $rule->earn_campaign->point_rule ) ) {
					$point_rule        = json_decode( $rule->earn_campaign->point_rule );
					$class_name        = ucfirst( $this->camelCaseAction( $action_type ) );
					$class_free_helper = '\\Wljm\\App\\Helpers\\' . $class_name;
					$class_pro_helper  = '\\Wljm\\App\\Premium\\Helpers\\' . $class_name;
					if ( class_exists( $class_free_helper ) ) {
						$helper = new $class_free_helper;
					} elseif ( class_exists( $class_pro_helper ) ) {
						$helper = new $class_pro_helper;
					}
					if ( isset( $helper ) && method_exists( $helper, 'processMessage' ) ) {
						$messages = $helper->processMessage( $point_rule, $earning );
					}
				}
			}
		}

		return $messages;
	}

	public function roundPoints( $points ) {
		$setting_option  = get_option( 'wlr_settings', '' );
		$rounding_option = ( isset( $setting_option['wlr_point_rounding_type'] ) && ! empty( $setting_option['wlr_point_rounding_type'] ) ) ? $setting_option['wlr_point_rounding_type'] : 'round';
		switch ( $rounding_option ) {
			case 'ceil':
				$point_earned = ceil( $points );
				break;
			case 'floor':
				$point_earned = floor( $points );
				break;
			default:
				$point_earned = round( $points );
				break;
		}

		return $point_earned;
	}

	public function getRewardLabel( $reward_count = 0 ) {
		$setting_option = get_option( 'wlr_settings', '' );
		$singular       = ( isset( $setting_option['reward_singular_label'] ) && ! empty( $setting_option['reward_singular_label'] ) ) ? __( $setting_option['reward_singular_label'], 'wp-loyalty-judge-me' ) : __( 'reward', 'wp-loyalty-judge-me' );
		$plural         = ( isset( $setting_option['reward_plural_label'] ) && ! empty( $setting_option['reward_plural_label'] ) ) ? __( $setting_option['reward_plural_label'], 'wp-loyalty-judge-me' ) : __( 'rewards', 'wp-loyalty-judge-me' );
		$reward_label   = ( $reward_count == 0 || $reward_count > 1 ) ? $plural : $singular;

		return apply_filters( 'wlr_get_reward_label', $reward_label, $reward_count );
	}

	function processShortCodes( $short_codes, $message ) {
		if ( ! is_array( $short_codes ) ) {
			return $message;
		}
		foreach ( $short_codes as $key => $value ) {
			$message = str_replace( $key, $value, $message );
		}

		return apply_filters( 'wlr_process_message_short_codes', $message, $short_codes );
	}

	function isAllowEarningWhenCoupon( $is_cart = true, $order = '' ) {
		$setting_option = get_option( 'wlr_settings', '' );
		$allow_earning  = ( isset( $setting_option['allow_earning_when_coupon'] ) && ! empty( $setting_option['allow_earning_when_coupon'] ) ) ? $setting_option['allow_earning_when_coupon'] : 'yes';
		if ( $allow_earning == 'yes' ) {
			return true;
		}
		$coupons = [];
		if ( $is_cart && function_exists( 'WC' ) && isset( WC()->cart->applied_coupons ) && ! empty( WC()->cart->applied_coupons ) ) {
			$coupons = WC()->cart->applied_coupons;
		} elseif ( ! empty( $order ) ) {
			$order = self::$woocommerce_helper->getOrder( $order );
			$items = self::$woocommerce_helper->isMethodExists( $order, 'get_items' ) ? $order->get_items( 'coupon' ) : [];
			foreach ( $items as $item ) {
				if ( self::$woocommerce_helper->isMethodExists( $item, 'get_code' ) ) {
					$coupons[] = $item->get_code();
				}
			}
		}
		if ( empty( $coupons ) ) {
			return true;
		}

		if ( ! apply_filters( 'wlr_is_allow_earning_when_coupon', true, $coupons ) ) {
			return false;
		}

		foreach ( $coupons as $code ) {
			if ( $this->is_loyalty_coupon( $code ) ) {
				return false;
			}
		}

		return true;
	}

	function get_unique_refer_code( $ref_code = '', $recursive = false, $email = '' ) {
		$referral_settings = get_option( 'wlr_settings' );
		$prefix            = ( isset( $referral_settings['wlr_referral_prefix'] ) && ! empty( $referral_settings['wlr_referral_prefix'] ) ) ? $referral_settings['wlr_referral_prefix'] : 'REF-';
		$ref_code          = ! empty( $ref_code ) ? $ref_code : $prefix . $this->get_random_code();
		if ( ! empty( $ref_code ) ) {
			if ( $recursive ) {
				$ref_code = $prefix . $this->get_random_code();
			}
			$ref_code = sanitize_text_field( $ref_code );
			$user     = self::$user_model->getQueryData( array(
				'refer_code' => array(
					'operator' => '=',
					'value'    => $ref_code
				)
			), '*', array(), false );
			if ( ! empty( $user ) ) {
				return $this->get_unique_refer_code( $ref_code, true, $email );
			}
		}

		return apply_filters( 'wlr_generate_referral_code', $ref_code, $prefix, $email );
	}

	function get_random_code() {
		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$ref_code_random = '';
		for ( $i = 0; $i < 2; $i ++ ) {
			$ref_code_random .= substr( str_shuffle( $permitted_chars ), 0, 3 ) . '-';
		}

		return strtoupper( trim( $ref_code_random, '-' ) );
	}

	function getActionName( $action_type ) {
		$action_name = '';
		if ( empty( $action_type ) ) {
			return $action_name;
		}
		$action_types = self::$woocommerce_helper->getActionTypes();
		if ( isset( $action_types[ $action_type ] ) ) {
			$action_name = $action_types[ $action_type ];
		}
		if ( empty( $action_name ) ) {
			$extra_action_types = $this->getExtraActionList();
			if ( isset( $extra_action_types[ $action_type ] ) ) {
				$action_name = $extra_action_types[ $action_type ];
			}
		}

		return empty( $action_name ) ? __( "-", 'wp-loyalty-judge-me' ) : $action_name;
	}

	function getAchievementName( $achievement_key ) {
		if ( empty( $achievement_key ) ) {
			return '';
		}
		$achievement_names = array(
			'level_update'  => __( 'Level Update', 'wp-loyalty-judge-me' ),
			'daily_login'   => __( 'Daily Login', 'wp-loyalty-judge-me' ),
			'custom_action' => __( 'Custom Action', 'wp-loyalty-judge-me' ),
		);
		$achievement_names = apply_filters( 'wlr_achievement_names', $achievement_names, $achievement_key );

		return isset( $achievement_names[ $achievement_key ] ) && ! empty( $achievement_names[ $achievement_key ] ) ? $achievement_names[ $achievement_key ] : '';
	}

	function updatePointLedger( $data = array(), $point_action = 'credit', $is_update = true ) {
		if ( ! is_array( $data ) || empty( $data['user_email'] ) || ( $data['points'] <= 0 && ! $this->isValidPointLedgerExtraAction( $data['action_type'] ) ) || empty( $data['action_type'] ) ) {
			return false;
		}
		$conditions               = array(
			'user_email' => array(
				'operator' => '=',
				'value'    => sanitize_email( $data['user_email'] ),
			),
		);
		$point_ledger             = new PointsLedger();
		$user_ledger              = $point_ledger->getQueryData( $conditions, '*', array(), false );
		$point_ledger_is_starting = false;
		if ( empty( $user_ledger ) ) {
			/*$user = self::$user_model->getQueryData($conditions, '*', array(), false);
            $credit_points = isset($user->points) && !empty($user->points) ? $user->points : 0;
            if ($this->isValidExtraAction($data['action_type']) && empty($credit_points)) {
                $credit_points = (isset($data['points']) && $data['points'] > 0 ? $data['points'] : 0);
            }*/
			$point_data = array(
				'user_email'          => $data['user_email'],
				'credit_points'       => (int) isset( $data['points'] ) && $data['points'] > 0 ? $data['points'] : 0,
				'action_type'         => 'starting_point',
				'debit_points'        => 0,
				'action_process_type' => 'starting_point',
				'note'                => __( 'Starting point of customer', 'wp-loyalty-judge-me' ),
				'created_at'          => strtotime(
					date( 'Y-m-d H:i:s' )
				),
			);
			$point_ledger->insertRow( $point_data );
			$point_ledger_is_starting = true;
		}
		if ( $is_update && ! $point_ledger_is_starting ) {
			$point_data = array(
				'user_email'          => $data['user_email'],
				'credit_points'       => $point_action == 'credit' ? $data['points'] : 0,
				'action_type'         => $data['action_type'],
				'debit_points'        => $point_action == 'debit' ? $data['points'] : 0,
				'action_process_type' => isset( $data['action_process_type'] ) && ! empty( $data['action_process_type'] ) ? $data['action_process_type'] : $data['action_type'],
				'note'                => isset( $data['note'] ) && ! empty( $data['note'] ) ? $data['note'] : '',
				'created_at'          => strtotime( date( 'Y-m-d H:i:s' ) ),
			);
			$point_ledger->insertRow( $point_data );
		}

		return true;
	}

	function isValidPointLedgerExtraAction( $action_type ) {
		$action_types = apply_filters( 'wlr_extra_point_ledger_action_list', array(
			'new_user_add',
			'admin_change',
			'import'
		) );

		return ! empty( $action_type ) && in_array( $action_type, $action_types );
	}

	function add_note( $data ) {
		return ( new Logs() )->saveLog( $data );
	}

	function getReferralUrl( $code = '' ) {
		if ( empty( $code ) ) {
			$user_email = self::$woocommerce_helper->get_login_user_email();
			$user       = $this->getPointUserByEmail( $user_email );
			$code       = ! empty( $user ) && isset( $user->refer_code ) && ! empty( $user->refer_code ) ? $user->refer_code : '';
		}
		$url = '';
		if ( ! empty( $code ) ) {
			$url = site_url() . '?wlr_ref=' . $code;
		}

		return apply_filters( 'wlr_get_referral_url', $url, $code );
	}

	function getPointUserByEmail( $user_email ) {
		if ( empty( $user_email ) ) {
			return '';
		}

		$user_email = sanitize_email( $user_email );

		if ( ! isset( self::$user_by_email[ $user_email ] ) ) {
			self::$user_by_email[ $user_email ] = self::$user_model->getQueryData(
				array(
					'user_email' => array(
						'operator' => '=',
						'value'    => $user_email,
					),
				),
				'*',
				array(),
				false
			);
		}

		return self::$user_by_email[ $user_email ];
	}

	function getSocialActionList() {
		$social_action_list = array(
			'facebook_share',
			'twitter_share',
			'whatsapp_share',
			'email_share'
		);

		return apply_filters( 'wlr_social_action_list', $social_action_list );
	}

	function is_loyalty_coupon( $code ) {
		if ( empty( $code ) ) {
			return false;
		}
		$user_reward = $this->getUserRewardByCoupon( $code );
		if ( ! empty( $user_reward ) ) {
			return true;
		}

		return false;
	}

	function getExtraActionList() {
		$action_list = array(
			'admin_change'             => __( 'Admin updated', 'wp-loyalty-judge-me' ),
			'redeem_point'             => sprintf( __( 'Convert %s to coupon', 'wp-loyalty-judge-me' ), $this->getPointLabel( 3 ) ),
			'new_user_add'             => __( 'New Customer', 'wp-loyalty-judge-me' ),
			'import'                   => __( 'Import Customer', 'wp-loyalty-judge-me' ),
			'revoke_coupon'            => __( 'Revoke coupon', 'wp-loyalty-judge-me' ),
			'expire_date_change'       => __( 'Expiry date has been changed manually', 'wp-loyalty-judge-me' ),
			'expire_email_date_change' => __( 'Expiry email date has been changed manually', 'wp-loyalty-judge-me' ),
			'expire_point'             => sprintf( __( '%s Expired', 'wp-loyalty-judge-me' ), $this->getPointLabel( 3 ) ),
			'new_level'                => __( 'New Level', 'wp-loyalty-judge-me' ),
			'rest_api'                 => __( 'REST API', 'wp-loyalty-judge-me' ),
			'birthday_change'          => __( 'Birthday change', 'wp-loyalty-judge-me' )
		);

		return apply_filters( "wlr_extra_action_list", $action_list );
	}

	function getUserRewardByCoupon( $code ) {
		if ( empty( $code ) ) {
			return '';
		}
		$code = ( is_object( $code ) && isset( $code->code ) ) ? $code->get_code() : $code;
		if ( ! isset( self::$user_reward_by_coupon[ $code ] ) ) {
			self::$user_reward_by_coupon[ $code ] = ( new UserRewards() )->getQueryData(
				array(
					'discount_code' => array(
						'operator' => '=',
						'value'    => $code,
					),
				),
				'*',
				array(),
				false
			);
		}

		return isset( self::$user_reward_by_coupon[ $code ] ) ? self::$user_reward_by_coupon[ $code ] : '';
	}

	function checkUserEarnedInCampaignFromOrder( $order_id, $campaign_id ) {
		if ( $order_id <= 0 || $campaign_id <= 0 ) {
			return false;
		}
		global $wpdb;
		$where  = $wpdb->prepare( 'order_id = %s AND campaign_id = %s', array( $order_id, $campaign_id ) );
		$result = ( new EarnCampaignTransactions() )->getWhere( $where );

		return ! empty( $result );
	}

	protected function camelCaseAction( $action_type ) {
		$action_type = trim( $action_type );
		$action_type = lcfirst( $action_type );
		$action_type = preg_replace( '/^[-_]+/', '', $action_type );
		$action_type = preg_replace_callback(
			'/[-_\s]+(.)?/u',
			function ( $match ) {
				if ( isset( $match[1] ) ) {
					return strtoupper( $match[1] );
				} else {
					return '';
				}
			},
			$action_type
		);
		$action_type = preg_replace_callback(
			'/[\d]+(.)?/u',
			function ( $match ) {
				return strtoupper( $match[0] );
			},
			$action_type
		);

		return $action_type;
	}

	function isPro() {
		return apply_filters( 'wlr_is_pro', false );
	}

	function is_valid_action( $action_type ) {
		$status       = false;
		$action_types = self::$woocommerce_helper->getActionTypes();
		if ( ! empty( $action_type ) && isset( $action_types[ $action_type ] ) && ! empty( $action_types[ $action_type ] ) ) {
			$status = true;
		}

		return $status;
	}

	function isIncludingTax() {
		$setting_option       = self::$woocommerce_helper->getOptions( 'wlr_settings', array() );
		$tax_calculation_type = ( isset( $setting_option['tax_calculation_type'] ) && ! empty( $setting_option['tax_calculation_type'] ) ) ? $setting_option['tax_calculation_type'] : 'inherit';
		$is_including_tax     = false;
		if ( $tax_calculation_type == 'inherit' ) {
			$is_including_tax = ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		} elseif ( $tax_calculation_type === 'including' ) {
			$is_including_tax = true;
		}

		return $is_including_tax;
	}
}