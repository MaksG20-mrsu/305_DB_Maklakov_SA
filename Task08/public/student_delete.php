<?php
require_once '../config.php';

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Получение данных студента
$stmt = $pdo->prepare("
    SELECT s.*, g.number as group_number
    FROM students s
    JOIN groups g ON s.group_id = g.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit;
}

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';

    if ($confirm === 'yes') {
        try {
            // Удаление студента (экзамены удалятся автоматически из-за CASCADE)
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении студента: ' . $e->getMessage();
        }
    } else {
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удалить студента</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Удаление студента</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="confirm-delete">
            <p><strong>Вы действительно хотите удалить этого студента?</strong></p>

            <div class="student-info">
                <p><strong>ФИО:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
                <p><strong>Пол:</strong> <?= htmlspecialchars($student['gender']) ?></p>
                <p><strong>Дата рождения:</strong> <?= date('d.m.Y', strtotime($student['birth_date'])) ?></p>
                <p><strong>Зачетная книжка:</strong> <?= htmlspecialchars($student['student_card_number']) ?></p>
            </div>

            <div class="alert alert-error">
                <strong>Внимание!</strong> Будут также удалены все результаты экзаменов этого студента!
            </div>

            <form method="POST" action="">
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" name="confirm" value="yes" class="btn btn-delete">
                        Да, удалить студента
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
