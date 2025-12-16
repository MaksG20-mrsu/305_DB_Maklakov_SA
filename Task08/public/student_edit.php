<?php
require_once '../config.php';

$error = '';
$success = '';
$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Получение данных студента
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit;
}

// Получение списка групп
$stmt = $pdo->query("SELECT id, number FROM groups ORDER BY number");
$groups = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $student_card_number = trim($_POST['student_card_number'] ?? '');
    $subgroup = $_POST['subgroup'] ?? 1;

    // Валидация
    if (empty($group_id) || empty($full_name) || empty($gender) ||
        empty($birth_date) || empty($student_card_number)) {
        $error = 'Все поля обязательны для заполнения!';
    } else {
        // Проверка уникальности номера зачетки (кроме текущего студента)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_card_number = ? AND id != ?");
        $stmt->execute([$student_card_number, $student_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Студент с таким номером зачетной книжки уже существует!';
        } else {
            // Обновление данных
            try {
                $stmt = $pdo->prepare(
                    "UPDATE students
                     SET group_id = ?, full_name = ?, gender = ?, birth_date = ?,
                         student_card_number = ?, subgroup = ?
                     WHERE id = ?"
                );
                $stmt->execute([$group_id, $full_name, $gender, $birth_date,
                               $student_card_number, $subgroup, $student_id]);
                $success = 'Данные студента успешно обновлены!';

                // Обновляем данные студента для отображения
                $student = [
                    'id' => $student_id,
                    'group_id' => $group_id,
                    'full_name' => $full_name,
                    'gender' => $gender,
                    'birth_date' => $birth_date,
                    'student_card_number' => $student_card_number,
                    'subgroup' => $subgroup
                ];

                // Перенаправление через 2 секунды
                header("refresh:2;url=index.php");
            } catch (PDOException $e) {
                $error = 'Ошибка при обновлении данных: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать студента</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Редактировать данные студента</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="group_id">Группа *</label>
                <select name="group_id" id="group_id" required>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>"
                            <?= $student['group_id'] == $group['id'] ? 'selected' : '' ?>>
                            Группа <?= htmlspecialchars($group['number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="full_name">ФИО *</label>
                <input type="text"
                       name="full_name"
                       id="full_name"
                       value="<?= htmlspecialchars($student['full_name']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Пол *</label>
                <div class="radio-group">
                    <label>
                        <input type="radio"
                               name="gender"
                               value="М"
                               <?= $student['gender'] === 'М' ? 'checked' : '' ?>
                               required>
                        Мужской
                    </label>
                    <label>
                        <input type="radio"
                               name="gender"
                               value="Ж"
                               <?= $student['gender'] === 'Ж' ? 'checked' : '' ?>
                               required>
                        Женский
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="birth_date">Дата рождения *</label>
                <input type="date"
                       name="birth_date"
                       id="birth_date"
                       value="<?= htmlspecialchars($student['birth_date']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="student_card_number">Номер зачетной книжки *</label>
                <input type="text"
                       name="student_card_number"
                       id="student_card_number"
                       value="<?= htmlspecialchars($student['student_card_number']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="subgroup">Подгруппа *</label>
                <select name="subgroup" id="subgroup" required>
                    <option value="1" <?= $student['subgroup'] == 1 ? 'selected' : '' ?>>1</option>
                    <option value="2" <?= $student['subgroup'] == 2 ? 'selected' : '' ?>>2</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</body>
</html>
