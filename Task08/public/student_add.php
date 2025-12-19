<?php
require_once '../config.php';

$error = '';
$success = '';

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
        // Проверка уникальности номера зачетки
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_card_number = ?");
        $stmt->execute([$student_card_number]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Студент с таким номером зачетной книжки уже существует!';
        } else {
            // Вставка данных
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO students (group_id, full_name, gender, birth_date, student_card_number, subgroup)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$group_id, $full_name, $gender, $birth_date, $student_card_number, $subgroup]);
                $success = 'Студент успешно добавлен!';

                // Перенаправление через 2 секунды
                header("refresh:2;url=index.php");
            } catch (PDOException $e) {
                $error = 'Ошибка при добавлении студента: ' . $e->getMessage();
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
    <title>Добавить студента</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Добавить нового студента</h1>

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
                    <option value="">Выберите группу</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>"
                            <?= isset($_POST['group_id']) && $_POST['group_id'] == $group['id'] ? 'selected' : '' ?>>
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
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                       placeholder="Иванов Иван Иванович"
                       required>
            </div>

            <div class="form-group">
                <label>Пол *</label>
                <div class="radio-group">
                    <label>
                        <input type="radio"
                               name="gender"
                               value="М"
                               <?= (!isset($_POST['gender']) || $_POST['gender'] === 'М') ? 'checked' : '' ?>
                               required>
                        Мужской
                    </label>
                    <label>
                        <input type="radio"
                               name="gender"
                               value="Ж"
                               <?= (isset($_POST['gender']) && $_POST['gender'] === 'Ж') ? 'checked' : '' ?>
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
                       value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="student_card_number">Номер зачетной книжки *</label>
                <input type="text"
                       name="student_card_number"
                       id="student_card_number"
                       value="<?= htmlspecialchars($_POST['student_card_number'] ?? '') ?>"
                       placeholder="231544"
                       required>
            </div>

            <div class="form-group">
                <label for="subgroup">Подгруппа *</label>
                <select name="subgroup" id="subgroup" required>
                    <option value="1" <?= (!isset($_POST['subgroup']) || $_POST['subgroup'] == 1) ? 'selected' : '' ?>>1</option>
                    <option value="2" <?= (isset($_POST['subgroup']) && $_POST['subgroup'] == 2) ? 'selected' : '' ?>>2</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary">Добавить студента</button>
            </div>
        </form>
    </div>
</body>
</html>
