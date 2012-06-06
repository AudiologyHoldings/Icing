<?php
class IcingUtil {
	/**
	* Returns if request is HTTPS or not.
	* @return boolean true if HTTPS, false if not.
	*/
	public static function isHttps(){
		return (
			(isset($_SERVER['HTTP_X_REMOTE_PROTOCOL']) && !empty($_SERVER['HTTP_X_REMOTE_PROTOCOL'])) ||
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']==443));
	}
}
?>
