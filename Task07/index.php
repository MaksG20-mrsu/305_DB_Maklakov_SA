<?php
require_once 'config.php';

$currentYear = date('Y');

$stmtGroups = $pdo->prepare("SELECT id, number FROM groups WHERE grad_year >= ? ORDER BY number");
$stmtGroups->execute([$currentYear]);
$groupList = $stmtGroups->fetchAll();

$selectedId = $_GET['group_id'] ?? 'all';

$sql = "SELECT g.number, g.major, s.full_name, s.gender, s.birth_date, s.student_card_number, s.subgroup
        FROM students s
        JOIN groups g ON s.group_id = g.id
        WHERE g.grad_year >= :year";

$params = ['year' => $currentYear];

if ($selectedId !== 'all') {
    $sql .= " AND g.id = :gid";
    $params['gid'] = $selectedId;
}

$sql .= " ORDER BY g.number, s.subgroup, s.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа 7</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f4f9; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h1 { text-align: center; color: #333; }
        .controls { margin-bottom: 20px; text-align: center; padding: 15px; background: #e9ecef; border-radius: 5px; }
        select { padding: 8px; font-size: 16px; border-radius: 4px; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; text-align: center; }
        .sub-1 { background-color: #17a2b8; } /* Цвет для 1 подгруппы */
        .sub-2 { background-color: #28a745; } /* Цвет для 2 подгруппы */
    </style>
</head>
<body>

<div class="container">
    <h1>Список студентов</h1>

    <div class="controls">
        <form action="" method="GET">
            <label for="group_selector">Фильтр по группе: </label>
            <select name="group_id" id="group_selector" onchange="this.form.submit()">
                <option value="all">Показать всех</option>
                <?php foreach ($groupList as $grp): ?>
                    <option value="<?= htmlspecialchars($grp['id']) ?>" 
                        <?= $selectedId == $grp['id'] ? 'selected' : '' ?>>
                        Группа <?= htmlspecialchars($grp['number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (count($students) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Группа</th>
                    <th>Направление подготовки</th>
                    <th>П/г</th>
                    <th>ФИО Студента</th>
                    <th>Пол</th>
                    <th>Дата рождения</th>
                    <th>Зачетная книжка</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['number']) ?></td>
                        <td><?= htmlspecialchars($s['major']) ?></td>
                        <td>
                            <span class="badge sub-<?= $s['subgroup'] ?>">
                                <?= $s['subgroup'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><?= $s['gender'] ?></td>
                        <td><?= date('d.m.Y', strtotime($s['birth_date'])) ?></td>
                        <td><?= htmlspecialchars($s['student_card_number']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center">Студентов не найдено.</p>
    <?php endif; ?>
</div>

</body>
</html>