<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wljm\App\Models;
defined( 'ABSPATH' ) or die();

class Users extends Base {
	function __construct() {
		parent::__construct();
		$this->table       = self::$db->prefix . 'wlr_users';
		$this->primary_key = 'id';
		$this->fields      = array(
			'user_email'          => '%s',
			'refer_code'          => '%s',
			'points'              => '%s',
			'used_total_points'   => '%s',
			'earn_total_point'    => '%s',
			'birth_date'          => '%s',
			'level_id'            => '%s',
			'is_allow_send_email' => '%d',
			'created_date'        => '%s',
			'birthday_date'       => '%s',
			'last_login'          => '%s',
			'is_banned_user'      => '%d',
		);
	}
}