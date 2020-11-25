<?php

$systemConfigs = array();
// only necessary if you want to install the Agile system in a sub folder, which requires extensive javascript special handling.
$systemConfigs["systemPath"] = $_SERVER['DOCUMENT_ROOT'].'/';
$systemConfigs["systemCorePath"] = $systemConfigs["systemPath"] ."Agile/";

$systemConfigs["systemTimeZone"] = "America/Chicago";

$systemConfigs["publicRoot"] = "/";
$systemConfigs["publicRootUrl"] = "http://" . $_SERVER["SERVER_NAME"] . $systemConfigs["publicRoot"];

$systemConfigs["desktopTopLeftLabel"] = "Agile Intranet";

$systemConfigs["database"] = array();
$systemConfigs["database"]["credFile"] = "m2m";
$systemConfigs["database"]["systemDatabase"] = "LeeSheet";
$systemConfigs["database"]["applicationLogDatabase"] = "LeeSheet";
$systemConfigs["databases"] = array(
	'LeeSheet'=>'LeeSheet'
);

$systemConfigs["printers"] = array(
//	array("Inventory Zebra 4x75","4x.75")
);

$systemConfigs["session"] = array();
$systemConfigs["session"]["cookieName"] = "Agile";
$systemConfigs["session"]["length"] = 3600 * 24 * 365; // (in seconds) = 1 year
$systemConfigs["session"]["cookiePath"] = $systemConfigs["publicRoot"];

//$systemConfigs["systemRouterConfigs"] = array();
//$systemConfigs["systemRouterConfigs"]["routerAppsPath"] = "DesktopApps";
$systemConfigs["router"] = array();
$systemConfigs["router"]["rootClass"] = "Links";
$systemConfigs["router"]["rootMethod"]  = "redirectToLinks";

$systemConfigs['table'] = array();
$systemConfigs['table']['users'] = $systemConfigs['database']['systemDatabase'] .'..User';
//$systemConfigs['table']['userCustomData'] = $systemConfigs['database']['systemDatabase'].'..userCustomData';
//$systemConfigs['table']['groups'] = $systemConfigs['database']['systemDatabase'] .'..groups';
//$systemConfigs['table']['userGroups'] = $systemConfigs['database']['systemDatabase'] .'..userGroups';
//$systemConfigs['table']['sessions'] = $systemConfigs['database']['systemDatabase'] .'..sessions';
//$systemConfigs['table']['groupPermissions'] = $systemConfigs['database']['systemDatabase'] .'..groupPermissions';
//$systemConfigs['table']['logAccess'] = $systemConfigs['database']['systemDatabase'] .'..logAccess';
//$systemConfigs['table']['logException'] = $systemConfigs['database']['systemDatabase'] .'..logException';

//$systemConfigs['exceptionEmailErrorTypes'] = array('error','shutdown','exception','useremail');

$systemConfigs['adminEmail'] = 'j-lee@fweco.net';