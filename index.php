<?php
require_once __DIR__ . '/lib/random_image.php';

$pcPath = __DIR__ . '/landscape';
$mobilePath = __DIR__ . '/portrait';

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent);
$path = $isMobile ? $mobilePath : $pcPath;

ri_handle_random_image_request($path);

