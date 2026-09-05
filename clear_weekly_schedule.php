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

if (!isset($data['week_start'])) {
    echo json_encode(['success' => false, 'message' => 'Не указана дата начала недели']);
    exit;
}

$week_start = mysqli_real_escape_string($bd, $data['week_start']);

// Рассчитываем даты недели
$dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($week_start . " +{$i} days"));
    $dates[] = $date;
}

// Снимаем задачи с расписания для этой недели
$date_condition = "scheduled_date IN ('" . implode("','", $dates) . "')";
$sql = "UPDATE user_tasks SET 
    is_scheduled = 0,
    scheduled_date = NULL,
    scheduled_time = NULL,
    scheduled_day_of_week = NULL,
    updated_at = NOW()
    WHERE user_id = $user_id AND $date_condition";

if (mysqli_query($bd, $sql)) {
    $affected_rows = mysqli_affected_rows($bd);
    echo json_encode(['success' => true, 'cleared_tasks' => $affected_rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . mysqli_error($bd)]);
}
?>