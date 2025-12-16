<?php
require_once 'config.php';

if (!function_exists('mb_str_pad')) {
    function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
        $diff = strlen($input) - mb_strlen($input);
        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }
}

function printLine($cols) {
    echo "+";
    foreach ($cols as $width) {
        echo str_repeat("-", $width + 2) . "+";
    }
    echo PHP_EOL;
}

// Получаем список активных групп
$currentYear = date('Y');
$stmt = $pdo->prepare("SELECT number FROM groups WHERE grad_year >= ? ORDER BY number");
$stmt->execute([$currentYear]);
$groups = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Выводим меню
echo "========================================" . PHP_EOL;
echo "  СПИСОК СТУДЕНТОВ ГРУППЫ 305" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo PHP_EOL;
echo "Доступные группы:" . PHP_EOL;
foreach ($groups as $grp) {
    echo "  - $grp" . PHP_EOL;
}
echo PHP_EOL;
echo "Введите номер группы или нажмите Enter для всех: ";

// Читаем ввод
$stdin = fopen('php://stdin', 'r');
$inputGroup = trim(fgets($stdin));
$filterGroup = null;

// Валидация
if ($inputGroup !== '') {
    if (in_array($inputGroup, $groups)) {
        $filterGroup = $inputGroup;
        echo PHP_EOL . "Фильтр: группа $filterGroup" . PHP_EOL;
    } else {
        echo PHP_EOL . "ОШИБКА: Группа '$inputGroup' не найдена!" . PHP_EOL;
        exit(1);
    }
} else {
    echo PHP_EOL . "Показаны все группы" . PHP_EOL;
}

echo PHP_EOL;

// Получаем студентов
$sql = "SELECT g.number, g.major, s.subgroup, s.full_name, s.gender, s.birth_date, s.student_card_number
        FROM students s
        JOIN groups g ON s.group_id = g.id
        WHERE g.grad_year >= :year";

$params = ['year' => $currentYear];

if ($filterGroup) {
    $sql .= " AND g.number = :grp";
    $params['grp'] = $filterGroup;
}

$sql .= " ORDER BY g.number, s.subgroup, s.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

if (empty($students)) {
    echo "Студенты не найдены." . PHP_EOL;
    exit(0);
}

// Структура таблицы (направление убрано, т.к. везде одинаковое)
$cols = [
    'Группа'     => 8,
    'П/г'        => 3,
    'ФИО'        => 36,
    'Пол'        => 3,
    'Дата рожд'  => 10,
    'Зачетка'    => 8
];

// Выводим заголовок один раз
echo "Направление: 09.03.04 Программная инженерия" . PHP_EOL;
echo PHP_EOL;

// Рисуем таблицу
printLine($cols);
echo "|";
foreach ($cols as $title => $width) {
    echo " " . mb_str_pad($title, $width) . " |";
}
echo PHP_EOL;
printLine($cols);

// Выводим строки
foreach ($students as $row) {
    echo "|";
    echo " " . mb_str_pad($row['number'], $cols['Группа']) . " |";
    echo " " . mb_str_pad($row['subgroup'], $cols['П/г']) . " |";
    echo " " . mb_str_pad($row['full_name'], $cols['ФИО']) . " |";
    echo " " . mb_str_pad($row['gender'], $cols['Пол']) . " |";
    echo " " . mb_str_pad($row['birth_date'], $cols['Дата рожд']) . " |";
    echo " " . mb_str_pad($row['student_card_number'], $cols['Зачетка']) . " |";
    echo PHP_EOL;
}
printLine($cols);

echo PHP_EOL;
echo "Всего студентов: " . count($students) . PHP_EOL;
?>
