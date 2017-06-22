<?php
require __DIR__.'/vendor/autoload.php';
use PHPFB\PHPFB;

define("data", __DIR__."/data");
define("fb_data", data."/fb_data");

is_dir(data) or mkdir(data);
is_dir(fb_data) or mkdir(fb_data);
(is_dir(data) and is_dir(fb_data)) or die("Gagal membuat directory !");

PHPFB::run();