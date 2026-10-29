<?php
// Safe configuration: set these environment variables on your deployment server.
// Do not commit database passwords or API keys.
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: '';
define('GOOGLE_VISION_KEY', getenv('GOOGLE_VISION_KEY') ?: '');
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) { die('Database connection failed.'); }
$conn->set_charset('utf8mb4');
