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
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Нет данных для обновления']);
    exit;
}

// Начинаем транзакцию
mysqli_begin_transaction($bd);

try {
    // Обновляем основную информацию пользователя
    $updates = [];
    $params = [];
    
    if (isset($data['last_name'])) {
        $updates[] = "last_name = ?";
        $params[] = $data['last_name'];
    }
    
    if (isset($data['first_name'])) {
        $updates[] = "first_name = ?";
        $params[] = $data['first_name'];
    }
    
    if (isset($data['middle_name'])) {
        $updates[] = "middle_name = ?";
        $params[] = $data['middle_name'];
    }
    
    if (isset($data['email'])) {
        // Проверяем, не занят ли email другим пользователем
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($bd, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $data['email'], $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception('Email уже используется другим пользователем');
        }
        
        $updates[] = "email = ?";
        $params[] = $data['email'];
    }
    
    if (isset($data['combine_work_study'])) {
        $updates[] = "combine_work_study = ?";
        $params[] = $data['combine_work_study'] ? 1 : 0;
    }
    
    if (isset($data['daily_limit'])) {
        $updates[] = "daily_limit = ?";
        $params[] = $data['daily_limit'];
    }
    
    if (isset($data['custom_daily_limit'])) {
        $updates[] = "custom_daily_limit = ?";
        $params[] = $data['custom_daily_limit'];
    }
    
    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt = mysqli_prepare($bd, $sql);
        $types = str_repeat('s', count($params) - 1) . 'i';
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Ошибка обновления пользователя');
        }
    }
    
    // Обновляем расписание работы
    if (isset($data['work_schedule'])) {
        // Удаляем старое расписание
        $delete_sql = "DELETE FROM user_work_schedule WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($bd, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        
        // Добавляем новое
        foreach ($data['work_schedule'] as $day => $schedule) {
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $insert_sql = "INSERT INTO user_work_schedule (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($bd, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "isss", $user_id, $day, $schedule['start_time'], $schedule['end_time']);
                mysqli_stmt_execute($insert_stmt);
            }
        }
    }
    
    // Обновляем расписание учебы
    if (isset($data['study_schedule'])) {
        // Удаляем старое расписание
        $delete_sql = "DELETE FROM user_study_schedule WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($bd, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        
        // Добавляем новое
        foreach ($data['study_schedule'] as $day => $schedule) {
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $insert_sql = "INSERT INTO user_study_schedule (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($bd, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "isss", $user_id, $day, $schedule['start_time'], $schedule['end_time']);
                mysqli_stmt_execute($insert_stmt);
            }
        }
    }
    
    // Обновляем энергозатратность задач
    if (isset($data['task_energy'])) {
        // Удаляем старые значения
        $delete_sql = "DELETE FROM user_task_energy WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($bd, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        
        // Добавляем новые
        foreach ($data['task_energy'] as $task_type => $energy_level) {
            $insert_sql = "INSERT INTO user_task_energy (user_id, task_type, energy_level) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($bd, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "isi", $user_id, $task_type, $energy_level);
            mysqli_stmt_execute($insert_stmt);
        }
    }
    
    // Обновляем фиксированные задачи
    if (isset($data['fixed_tasks'])) {
        // Удаляем старые задачи
        $delete_sql = "DELETE FROM user_fixed_tasks WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($bd, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        
        // Добавляем новые
        foreach ($data['fixed_tasks'] as $task) {
            $insert_sql = "INSERT INTO user_fixed_tasks (user_id, day_of_week, start_time, end_time, description) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($bd, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "issss", $user_id, $task['day_of_week'], $task['start_time'], $task['end_time'], $task['description']);
            mysqli_stmt_execute($insert_stmt);
        }
    }
    
    // Подтверждаем транзакцию
    mysqli_commit($bd);
    
    echo json_encode(['success' => true, 'message' => 'Данные успешно обновлены']);
    
} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    mysqli_rollback($bd);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>