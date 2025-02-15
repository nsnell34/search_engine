<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("Database.php");

$database = new Database();
$conn = $database->conn;

try {
    $result = $conn->query("SHOW TABLES");
    $tables = [];

    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($tables as $table) {
        $conn->query("TRUNCATE TABLE `$table`");
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "<script>alert('Database Cleared');</script>";
    echo '<script>window.history.back();</script>';

} catch (Exception $e) {
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
}



