<?php

namespace PHPFB;

defined("data") or die("data not defined !");
defined("fb_data") or die("fb_data not defined !");


use PHPFB\Teacrypt;
use PHPFB\Hub\Singleton;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @version 0.0.1
 * @package PHPFB
 */

class PHPFBHandler
{
	const APPKEY	= "PHPFB";
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
	private $header_request = array();

	/**
	 * @var array
	 */
	private $header_response = array();

	/**
	 * @var array
	 */
	private $send_header = array();

	/**
	 * @var string
	 */
	private $output;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		is_dir(fb_data."/cookies") or mkdir(fb_data."/cookies");
		is_dir(fb_data."/logs") or mkdir(fb_data."/logs");
		if (!(is_dir(fb_data."/cookies") && is_dir(fb_data."/logs"))) {
			throw new \Exception("Cannot make directory", 1);
			die("Cannot make directory");
		}
		$this->user_cookies = fb_data."/cookies/user_cookies.cr";
		$this->init_cookiefile();
	}

	/**
	 * Run App.
	 */
	public static function run()
	{
		$self = self::getInstance();
		$self->rdyn();
		foreach ($self->send_header as $key => $value) {
			if (!empty($value)) {
				header($key.": ".$value);
			}
		}
		print $self->output;
	}

	/**
	 * Private run.
	 */
	private function rdyn()
	{
		$op = null;
		if (PHP_SAPI == "cli") {
			$this->header = array();
			$post = null;
		} else {
			$this->header = getallheaders();
			$this->standard_header();
			$post = count($_POST) ? $_POST : null;
		}
		if ($post!==null) {
			if (isset($this->header_request['content-type'])) {
				if ($header['content-type']=="application/x-www-form-urlencoded") {
					$_p = "";
					foreach ($_POST as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $k2 => $v2) {
								$_p .= $key.urlencode("[".$k2."]")."=".urlencode($v2)."&";
							}
						} else {
							$_p .= $key."=".urlencode($value)."&";
						}
					}
					$post = rtrim($_p, "&");	
				} else {
					$post = $_POST;
					if (count($_FILES)) {
						foreach ($_FILES as $key => $value) {
							is_dir(__DIR__."/tmp") or mkdir(__DIR__."/tmp");
							move_uploaded_file($value['tmp_name'], __DIR__.'/tmp/'.$value['name']);
							$post[$key] = new \CurlFile(__DIR__.'/tmp/'.$value['name']);
						}
					} else {
						$post = http_build_query($post);
					}
				}
			}
		}
		$url = isset($_GET['url']) ? urldecode($_GET['url']) : self::FBURL;
		$url = filter_var($url, FILTER_VALIDATE_URL) ? $url : self::FBURL."/".$url;
		$this->output = $this->page_fixer($this->curl($url, $post, $op));
		$this->send_header["Content-type"] = $this->header_response['content-type'];
		if (isset($this->curl_info['redirect_url']) && !empty($this->curl_info['redirect_url'])) {
			$this->send_header["location"] = "?url=".urlencode($this->curl_info['redirect_url']);
		}
	}

	/**
	 * Init user cookie.
	 */
	private function init_cookiefile()
	{
		if (!file_exists($this->user_cookies)) {
			file_put_contents($this->user_cookies, "");
		}
	}

	/**
	 * Convert to standart header
	 */
	private function standard_header()
	{
		$flag = array();
		foreach ($this->header as $key => $value) {
			$flag[strtolower($key)] = $value;
		}
		$this->header_request = $flag;
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
		return $src;
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
				CURLOPT_COOKIEFILE		=> $this->user_cookies,
				CURLOPT_HEADER			=> true
			);
		if (is_array($ops)) {
			foreach ($ops as $key => $value) {
				$op[$key] = $value;
			}
		}
		if ($post!==null) {
			$op[CURLOPT_POST] = true;
			$op[CURLOPT_POSTFIELDS] = $post;
		}
		curl_setopt_array($ch, $op);
		$out = curl_exec($ch);
		$this->curl_info = curl_getinfo($ch);
		$out = $this->shift_header($out);
		$err = curl_error($ch) and $out = curl_errno($ch).": ".$err."\n";
		curl_close($ch);
		$this->encrypt_cookies();
		return $out;
	}

	/**
	 * Get response header.
	 * @param  string $out
	 * @return array
	 */
	private function shift_header($out)
	{
		$this->header_response = array();
		foreach (explode("\n", substr($out, 0, $this->curl_info['header_size'])) as $val) {
			$b = explode(":", $val, 2);
			$this->header_response[strtolower(trim($b[0]))] = $b[1];
		}
		return substr($out, $this->curl_info['header_size']);
	}

	/**
	 * Decrypt user cookies.
	 */
	private function decrypt_cookies()
	{
		$cookie_data = file_get_contents($this->user_cookies);
		if (!empty(trim($cookie_data))) {
			file_put_contents($this->user_cookies, Teacrypt::decrypt($cookie_data, self::APPKEY));
		}
		$cookie_data = file_get_contents($this->user_cookies);
	}

	/**
	 * Encrypt user cookies.
	 */
	private function encrypt_cookies()
	{
		$cookie_data = file_get_contents($this->user_cookies);
		if (!empty(trim($cookie_data))) {
			file_put_contents($this->user_cookies, Teacrypt::encrypt($cookie_data, self::APPKEY));
		}
	}
}