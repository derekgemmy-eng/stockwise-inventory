<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'stockwise';
$username = getenv('DB_USER') ?: 'stockwise_user';
$password = getenv('DB_PASSWORD') ?: 'stockwise_password';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    error_log('Database connection failed: ' . $exception->getMessage());
    exit('Koneksi database gagal. Pastikan layanan Docker aktif dan periksa log aplikasi.');
}
