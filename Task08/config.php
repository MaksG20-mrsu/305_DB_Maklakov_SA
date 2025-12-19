<?php
// Конфигурация подключения к PostgreSQL
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
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Функция для получения текущего курса и семестра студента
function getStudentCurrentSemester($start_year) {
    $current_year = date('Y');
    $current_month = date('n');

    $years_passed = $current_year - $start_year;

    // Если месяц >= сентября (9), это нечетный семестр, иначе четный
    if ($current_month >= 9) {
        $semester = $years_passed * 2 + 1;
    } else {
        $semester = $years_passed * 2;
    }

    // Семестры: 1-8 (4 года)
    if ($semester < 1) $semester = 1;
    if ($semester > 8) $semester = 8;

    $course = ceil($semester / 2);

    return ['course' => $course, 'semester' => $semester];
}

// Функция для безопасного редиректа
function redirect($url) {
    header("Location: $url");
    exit;
}

// Функция для вывода сообщений об ошибках/успехе
function showMessage($message, $type = 'info') {
    $class = $type === 'error' ? 'alert-error' : 'alert-success';
    return "<div class='alert $class'>$message</div>";
}
?>
