<?php
session_start();
require_once 'bd.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));

// Рассчитываем даты недели
$start_date = date('Y-m-d', strtotime($week_start));
$end_date = date('Y-m-d', strtotime($week_start . ' +6 days'));

// ВАЖНОЕ ИЗМЕНЕНИЕ: Получаем ВСЕ задачи пользователя
$sql = "SELECT * FROM user_tasks WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($bd, $sql);

$tasks = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Определяем, запланирована ли задача на эту неделю
    $isScheduled = false;
    $day = null;
    $scheduled_time = null;
    
    if ($row['is_scheduled'] == 1 && $row['scheduled_date']) {
        // Проверяем, относится ли дата к текущей неделе
        if ($row['scheduled_date'] >= $start_date && $row['scheduled_date'] <= $end_date) {
            $isScheduled = true;
            $day = $row['scheduled_day_of_week'];
            $scheduled_time = $row['scheduled_time'];
        }
    }
    
    // ВАЖНО: Выполненные задачи, запланированные на другие недели, НЕ показываем как нераспределенные
    $isPending = (!$isScheduled && $row['completed'] == 0); // Только невыполненные
    
    $tasks[] = [
        'id' => intval($row['id']),
        'name' => $row['title'],
        'type' => $row['task_type'],
        'urgency' => intval($row['urgency']),
        'importance' => intval($row['importance']),
        'duration' => floatval($row['duration']),
        'weight' => floatval($row['weight']),
        'deadline' => $row['deadline'],
        'preferred_day' => $row['preferred_day'] ?? 'any',
        'description' => $row['notes'],
        'preferred_time' => $row['preferred_time'] ?? 'any',
        'scheduled' => $isScheduled, // Только если на этой неделе!
        'scheduled_date' => $isScheduled ? $row['scheduled_date'] : null,
        'scheduled_time' => $scheduled_time,
        'day' => $day,
        'completed' => $row['completed'] == 1
    ];
}

echo json_encode([
    'success' => true, 
    'tasks' => $tasks,
    'week_start' => $start_date,
    'week_end' => $end_date
]);
?>