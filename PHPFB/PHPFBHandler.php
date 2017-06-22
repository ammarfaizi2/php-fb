<?php

namespace PHPFB;

defined("data") or die("data not defined !");
defined("fb_data") or die("fb_data not defined !");


use PHPFB\Teacrypt;
use PHPFB\Hub\Singleton;

/**
 * @author	Ammar Faizi <ammarfaizi2@gmail.com>
 * @version 0.0.1
 * @package PHPFB
 */

class PHPFBHandler
{
	const VERSION 	= "0.0.1";
	const USERAGENT = "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:46.0) Gecko/20100101 Firefox/46.0";

	/**
	 * Use Singleton trait.
	 */
	use Singleton;

	/**
	 * @var array
	 */
	public $curl_info;

	/**
	 * @var string
	 */
	public $user_cookies;

	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	public static function run()
	{
		print self::getInstance()->curl("https://m.facebook.com");
	}

	private function curl($url, $post = null, $ops = null)
	{
		$this->decrypt_cookies();
		$ch = curl_init($url);
		$op = array(
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_SSL_VERIFYHOST	=> false,
				CURLOPT_USERAGENT		=> self::USERAGENT,
				CURLOPT_COOKIEJAR		=> $this->user_cookies,
				CURLOPT_COOKIEFILE		=> $this->user_cookies
			);
		if (is_array($ops)) {
			foreach ($ops as $key => $value) {
				$op[$key] = $value;
			}
		}
		curl_setopt_array($ch, $op);
		$out = curl_exec($ch);
		$this->curl_info = curl_getinfo($ch);
		$err = curl_error($ch) and $out = curl_errno($ch).": ".$err."\n";
		$this->encrypt_cookies();
		return $out;
	}

	private function decrypt_cookies()
	{

	}

	private function encrypt_cookies()
	{

	}
}