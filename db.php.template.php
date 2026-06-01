<?php
$host = 'localhost';
$user = 'uStudentID';
$pass = 'asdASD123!'; //ur password
$db   = 'dbStudentID';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>