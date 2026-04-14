<?php
/*
 * ConsuTrade - Database Configuration
 * Author: Kamogelo Phale
 *
 */

$host = 'localhost';
$db_name = 'consutrade';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die('Database connection failed' .$conn->connect_error);
}

$conn->set_charset('utf8mb4');
