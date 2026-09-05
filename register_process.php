<?php
// register_process.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Включаем вывод ошибок для отладки (убрать в продакшене)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неверный метод запроса');
    }
    
    // Подключаемся к базе данных
    require_once 'bd.php';
    
    if (!isset($bd) || !$bd) {
        throw new Exception('Нет подключения к базе данных');
    }
    
    // Устанавливаем кодировку
    mysqli_set_charset($bd, "utf8");
    
    // Получаем и очищаем данные
    $lastName = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agreeTerms = isset($_POST['agreeTerms']) && $_POST['agreeTerms'] === 'on';
    $combineWorkStudy = isset($_POST['combineWorkStudy']) && $_POST['combineWorkStudy'] === 'on';
    $dailyLimit = $_POST['daily_limit'] ?? '15';
    $customDailyLimit = $_POST['custom_daily_limit'] ?? null;
    
    // Валидация
    $errors = [];
    
    if (empty($lastName)) {
        $errors[] = "Фамилия обязательна для заполнения";
    }
    
    if (empty($firstName)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email адрес";
    }
    
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    if (!$agreeTerms) {
        $errors[] = "Необходимо согласиться с условиями использования";
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Исправьте ошибки в форме',
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Проверяем существующий email
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = mysqli_prepare($bd, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_num_rows($checkResult) > 0) {
        throw new Exception('Пользователь с таким email уже существует');
    }
    mysqli_stmt_close($checkStmt);
    
    // Хешируем пароль
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Обрабатываем лимит
    if ($dailyLimit === 'custom' && $customDailyLimit !== null) {
        $dailyLimitValue = (int)$customDailyLimit;
        $customDailyLimitValue = (int)$customDailyLimit;
    } else {
        $dailyLimitValue = (int)$dailyLimit;
        $customDailyLimitValue = null;
    }
    
    // Начинаем транзакцию
    mysqli_begin_transaction($bd);
    
    // Сохраняем пользователя
    $insertQuery = "
        INSERT INTO users 
        (last_name, first_name, middle_name, email, password, combine_work_study, daily_limit, custom_daily_limit, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
    ";
    
    $stmt = mysqli_prepare($bd, $insertQuery);
    $combineWorkStudyInt = $combineWorkStudy ? 1 : 0;
    
    mysqli_stmt_bind_param(
        $stmt, 
        "sssssiii", 
        $lastName, 
        $firstName, 
        $middleName, 
        $email, 
        $hashedPassword, 
        $combineWorkStudyInt, 
        $dailyLimitValue, 
        $customDailyLimitValue
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Ошибка сохранения пользователя: ' . mysqli_stmt_error($stmt));
    }
    
    $userId = mysqli_insert_id($bd);
    mysqli_stmt_close($stmt);
    
    // СОХРАНЕНИЕ РАСПИСАНИЯ РАБОТЫ
    if ($combineWorkStudy) {
        $workDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $dayMap = [
            'mon' => 'monday',
            'tue' => 'tuesday', 
            'wed' => 'wednesday',
            'thu' => 'thursday',
            'fri' => 'friday',
            'sat' => 'saturday',
            'sun' => 'sunday'
        ];
        
        $workStmt = mysqli_prepare($bd, "
            INSERT INTO user_work_schedule (user_id, day_of_week, start_time, end_time) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($workDays as $shortDay) {
            $startField = "work_{$shortDay}_start";
            $endField = "work_{$shortDay}_end";
            
            $startTime = $_POST[$startField] ?? '';
            $endTime = $_POST[$endField] ?? '';
            $fullDay = $dayMap[$shortDay];
            
            // Сохраняем только если указано время
            if (!empty($startTime) && !empty($endTime)) {
                mysqli_stmt_bind_param($workStmt, "isss", $userId, $fullDay, $startTime, $endTime);
                if (!mysqli_stmt_execute($workStmt)) {
                    throw new Exception('Ошибка сохранения расписания работы: ' . mysqli_stmt_error($workStmt));
                }
            }
        }
        mysqli_stmt_close($workStmt);
    }
    
    // СОХРАНЕНИЕ РАСПИСАНИЯ УЧЕБЫ
    $studyDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $studyDayMap = [
        'mon' => 'monday',
        'tue' => 'tuesday', 
        'wed' => 'wednesday',
        'thu' => 'thursday',
        'fri' => 'friday',
        'sat' => 'saturday',
        'sun' => 'sunday'
    ];
    
    $studyStmt = mysqli_prepare($bd, "
        INSERT INTO user_study_schedule (user_id, day_of_week, start_time, end_time) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($studyDays as $shortDay) {
        $startField = "study_{$shortDay}_start";
        $endField = "study_{$shortDay}_end";
        
        $startTime = $_POST[$startField] ?? '';
        $endTime = $_POST[$endField] ?? '';
        $fullDay = $studyDayMap[$shortDay];
        
        // Сохраняем только если указано время
        if (!empty($startTime) && !empty($endTime)) {
            mysqli_stmt_bind_param($studyStmt, "isss", $userId, $fullDay, $startTime, $endTime);
            if (!mysqli_stmt_execute($studyStmt)) {
                throw new Exception('Ошибка сохранения расписания учебы: ' . mysqli_stmt_error($studyStmt));
            }
        }
    }
    mysqli_stmt_close($studyStmt);
    
    // СОХРАНЕНИЕ ФИКСИРОВАННЫХ ЗАДАЧ
    $fixedDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $fixedDayMap = [
        'mon' => 'monday',
        'tue' => 'tuesday', 
        'wed' => 'wednesday',
        'thu' => 'thursday',
        'fri' => 'friday',
        'sat' => 'saturday',
        'sun' => 'sunday'
    ];
    
    $fixedStmt = mysqli_prepare($bd, "
        INSERT INTO user_fixed_tasks (user_id, day_of_week, start_time, end_time) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($fixedDays as $shortDay) {
        $startField = "fixed_{$shortDay}_start";
        $endField = "fixed_{$shortDay}_end";
        
        $startTime = $_POST[$startField] ?? '';
        $endTime = $_POST[$endField] ?? '';
        $fullDay = $fixedDayMap[$shortDay];
        
        // Сохраняем только если указано время
        if (!empty($startTime) && !empty($endTime)) {
            mysqli_stmt_bind_param($fixedStmt, "isss", $userId, $fullDay, $startTime, $endTime);
            if (!mysqli_stmt_execute($fixedStmt)) {
                throw new Exception('Ошибка сохранения фиксированных задач: ' . mysqli_stmt_error($fixedStmt));
            }
        }
    }
    mysqli_stmt_close($fixedStmt);
    
    // СОХРАНЕНИЕ ЭНЕРГОЗАТРАТНОСТИ ЗАДАЧ
    $energyTypes = [
        'analytical' => 'analytical',
        'creative' => 'creative',
        'routine' => 'routine',
        'social' => 'social',
        'research' => 'research',
        'physical' => 'physical',
        'learning' => 'learning',
        'planning' => 'planning'
    ];
    
    $energyStmt = mysqli_prepare($bd, "
        INSERT INTO user_task_energy (user_id, task_type, energy_level) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($energyTypes as $key => $type) {
        $fieldName = "energy_{$key}";
        if (isset($_POST[$fieldName]) && is_numeric($_POST[$fieldName])) {
            $value = (int)$_POST[$fieldName];
            if ($value >= 1 && $value <= 10) {
                mysqli_stmt_bind_param($energyStmt, "isi", $userId, $type, $value);
                if (!mysqli_stmt_execute($energyStmt)) {
                    throw new Exception('Ошибка сохранения энергозатратности: ' . mysqli_stmt_error($energyStmt));
                }
            }
        }
    }
    mysqli_stmt_close($energyStmt);
    
    // Коммитим транзакцию
    mysqli_commit($bd);
    
    // Устанавливаем сессию для нового пользователя
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Регистрация успешно завершена!',
        'userId' => $userId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Откатываем транзакцию
    if (isset($bd)) {
        mysqli_rollback($bd);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => []
    ], JSON_UNESCAPED_UNICODE);
}

// После успешной регистрации пользователя, добавьте, добавление аваторки
$randomAvatar = rand(1, 20);
$avatarSql = "INSERT INTO user_avatars (user_id, avatar_number) VALUES ($userId, $randomAvatar)";
mysqli_query($bd, $avatarSql);
?>