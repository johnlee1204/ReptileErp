<?php


function sql_safe($text){
	if( ini_get('magic_quotes_gpc') === true || ini_get('magic_quotes_runtime') === true){
		$text = str_replace('\\','\\\\',$text);
	}
	return str_replace("'","''",$text);
}


?>