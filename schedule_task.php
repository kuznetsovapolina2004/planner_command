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

if (!isset($data['task_id']) || !isset($data['day']) || !isset($data['time'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

$task_id = intval($data['task_id']);
$day = mysqli_real_escape_string($bd, $data['day']);
$time = mysqli_real_escape_string($bd, $data['time']);

// Определяем дату недели
$week_start = $data['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
if (!strtotime($week_start)) {
    $week_start = date('Y-m-d', strtotime('monday this week'));
}

// Конвертируем день недели в дату
$day_map = [
    'monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 
    'thursday' => 3, 'friday' => 4, 'saturday' => 5, 'sunday' => 6
];
$day_offset = $day_map[$day] ?? 0;
$scheduled_date = date('Y-m-d', strtotime("$week_start +$day_offset days"));

// 1. Получаем информацию о задаче
$task_sql = "SELECT weight, duration FROM user_tasks WHERE id = $task_id AND user_id = $user_id";
$task_result = mysqli_query($bd, $task_sql);
$task = mysqli_fetch_assoc($task_result);

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Задача не найдена']);
    exit;
}

$task_weight = floatval($task['weight']);

// 2. Проверяем дневной лимит
// Получаем лимит пользователя
$user_sql = "SELECT daily_limit FROM users WHERE id = $user_id";
$user_result = mysqli_query($bd, $user_sql);
$user = mysqli_fetch_assoc($user_result);
$daily_limit = $user['daily_limit'] ?? 15;

// Рассчитываем текущую загрузку дня
$current_weight_sql = "SELECT SUM(weight) as total FROM user_tasks 
                       WHERE user_id = $user_id 
                       AND scheduled_date = '$scheduled_date' 
                       AND is_scheduled = 1 
                       AND completed = 0";
$current_result = mysqli_query($bd, $current_weight_sql);
$current_row = mysqli_fetch_assoc($current_result);
$current_weight = floatval($current_row['total'] ?? 0);

// Проверяем, не превысит ли новая задача лимит
if ($current_weight + $task_weight > $daily_limit) {
    echo json_encode([
        'success' => false, 
        'message' => "Превышен дневной лимит! Текущая загрузка: {$current_weight}/{$daily_limit} баллов. Задача добавит {$task_weight} баллов."
    ]);
    exit;
}

// 3. Обновляем задачу в БД
$sql = "UPDATE user_tasks SET 
    is_scheduled = 1,
    scheduled_date = '$scheduled_date',
    scheduled_time = '$time',
    scheduled_day_of_week = '$day',
    updated_at = NOW()
    WHERE id = $task_id AND user_id = $user_id";

if (mysqli_query($bd, $sql)) {
    echo json_encode(['success' => true, 'scheduled_date' => $scheduled_date]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . mysqli_error($bd)]);
}
?>