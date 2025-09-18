<?php
$DB_HOST = getenv('DB_HOST') ?: 'db';
$DB_NAME = getenv('DB_NAME') ?: 'garment_app';
$DB_USER = getenv('DB_USER') ?: 'garment';
$DB_PASS = getenv('DB_PASS') ?: 'garment_pass';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
$pdo = new PDO($dsn,$DB_USER,$DB_PASS,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);