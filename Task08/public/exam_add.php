<?php
require_once '../config.php';

$student_id = $_GET['student_id'] ?? null;
$error = '';
$success = '';

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

// Определение текущего курса и семестра студента
$current_info = getStudentCurrentSemester($student['start_year']);
$current_course = $current_info['course'];

// Получение дисциплин (только до текущего курса включительно, с учетом направления)
$stmt = $pdo->prepare("
    SELECT id, name, course, semester, exam_type
    FROM disciplines
    WHERE major = ? AND course <= ?
    ORDER BY course, semester, name
");
$stmt->execute([$student['major'], $current_course]);
$disciplines = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discipline_id = $_POST['discipline_id'] ?? '';
    $exam_date = $_POST['exam_date'] ?? '';
    $grade = $_POST['grade'] ?? '';

    // Валидация
    if (empty($discipline_id) || empty($exam_date) || empty($grade)) {
        $error = 'Все поля обязательны для заполнения!';
    } else {
        // Проверка на дублирование (один студент не может сдавать одну дисциплину дважды в один день)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exams
            WHERE student_id = ? AND discipline_id = ? AND exam_date = ?
        ");
        $stmt->execute([$student_id, $discipline_id, $exam_date]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Результат экзамена по этой дисциплине на эту дату уже существует!';
        } else {
            // Вставка данных
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO exams (student_id, discipline_id, exam_date, grade)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$student_id, $discipline_id, $exam_date, $grade]);
                $success = 'Результат экзамена успешно добавлен!';

                // Перенаправление через 2 секунды
                header("refresh:2;url=exams.php?student_id=$student_id");
            } catch (PDOException $e) {
                $error = 'Ошибка при добавлении результата: ' . $e->getMessage();
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
    <title>Добавить результат экзамена</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Добавить результат экзамена</h1>

        <div class="student-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p><strong>Студент:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
            <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
            <p><strong>Текущий курс:</strong> <?= $current_course ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="discipline_id">Дисциплина *</label>
                <select name="discipline_id" id="discipline_id" required>
                    <option value="">Выберите дисциплину</option>
                    <?php
                    $prev_course = null;
                    foreach ($disciplines as $discipline):
                        if ($prev_course !== $discipline['course']):
                            if ($prev_course !== null): ?>
                                </optgroup>
                            <?php endif; ?>
                            <optgroup label="Курс <?= $discipline['course'] ?> (семестры <?= $discipline['course']*2-1 ?>, <?= $discipline['course']*2 ?>)">
                            <?php $prev_course = $discipline['course'];
                        endif;
                    ?>
                        <option value="<?= $discipline['id'] ?>"
                            data-exam-type="<?= htmlspecialchars($discipline['exam_type']) ?>"
                            <?= isset($_POST['discipline_id']) && $_POST['discipline_id'] == $discipline['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($discipline['name']) ?> (сем. <?= $discipline['semester'] ?>) [<?= $discipline['exam_type'] ?>]
                        </option>
                    <?php endforeach; ?>
                    <?php if ($prev_course !== null): ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <small style="color: #666;">Доступны дисциплины с 1 по <?= $current_course ?> курс (возможен ввод задним числом)</small>
            </div>

            <div class="form-group">
                <label for="exam_date">Дата экзамена *</label>
                <input type="date"
                       name="exam_date"
                       id="exam_date"
                       value="<?= htmlspecialchars($_POST['exam_date'] ?? date('Y-m-d')) ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="grade">Оценка *</label>
                <select name="grade" id="grade" required>
                    <option value="">Сначала выберите дисциплину</option>
                    <optgroup label="Оценки за экзамен" id="exam-grades" style="display: none;">
                        <option value="5" <?= isset($_POST['grade']) && $_POST['grade'] === '5' ? 'selected' : '' ?>>5 (отлично)</option>
                        <option value="4" <?= isset($_POST['grade']) && $_POST['grade'] === '4' ? 'selected' : '' ?>>4 (хорошо)</option>
                        <option value="3" <?= isset($_POST['grade']) && $_POST['grade'] === '3' ? 'selected' : '' ?>>3 (удовлетворительно)</option>
                        <option value="2" <?= isset($_POST['grade']) && $_POST['grade'] === '2' ? 'selected' : '' ?>>2 (неудовлетворительно)</option>
                    </optgroup>
                    <optgroup label="Оценки за зачет" id="zachet-grades" style="display: none;">
                        <option value="зачет" <?= isset($_POST['grade']) && $_POST['grade'] === 'зачет' ? 'selected' : '' ?>>Зачет</option>
                        <option value="незачет" <?= isset($_POST['grade']) && $_POST['grade'] === 'незачет' ? 'selected' : '' ?>>Незачет</option>
                    </optgroup>
                </select>
                <small id="grade-hint" style="color: #666; display: none;"></small>
            </div>

            <div class="form-actions">
                <a href="exams.php?student_id=<?= $student_id ?>" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary">Добавить результат</button>
            </div>
        </form>
    </div>

    <script>
        // Динамическое обновление списка оценок в зависимости от типа экзамена
        const disciplineSelect = document.getElementById('discipline_id');
        const gradeSelect = document.getElementById('grade');
        const gradeHint = document.getElementById('grade-hint');
        const examGrades = document.getElementById('exam-grades');
        const zachetGrades = document.getElementById('zachet-grades');

        disciplineSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const examType = selectedOption.getAttribute('data-exam-type');

            // Сброс выбранной оценки
            gradeSelect.value = '';

            if (examType === 'экзамен') {
                // Показываем оценки для экзамена
                examGrades.style.display = '';
                zachetGrades.style.display = 'none';
                gradeSelect.options[0].text = 'Выберите оценку';
                gradeHint.textContent = 'Выберите оценку от 2 до 5';
                gradeHint.style.display = 'block';
            } else if (examType === 'зачет') {
                // Показываем оценки для зачета
                examGrades.style.display = 'none';
                zachetGrades.style.display = '';
                gradeSelect.options[0].text = 'Выберите результат';
                gradeHint.textContent = 'Выберите зачет или незачет';
                gradeHint.style.display = 'block';
            } else {
                // Скрываем все оценки
                examGrades.style.display = 'none';
                zachetGrades.style.display = 'none';
                gradeSelect.options[0].text = 'Сначала выберите дисциплину';
                gradeHint.style.display = 'none';
            }
        });

        // Инициализация при загрузке страницы (если дисциплина уже выбрана)
        if (disciplineSelect.value) {
            disciplineSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>
