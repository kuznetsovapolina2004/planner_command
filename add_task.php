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

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Нет данных']);
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
$preferred_day = mysqli_real_escape_string($bd, $data['preferred_day'] ?? 'any'); // ДОБАВИТЬ ЭТО
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

// Вставляем задачу в БД (ДОБАВИТЬ preferred_day в запрос)
$sql = "INSERT INTO user_tasks (
    user_id, title, task_type, urgency, importance, 
    expected_duration_minutes, duration, deadline, 
    preferred_time, preferred_day, notes, weight, created_at
) VALUES (
    $user_id, '$title', '$task_type', $urgency, $importance,
    $expected_duration_minutes, $duration, " . ($deadline ? "'$deadline'" : "NULL") . ",
    '$preferred_time', '$preferred_day', '$description', $weight, NOW()
)";

if (mysqli_query($bd, $sql)) {
    echo json_encode(['success' => true, 'task_id' => mysqli_insert_id($bd)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . mysqli_error($bd)]);
}
?>