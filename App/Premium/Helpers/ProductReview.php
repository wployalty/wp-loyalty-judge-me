<?php

namespace Wljm\App\Premium\Helpers;

use Wljm\App\Helpers\Base;
use Wljm\App\Helpers\EarnCampaign;

class ProductReview extends Base {
	function applyEarnProductReview( $action_data ) {
		if ( ! is_array( $action_data ) || empty( $action_data['user_email'] ) ) {
			return false;
		}
		if ( empty( $action_data['product_id'] ) ) {
			return false;
		}
		$status           = false;
		$earn_campaign    = EarnCampaign::getInstance();
		$cart_action_list = array( 'product_review' );
		foreach ( $cart_action_list as $action_type ) {
			$variant_reward = $this->getTotalEarning( $action_type, array(), $action_data );
			foreach ( $variant_reward as $campaign_id => $v_reward ) {
				if ( isset( $v_reward['point'] ) && ! empty( $v_reward['point'] ) && $v_reward['point'] > 0 ) {
					$status = $earn_campaign->addEarnCampaignPoint( $action_type, $v_reward['point'], $campaign_id, $action_data );
				}
				if ( isset( $v_reward['rewards'] ) && $v_reward['rewards'] ) {
					foreach ( $v_reward['rewards'] as $single_reward ) {
						$status = $earn_campaign->addEarnCampaignReward( $action_type, $single_reward, $campaign_id, $action_data );
					}
				}
			}
		}

		return $status;
	}

}