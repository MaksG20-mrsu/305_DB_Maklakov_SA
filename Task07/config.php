<?php
// Настройки подключения к PostgreSQL
$host = '127.0.0.1';
$port = '5432';
$db   = 'university';
$user = 'postgres';
$pass = 'taekwondo';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage() . "\n\nПроверьте:\n1. Запущена ли PostgreSQL\n2. Правильный ли пароль в config.php\n3. Создана ли БД university\n4. Установлен ли PDO драйвер для PostgreSQL");
}
?>
