<?php


class Permission
{
	static public function checkAppPermission($app, $conn) {
		return FALSE;
//		if (isset($_COOKIE["SchemeUserCookie"])) {
//			$userCookie = $_COOKIE["SchemeUserCookie"];
//		} else {
//			return FALSE;
//		}
//
//		$sql = "
//	SELECT userId FROM Sessions WHERE sessionCookie = '$userCookie'
//	";
//
//		if (NULL === $user = $conn->query($sql)->fetch_assoc()) {
//			return FALSE;
//		}
//
//		$currentUserId = $user['userId'];
//
//		if (NULL === $conn->query("SELECT userId FROM Permissions WHERE userId = '$currentUserId' AND app = '$app'")->fetch_assoc()) {
//			return FALSE;
//		}
//		return TRUE;
	}
}