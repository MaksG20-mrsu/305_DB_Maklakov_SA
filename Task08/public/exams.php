<?php
require_once '../config.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Получение данных студента и его группы
$stmt = $pdo->prepare("
    SELECT s.*, g.number as group_number, g.start_year, g.major
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

// Получение списка экзаменов студента
$stmt = $pdo->prepare("
    SELECT e.*, d.name as discipline_name, d.course, d.semester, d.exam_type
    FROM exams e
    JOIN disciplines d ON e.discipline_id = d.id
    WHERE e.student_id = ?
    ORDER BY e.exam_date ASC
");
$stmt->execute([$student_id]);
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты экзаменов</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Результаты экзаменов студента</h1>

        <div class="student-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h2>Информация о студенте</h2>
            <p><strong>ФИО:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
            <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
            <p><strong>Зачетная книжка:</strong> <?= htmlspecialchars($student['student_card_number']) ?></p>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Вернуться к списку студентов</a>
            <a href="exam_add.php?student_id=<?= $student_id ?>" class="btn btn-primary">+ Добавить результат экзамена</a>
        </div>

        <?php if (count($exams) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дисциплина</th>
                        <th>Курс</th>
                        <th>Семестр</th>
                        <th>Тип</th>
                        <th>Дата</th>
                        <th>Оценка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($exam['discipline_name']) ?></td>
                            <td><?= $exam['course'] ?></td>
                            <td><?= $exam['semester'] ?></td>
                            <td>
                                <span style="font-size: 12px; padding: 2px 8px; border-radius: 4px; background: <?= $exam['exam_type'] === 'экзамен' ? '#e3f2fd' : '#f3e5f5' ?>; color: <?= $exam['exam_type'] === 'экзамен' ? '#1976d2' : '#7b1fa2' ?>">
                                    <?= htmlspecialchars($exam['exam_type']) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y', strtotime($exam['exam_date'])) ?></td>
                            <td>
                                <strong style="color: <?= in_array($exam['grade'], ['5', 'зачет']) ? 'green' :
                                    (in_array($exam['grade'], ['4']) ? 'blue' :
                                    (in_array($exam['grade'], ['3']) ? 'orange' : 'red')) ?>">
                                    <?= htmlspecialchars($exam['grade']) ?>
                                </strong>
                            </td>
                            <td class="actions-cell">
                                <a href="exam_edit.php?id=<?= $exam['id'] ?>&student_id=<?= $student_id ?>"
                                   class="btn btn-small btn-edit">Редактировать</a>
                                <a href="exam_delete.php?id=<?= $exam['id'] ?>&student_id=<?= $student_id ?>"
                                   class="btn btn-small btn-delete">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="total">Всего экзаменов сдано: <?= count($exams) ?></p>
        <?php else: ?>
            <p class="no-data">Нет результатов экзаменов для данного студента.</p>
        <?php endif; ?>
    </div>
</body>
</html>
