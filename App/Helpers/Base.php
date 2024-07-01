<?php

namespace Wljm\App\Helpers;

use Wljm\App\Models\EarnCampaignTransactions;

class Base {
	public static $woocommerce_helper;

	public function __construct( $config = array() ) {
		self::$woocommerce_helper              = empty( self::$woocommerce_helper ) ? Woocommerce::getInstance() : self::$woocommerce_helper;
	}

	public function getPointLabel( $point, $label_translate = true ) {
		$setting_option = get_option( 'wlr_settings', '' );
		$singular       = ( isset( $setting_option['wlr_point_singular_label'] ) && ! empty( $setting_option['wlr_point_singular_label'] ) ) ? $setting_option['wlr_point_singular_label'] : 'point';
		if ( $label_translate ) {
			$singular = __( $singular, 'wp-loyalty-rules' );
		}
		$plural = ( isset( $setting_option['wlr_point_label'] ) && ! empty( $setting_option['wlr_point_label'] ) ) ? $setting_option['wlr_point_label'] : 'points';
		if ( $label_translate ) {
			$plural = __( $plural, 'wp-loyalty-rules' );
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
					$class_free_helper = '\\Wlr\\App\\Helpers\\' . $class_name;
					$class_pro_helper  = '\\Wlr\\App\\Premium\\Helpers\\' . $class_name;
					if ( class_exists( $class_free_helper ) ) {
						$helper = new $class_free_helper();
					} elseif ( class_exists( $class_pro_helper ) ) {
						$helper = new $class_pro_helper();
					}
					if ( isset( $helper ) && method_exists( $helper, 'processMessage' ) ) {
						$messages = $helper->processMessage( $point_rule, $earning );
					}
				}
			}
		}

		return $messages;
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

	function getSocialActionList() {
		$social_action_list = array(
			'facebook_share',
			'twitter_share',
			'whatsapp_share',
			'email_share'
		);

		return apply_filters( 'wlr_social_action_list', $social_action_list );
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
	function is_valid_action( $action_type ) {
		$status       = false;
		$action_types = self::$woocommerce_helper->getActionTypes();
		if ( ! empty( $action_type ) && isset( $action_types[ $action_type ] ) && ! empty( $action_types[ $action_type ] ) ) {
			$status = true;
		}

		return $status;
	}
}