<?php
function U(string $str) { return rawurlencode($str); }
# as per 'nocache' @ https://www.php.net/manual/en/function.session-cache-limiter.php
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
<head>
<title>Precise Clock - QR code</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0,  minimum-scale=1.0">
<meta name="theme-color" content="#000">
<link rel="manifest" href="manifest-phone-clock.webmanifest?<?= U(filemtime('manifest-phone-clock.webmanifest')) ?>">
<style>
html, body {
	width: 100%;
	height: 100%;
	margin: 0;
	padding: 0;
}
</style>
</head>
<body style="background: black; display: flex; align-items: center; justify-content: center;">
<a href="."><img src="qr.png" width="300" height="300" style="width: 300px; height: 300px;"/></a>
