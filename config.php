<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = mysqli_connect("localhost", "root", "", "travel_db", 3307);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>