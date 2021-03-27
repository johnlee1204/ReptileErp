<?php

class LegacyLoadingMessage {

	function __construct($config = array()){
		
		$this->loadingImage = '/inc/img/large-loading.gif';
		$this->loadingElementId = 'loadingMsg';
		$this->loadingText = 'Loading...';
		
		if(isset($config['loadingImage']) ){
			$this->loadingImage = $config['loadingImage'];
		}
		if(isset($config['loadingElementId']) ){
			$this->loadingElementId = $config['loadingElementId'];
		}
		if(isset($config['loadingText']) ){
			$this->loadingText = $config['loadingText'];
		}
	
		//Turn of Apache output compression
		// Necessary if you have gzip setup in your httpd.conf (e.g. LoadModule deflate_module modules/mod_deflate.so)
		if(function_exists('apache_setenv')){
			apache_setenv('no-gzip', 1);
		}
		ini_set('zlib.output_compression', 0);
		
		//Disable all PHP output buffering
		ini_set('output_buffering', 'Off');
		ini_set('implicit_flush', 1);
		ob_implicit_flush(1);
		
		for ($i = 0, $level = ob_get_level(); $i < $level; $i++) { ob_end_flush(); } //Flush all levels of the buffer to start
	}
	
// =======================================================
	
	function show(){
		
		echo <<<LOADINGMSG
<style type="text/css">
	#{$this->loadingElementId}{font-family:arial;font-weight:bold;}
</style>
<div id="{$this->loadingElementId}"><center>
	<br/>{$this->loadingText}<br/><br/>
	<img src="{$this->loadingImage}" />
</center></div>
<!-- 
Load Msg Padding
x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x 
x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x  
x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x x
-->
LOADINGMSG;

		flush();
	}
	
// =======================================================
	
	function hide(){
		echo "<script>(function(){var loading = document.getElementById('{$this->loadingElementId}'); loading.style.visibility='hidden'; loading.style.display='inline'; loading.parentNode.removeChild(loading);})();</script>";
	}

}

?>