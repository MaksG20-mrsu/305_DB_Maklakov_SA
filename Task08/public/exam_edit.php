<?php
require_once '../config.php';

$exam_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$error = '';
$success = '';

if (!$exam_id || !$student_id) {
    header("Location: index.php");
    exit;
}

// Получение данных экзамена
$stmt = $pdo->prepare("
    SELECT e.*, d.name as discipline_name
    FROM exams e
    JOIN disciplines d ON e.discipline_id = d.id
    WHERE e.id = ? AND e.student_id = ?
");
$stmt->execute([$exam_id, $student_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header("Location: index.php");
    exit;
}

// Получение данных студента
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

// Определение текущего курса студента
$current_info = getStudentCurrentSemester($student['start_year']);
$current_course = $current_info['course'];

// Получение дисциплин
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
        // Проверка на дублирование (кроме текущего экзамена)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exams
            WHERE student_id = ? AND discipline_id = ? AND exam_date = ? AND id != ?
        ");
        $stmt->execute([$student_id, $discipline_id, $exam_date, $exam_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Результат экзамена по этой дисциплине на эту дату уже существует!';
        } else {
            // Обновление данных
            try {
                $stmt = $pdo->prepare("
                    UPDATE exams
                    SET discipline_id = ?, exam_date = ?, grade = ?
                    WHERE id = ?
                ");
                $stmt->execute([$discipline_id, $exam_date, $grade, $exam_id]);
                $success = 'Результат экзамена успешно обновлен!';

                // Обновляем данные экзамена
                $exam['discipline_id'] = $discipline_id;
                $exam['exam_date'] = $exam_date;
                $exam['grade'] = $grade;

                // Перенаправление через 2 секунды
                header("refresh:2;url=exams.php?student_id=$student_id");
            } catch (PDOException $e) {
                $error = 'Ошибка при обновлении результата: ' . $e->getMessage();
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
    <title>Редактировать результат экзамена</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Редактировать результат экзамена</h1>

        <div class="student-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p><strong>Студент:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
            <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
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
                            <?= $exam['discipline_id'] == $discipline['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($discipline['name']) ?> (сем. <?= $discipline['semester'] ?>) [<?= $discipline['exam_type'] ?>]
                        </option>
                    <?php endforeach; ?>
                    <?php if ($prev_course !== null): ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="exam_date">Дата экзамена *</label>
                <input type="date"
                       name="exam_date"
                       id="exam_date"
                       value="<?= htmlspecialchars($exam['exam_date']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="grade">Оценка *</label>
                <select name="grade" id="grade" required>
                    <option value="">Выберите оценку</option>
                    <optgroup label="Оценки за экзамен" id="exam-grades" style="display: none;">
                        <option value="5" <?= $exam['grade'] === '5' ? 'selected' : '' ?>>5 (отлично)</option>
                        <option value="4" <?= $exam['grade'] === '4' ? 'selected' : '' ?>>4 (хорошо)</option>
                        <option value="3" <?= $exam['grade'] === '3' ? 'selected' : '' ?>>3 (удовлетворительно)</option>
                        <option value="2" <?= $exam['grade'] === '2' ? 'selected' : '' ?>>2 (неудовлетворительно)</option>
                    </optgroup>
                    <optgroup label="Оценки за зачет" id="zachet-grades" style="display: none;">
                        <option value="зачет" <?= $exam['grade'] === 'зачет' ? 'selected' : '' ?>>Зачет</option>
                        <option value="незачет" <?= $exam['grade'] === 'незачет' ? 'selected' : '' ?>>Незачет</option>
                    </optgroup>
                </select>
                <small id="grade-hint" style="color: #666; display: none;"></small>
            </div>

            <div class="form-actions">
                <a href="exams.php?student_id=<?= $student_id ?>" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
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

        function updateGradeOptions() {
            const selectedOption = disciplineSelect.options[disciplineSelect.selectedIndex];
            const examType = selectedOption.getAttribute('data-exam-type');

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
        }

        disciplineSelect.addEventListener('change', function() {
            // Сброс выбранной оценки при смене дисциплины
            const currentGrade = gradeSelect.value;
            updateGradeOptions();
            // Если текущая оценка подходит для нового типа, оставляем её
            const validOptions = Array.from(gradeSelect.options).filter(opt =>
                opt.value && opt.parentElement.style.display !== 'none'
            );
            if (!validOptions.some(opt => opt.value === currentGrade)) {
                gradeSelect.value = '';
            } else {
                gradeSelect.value = currentGrade;
            }
        });

        // Инициализация при загрузке страницы
        if (disciplineSelect.value) {
            updateGradeOptions();
        }
    </script>
</body>
</html>
