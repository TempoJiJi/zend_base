<?php

require_once 'setting.php';
$settings = Zend_Registry::get('ini_config');
$awsS3 = $settings['app']['awss3'];
$appUrl = $settings['app']['url'];

define('ZEND_TMP_PATH', '/zend_tmp/');

// GMT
define('GMT_SYSTEM', date('O'));
define("TOKEN_EXPIRY", "1 HOUR");

// PROTOCOL
define("C_GLOBAL_WEB_ROOT_PROTOCOL", isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') || stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https' : 'http');

// WEB ROOT
define("C_GLOBAL_WEB_ROOT", C_GLOBAL_WEB_ROOT_PROTOCOL . "://{$_SERVER['HTTP_HOST']}/");
define("STATIC_API", C_GLOBAL_WEB_ROOT_PROTOCOL . "://" . $appUrl['api']);
define("STATIC_WEB_URL", C_GLOBAL_WEB_ROOT_PROTOCOL . "://" . $appUrl['web']);

// Asset version
if (APPLICATION_ENV == 'production') {
	define("C_GLOBAL_VERSION_NO", "1.000");
} else {
	define("C_GLOBAL_VERSION_NO", date("c"));
}

// Assets
define("C_GLOBAL_ASSET", C_GLOBAL_WEB_ROOT . "asset/");
define("C_GLOBAL_APP_ASSET", C_GLOBAL_ASSET . "app-assets/");
define("C_GLOBAL_CSS", C_GLOBAL_ASSET . "css/");
define("C_GLOBAL_JS", C_GLOBAL_ASSET . "js/");
define("C_GLOBAL_IMG", C_GLOBAL_ASSET . "img/");
define("C_GLOBAL_UPLOAD", C_GLOBAL_IMG . "img/upload/");
define("C_GLOBAL_FONTS", C_GLOBAL_ASSET . "fonts/");
define("C_GLOBAL_DEMO", C_GLOBAL_ASSET . "demo/");

// upload folder
if (APPLICATION_ENV == 'development') {
	define("C_GLOBAL_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT'] . "/asset/img/upload/");
	define("C_GLOBAL_UPLOAD_PATH_URL", C_GLOBAL_IMG . "upload/");
} else {
	define("C_GLOBAL_UPLOAD_PATH", $_SERVER['DOCUMENT_ROOT'] . "/asset/img/upload/");
	define("C_GLOBAL_UPLOAD_PATH_URL", C_GLOBAL_WEB_ROOT . "/asset/img/upload/");
}

// AWS S3
define("AWS_PATH", $awsS3['path'] ?? '');
define("AWS_KEY", $awsS3['key'] ?? '');
define("AWS_SECRET", $awsS3['secret'] ?? '');
define("AWS_REGION", $awsS3['region'] ?? '');
define("AWS_BUCKET", $awsS3['bucket'] ?? '');
define('AWS_BUCKET_FOLDER', $awsS3['bucket_folder'] ?? '');
define('AWS_BUCKET_ARN', $awsS3['arn'] ?? '');