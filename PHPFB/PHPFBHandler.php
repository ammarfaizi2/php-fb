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
	const FBURL		= "https://m.facebook.com";
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
	 * @var array
	 */
	private $header = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Run App.
	 */
	public static function run()
	{

		print self::getInstance()->rdyn();
	}

	private function rdyn()
	{
		if (PHP_SAPI == "cli") {
			$this->header = array();
		} else {
			$this->header = getallheaders();
		}
		$this->standard_header();
	}

	/**
	 * Void
	 *
	 */
	private function standard_header()
	{
		$flag = array();
		foreach ($this->header as $key => $value) {
			$flag[strtolower($key)] = $value;
		}
		$this->header = $flag;
	}

	/**
	 * @param  string $src
	 * @return string
	 */
	private function page_fixer($src)
	{
		return $this->href_fixer($this->form_fixer($src));
	}

	/**
	 * @param  string $src
	 * @return string
	 */
	private function href_fixer($src)
	{
		$a = explode("<a ", $src);
		foreach ($a as $val) {
			$b = explode("href=\"", $val, 2);
			if (count($b)>1) {
				$b = explode("\"", $b[1], 2);
				$src = str_replace("href=\"".$b[0]."\"", "href=\"?".(isset($_GET['user'])?"user=".$_GET['uer']."&":"")."url=".htmlspecialchars(urlencode(urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'))))."\"", $src);
			}
		}
		return $src;
	}

	/**
	 * @param  string $src
	 * @return string
	 */
	private function form_fixer($src)
	{
		$a = explode("<form", $src);
		if (count($a)>1) {
			$r = array();
			foreach ($a as $val) {
				$b = explode("action=\"", $val, 2);
				if (count($b)>1) {
					$b = explode("\"", $b[1], 2);
					$src = str_replace("action=\"".$b[0]."\"", "action=\"?url=".htmlspecialchars(urlencode(urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'))))."\"", $src);
				}
			}
		}
	}

	/**
	 * @param  string 		$url
	 * @param  string|array $post
	 * @param  array		$ops
	 * @return string
	 */
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