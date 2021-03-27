<?php
$servername = "localhost";
$username = "johntheadmin";
$password = "Echo120499!";
$dbname = "Scheme";
$conn = new mysqli($servername, $username, $password, $dbname);

if (isset($_COOKIE["SchemeUserCookie"])) {
	$userCookie = $_COOKIE["SchemeUserCookie"];
} else {
	echo json_encode(array(
		'success' => false
	));
	return;
}
$sql = "
	SELECT userId FROM Sessions WHERE sessionCookie = '$userCookie'
";

if(NULL === $user = $conn->query($sql)->fetch_assoc()) {
	echo json_encode(array(
		'success' => false
	));
	return;
}

$currentUserId = $user['userId'];
$app = $_POST['app'];
if(NULL === $conn->query("SELECT userId FROM Permissions WHERE userId = '$currentUserId' AND app = '$app'")->fetch_assoc()) {
	echo json_encode(array(
		'success' => false
	));
	return;
}
echo json_encode(array(
	'success' => true
));