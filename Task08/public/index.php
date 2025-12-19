<?php
require_once '../config.php';

// Получение списка групп для фильтра
$stmt = $pdo->query("SELECT id, number FROM groups ORDER BY number");
$groups = $stmt->fetchAll();

// Фильтрация по группе
$selectedGroupId = $_GET['group_id'] ?? 'all';

// Формирование SQL-запроса
$sql = "SELECT s.*, g.number as group_number
        FROM students s
        JOIN groups g ON s.group_id = g.id";

$params = [];

if ($selectedGroupId !== 'all') {
    $sql .= " WHERE s.group_id = ?";
    $params[] = $selectedGroupId;
}

$sql .= " ORDER BY g.number, s.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа 8 - Список студентов</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Список студентов группы 305</h1>

        <!-- Фильтр по группе -->
        <div class="filter-panel">
            <form method="GET" action="">
                <label for="group_filter">Фильтр по группе:</label>
                <select name="group_id" id="group_filter" onchange="this.form.submit()">
                    <option value="all" <?= $selectedGroupId === 'all' ? 'selected' : '' ?>>Все группы</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>"
                            <?= $selectedGroupId == $group['id'] ? 'selected' : '' ?>>
                            Группа <?= htmlspecialchars($group['number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Кнопка добавления студента -->
        <div class="actions">
            <a href="student_add.php" class="btn btn-primary">+ Добавить студента</a>
        </div>

        <!-- Таблица студентов -->
        <?php if (count($students) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Группа</th>
                        <th>ФИО</th>
                        <th>Пол</th>
                        <th>Дата рождения</th>
                        <th>Зачетная книжка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($student['group_number']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['gender']) ?></td>
                            <td><?= date('d.m.Y', strtotime($student['birth_date'])) ?></td>
                            <td><?= htmlspecialchars($student['student_card_number']) ?></td>
                            <td class="actions-cell">
                                <a href="student_edit.php?id=<?= $student['id'] ?>"
                                   class="btn btn-small btn-edit">Редактировать</a>
                                <a href="student_delete.php?id=<?= $student['id'] ?>"
                                   class="btn btn-small btn-delete">Удалить</a>
                                <a href="exams.php?student_id=<?= $student['id'] ?>"
                                   class="btn btn-small btn-info">Результаты экзаменов</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="total">Всего студентов: <?= count($students) ?></p>
        <?php else: ?>
            <p class="no-data">Студенты не найдены.</p>
        <?php endif; ?>
    </div>
</body>
</html>
