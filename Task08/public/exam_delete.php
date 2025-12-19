<?php
require_once '../config.php';

$exam_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

if (!$exam_id || !$student_id) {
    header("Location: index.php");
    exit;
}

// Получение данных экзамена
$stmt = $pdo->prepare("
    SELECT e.*, d.name as discipline_name, d.course, d.semester,
           s.full_name as student_name
    FROM exams e
    JOIN disciplines d ON e.discipline_id = d.id
    JOIN students s ON e.student_id = s.id
    WHERE e.id = ? AND e.student_id = ?
");
$stmt->execute([$exam_id, $student_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header("Location: index.php");
    exit;
}

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';

    if ($confirm === 'yes') {
        try {
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$exam_id]);

            header("Location: exams.php?student_id=$student_id");
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка при удалении результата экзамена: ' . $e->getMessage();
        }
    } else {
        header("Location: exams.php?student_id=$student_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удалить результат экзамена</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Удаление результата экзамена</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="confirm-delete">
            <p><strong>Вы действительно хотите удалить этот результат экзамена?</strong></p>

            <div class="student-info">
                <p><strong>Студент:</strong> <?= htmlspecialchars($exam['student_name']) ?></p>
                <p><strong>Дисциплина:</strong> <?= htmlspecialchars($exam['discipline_name']) ?></p>
                <p><strong>Курс:</strong> <?= $exam['course'] ?>, <strong>Семестр:</strong> <?= $exam['semester'] ?></p>
                <p><strong>Дата экзамена:</strong> <?= date('d.m.Y', strtotime($exam['exam_date'])) ?></p>
                <p><strong>Оценка:</strong> <span style="font-size: 18px; font-weight: bold; color: <?= in_array($exam['grade'], ['5', 'зачет']) ? 'green' : (in_array($exam['grade'], ['4']) ? 'blue' : (in_array($exam['grade'], ['3']) ? 'orange' : 'red')) ?>"><?= htmlspecialchars($exam['grade']) ?></span></p>
            </div>

            <form method="POST" action="">
                <div class="form-actions">
                    <a href="exams.php?student_id=<?= $student_id ?>" class="btn btn-secondary">Отмена</a>
                    <button type="submit" name="confirm" value="yes" class="btn btn-delete">
                        Да, удалить результат
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
