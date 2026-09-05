<?php
session_start();
require_once 'bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем все задачи пользователя
$sql = "SELECT * FROM user_tasks WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($bd, $sql);

$tasks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tasks[] = [
        'id' => $row['id'],
        'name' => $row['title'],
        'type' => $row['task_type'],
        'urgency' => $row['urgency'],
        'importance' => $row['importance'],
        'duration' => $row['duration'] ?? ($row['expected_duration_minutes'] / 60),
        'weight' => $row['weight'],
        'deadline' => $row['deadline'],
        'description' => $row['notes'],
        'preferred_time' => $row['preferred_time'],
        'preferred_day' => $row['preferred_day'] ?? 'any', // ДОБАВИТЬ ЭТО
        'scheduled' => $row['is_scheduled'] == 1,
        'scheduled_date' => $row['scheduled_date'],
        'scheduled_time' => $row['scheduled_time']
    ];
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
?>