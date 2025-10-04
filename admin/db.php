<?php
// $servername = "localhost";
// $username   = "root";
// $password   = "yego"; 
// $dbname     = "agmsdb";

// // Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
$host = '165.22.120.164';
$dbname = 'newdb';
$username = 'algorithm_system';
$password = 'PaCFAO@!123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
