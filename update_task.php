<?php
session_start();
require_once 'bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['task_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID задачи']);
    exit;
}

$task_id = intval($data['task_id']);

// Проверяем, принадлежит ли задача пользователю
$check_sql = "SELECT id FROM user_tasks WHERE id = $task_id AND user_id = $user_id";
$check_result = mysqli_query($bd, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Задача не найдена или доступ запрещен']);
    exit;
}

// Подготовка данных
$title = mysqli_real_escape_string($bd, $data['name'] ?? '');
$task_type = mysqli_real_escape_string($bd, $data['type'] ?? '');
$urgency = intval($data['urgency'] ?? 4);
$importance = intval($data['importance'] ?? 5);
$duration = floatval($data['duration'] ?? 1.0);
$expected_duration_minutes = intval($duration * 60);
$deadline = !empty($data['deadline']) ? mysqli_real_escape_string($bd, $data['deadline']) : null;
$preferred_time = mysqli_real_escape_string($bd, $data['preferred_time'] ?? 'any');
$preferred_day = mysqli_real_escape_string($bd, $data['preferred_day'] ?? 'any');
$description = mysqli_real_escape_string($bd, $data['description'] ?? '');

// Получаем энергозатратность типа задачи
$energy_sql = "SELECT energy_level FROM user_task_energy WHERE user_id = $user_id AND task_type = '$task_type'";
$energy_result = mysqli_query($bd, $energy_sql);
$energy_row = mysqli_fetch_assoc($energy_result);
$energy_level = $energy_row['energy_level'] ?? 5;
$energy_coefficient = $energy_level / 10.0;

// Рассчитываем вес задачи
$weight = ($urgency + $importance) * $energy_coefficient;

// Добавляем баллы за длительность
$duration_score = 1;
if ($expected_duration_minutes > 30 && $expected_duration_minutes <= 120) {
    $duration_score = 3;
} elseif ($expected_duration_minutes > 120) {
    $duration_score = 6;
}
$weight += $duration_score;
$weight = round($weight, 1);

// Обновляем задачу в БД
$sql = "UPDATE user_tasks SET 
    title = '$title',
    task_type = '$task_type',
    urgency = $urgency,
    importance = $importance,
    expected_duration_minutes = $expected_duration_minutes,
    duration = $duration,
    deadline = " . ($deadline ? "'$deadline'" : "NULL") . ",
    preferred_time = '$preferred_time',
    preferred_day = '$preferred_day',
    notes = '$description',
    weight = $weight,
    updated_at = NOW()
    WHERE id = $task_id AND user_id = $user_id";

if (mysqli_query($bd, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . mysqli_error($bd)]);
}
?>