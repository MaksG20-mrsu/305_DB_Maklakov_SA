<?php
// Веб-интерфейс для инициализации базы данных

$host = '127.0.0.1';
$port = '5432';
$db   = 'university';
$user = 'postgres';
$pass = 'taekwondo';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['init'])) {
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);

        // Читаем SQL файл
        $sql = file_get_contents('../data/init_db.sql');

        // Выполняем SQL
        $pdo->exec($sql);

        $message = 'База данных успешно инициализирована!';

        // Проверяем созданные таблицы
        $stmt = $pdo->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            ORDER BY table_name
        ");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $message .= '<br><br>Созданные таблицы:<ul>';
        foreach ($tables as $table) {
            $message .= "<li>$table</li>";
        }
        $message .= '</ul>';

        // Подсчет записей
        $stmt = $pdo->query("SELECT COUNT(*) FROM groups");
        $groups_count = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $students_count = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM disciplines");
        $disciplines_count = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM exams");
        $exams_count = $stmt->fetchColumn();

        $message .= "<br>Данные:<ul>";
        $message .= "<li>Групп: $groups_count</li>";
        $message .= "<li>Студентов: $students_count</li>";
        $message .= "<li>Дисциплин: $disciplines_count</li>";
        $message .= "<li>Экзаменов: $exams_count</li>";
        $message .= "</ul>";

    } catch (PDOException $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Проверка состояния БД
$db_status = '';
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Проверяем наличие таблиц
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_name IN ('groups', 'students', 'disciplines', 'exams')
    ");
    $tables_exist = $stmt->fetchColumn();

    if ($tables_exist == 4) {
        $db_status = '<div class="alert alert-success">✓ База данных инициализирована. Все таблицы существуют.</div>';
    } else if ($tables_exist > 0) {
        $db_status = '<div class="alert alert-error">⚠ База данных частично инициализирована (найдено таблиц: ' . $tables_exist . ' из 4).</div>';
    } else {
        $db_status = '<div class="alert alert-info">База данных не инициализирована. Нажмите кнопку ниже для инициализации.</div>';
    }

} catch (PDOException $e) {
    $db_status = '<div class="alert alert-error">Ошибка подключения к БД: ' . $e->getMessage() . '</div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Инициализация БД</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Инициализация базы данных</h1>

        <?= $db_status ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <br>
            <a href="index.php" class="btn btn-primary">Перейти к приложению</a>
        <?php else: ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Что будет сделано:</h2>
                <ul>
                    <li>Удаление существующих таблиц (если есть)</li>
                    <li>Создание таблиц: groups, students, disciplines, exams</li>
                    <li>Загрузка тестовых данных:
                        <ul>
                            <li>2 группы (305(1), 305(2))</li>
                            <li>25 студентов</li>
                            <li>66 дисциплин из учебного плана</li>
                            <li>Примеры экзаменов</li>
                        </ul>
                    </li>
                    <li>Создание индексов для оптимизации</li>
                </ul>
            </div>

            <form method="POST" action="">
                <div style="text-align: center; margin: 30px 0;">
                    <button type="submit" name="init" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px;">
                        Инициализировать базу данных
                    </button>
                </div>
            </form>

            <div class="alert alert-error" style="margin-top: 20px;">
                <strong>Внимание!</strong> Все существующие данные будут удалены!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
