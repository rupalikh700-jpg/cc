<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'try_sex');
define('DB_PASS', 'try_sex');
define('DB_NAME', 'try_sex');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
