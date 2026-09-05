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

// Снимаем задачу с расписания
$sql = "UPDATE user_tasks SET 
    is_scheduled = 0,
    scheduled_date = NULL,
    scheduled_time = NULL,
    scheduled_day_of_week = NULL,
    updated_at = NOW()
    WHERE id = $task_id AND user_id = $user_id";

if (mysqli_query($bd, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>