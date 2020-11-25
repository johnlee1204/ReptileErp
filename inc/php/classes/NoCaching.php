<?php

class NoCaching{
	static function disableCaching(){
		header("Content-Type: text/html; charset=utf-8");
		header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); //last modified right now
		//caching controls to tell the browser not to cache
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
}
?>