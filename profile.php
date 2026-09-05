<?php
header('Content-Type: application/json');
require_once 'bd.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($bd, $sql);
$user = mysqli_fetch_assoc($result);

// Получаем расписание работы
$work_schedule = [];
$work_sql = "SELECT * FROM user_work_schedule WHERE user_id = $user_id";
$work_result = mysqli_query($bd, $work_sql);
while ($row = mysqli_fetch_assoc($work_result)) {
    $work_schedule[$row['day_of_week']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

// Получаем расписание учебы
$study_schedule = [];
$study_sql = "SELECT * FROM user_study_schedule WHERE user_id = $user_id";
$study_result = mysqli_query($bd, $study_sql);
while ($row = mysqli_fetch_assoc($study_result)) {
    $study_schedule[$row['day_of_week']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

// Получаем энергозатратность задач
$task_energy = [];
$energy_sql = "SELECT * FROM user_task_energy WHERE user_id = $user_id";
$energy_result = mysqli_query($bd, $energy_sql);
while ($row = mysqli_fetch_assoc($energy_result)) {
    $task_energy[$row['task_type']] = $row['energy_level'];
}

// Получаем фиксированные задачи
$fixed_tasks = [];
$tasks_sql = "SELECT * FROM user_fixed_tasks WHERE user_id = $user_id";
$tasks_result = mysqli_query($bd, $tasks_sql);
while ($row = mysqli_fetch_assoc($tasks_result)) {
    $fixed_tasks[] = [
        'id' => $row['id'],
        'day_of_week' => $row['day_of_week'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'description' => $row['description']
    ];
}

// Формируем ответ
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'last_name' => $user['last_name'],
        'first_name' => $user['first_name'],
        'middle_name' => $user['middle_name'],
        'email' => $user['email'],
        'combine_work_study' => (bool)$user['combine_work_study'],
        'daily_limit' => $user['daily_limit'],
        'custom_daily_limit' => $user['custom_daily_limit'],
        'work_schedule' => $work_schedule,
        'study_schedule' => $study_schedule,
        'task_energy' => $task_energy,
        'fixed_tasks' => $fixed_tasks
    ]
]);
?>