<?php
$servername = "localhost";
$username = "root"; // or your DB user
$password = "";     // your DB password
$database = "libos"; // make sure this is correct

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>