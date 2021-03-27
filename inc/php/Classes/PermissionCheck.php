<?php

namespace inc\php\classes;

class PermissionCheck
{
    static function checkPermission($userId, $app) {
        $servername = "localhost";
        $username = "johntheadmin";
        $password = "Echo120499!";
        $dbname = "Scheme";
        $conn = new mysqli($servername, $username, $password, $dbname);

//        $database = new sqlsrv_helper();
////        $database->select("Scheme..Permissions", array('userId'), array('userId' => $userId, 'app' => $app));

        return $userId;
        if(NULL === $conn->query("SELECT userId FROM Permissions WHERE userId = '$userId' AND app = '$app'")->fetch_assoc()) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}