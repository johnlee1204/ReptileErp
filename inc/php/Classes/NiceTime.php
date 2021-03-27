<?php

	class NiceTime{
		/**
		 *	converts a number of seconds to human readable string broken into hours, minutes, and seconds
		 *	e.g.: niceTime(75) returns "1m 15s"
		 *	seconds will be rounded to 2 decimal places
		 *	niceTime(30) = 30 seconds
		 *	niceTime(1) = 1 second
		 *	niceTime(121) = 2 minutes 1 second
		 *	niceTime(3721) = 1 hour 2 minutes 1 second
		 *
		 *	@return string
		 *	@param int $seconds
		 */
		static function generateNiceTime(/*.int.*/ $seconds=0, $short=false){
			$delims = array(
				'year' => 31536000
				,'month' => 2592000
				,'day' => 86400
				,'hour' => 3600
				,'minute' => 60
				,'second' => 1
			);

			$output = array();

			if($seconds < 1){
				return '< 1 ' . ($short ? 's' : 'second');
			}

			foreach($delims as $scale => $scaleInSeconds){
				if( $seconds >= $scaleInSeconds ){
					$scaleResult = floor($seconds / $scaleInSeconds);
					$scaleResultString = $scaleResult .' '. ($short ? $scale[0] : $scale);
					if(!$short && $scaleResult > (int)1){
						$scaleResultString .= 's';
					}
					$output[] = $scaleResultString;
					$seconds = $seconds % $scaleInSeconds;
				}
			}
			//elseif( count($output) == 0 ){
			//	$output[] = round($seconds,2) .'s';
			//}
			return implode(' ', $output);
		}
	}

?>