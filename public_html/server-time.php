<?php
ini_set('zlib.output_compression', '0');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Content-type: text/plain');
echo microtime(true)*1000;
