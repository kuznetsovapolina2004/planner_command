<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'bd.php';

$user_id = $_SESSION['user_id'];

// Получаем информацию о пользователе
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($bd, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$userData = mysqli_fetch_assoc($result);

// ==============================
// ОБНОВЛЕНИЕ СТАТИСТИКИ ПРИ ЗАХОДЕ
// ==============================
$today = date('Y-m-d');
$registration_date = date('Y-m-d', strtotime($userData['created_at']));

// Инициализируем статистику по умолчанию
$userStats = [
    'tasks_completed' => 0,
    'days_active' => 1
];

try {
    // Проверяем таблицу user_stats
    $check_table = "SHOW TABLES LIKE 'user_stats'";
    $table_result = mysqli_query($bd, $check_table);
    
    if ($table_result && mysqli_num_rows($table_result) > 0) {
        $stats_sql = "SELECT * FROM user_stats WHERE user_id = $user_id";
        $stats_result = mysqli_query($bd, $stats_sql);
        
        if ($stats_result && mysqli_num_rows($stats_result) > 0) {
            $userStats = mysqli_fetch_assoc($stats_result);
            
            // Пересчитываем days_active на основе даты регистрации
            $reg_date = new DateTime($userData['created_at']);
            $today_obj = new DateTime();
            $interval = $reg_date->diff($today_obj);
            $days_since_registration = max(1, $interval->days + 1);
            
            // Обновляем days_active, если изменилось
            if ($userStats['days_active'] != $days_since_registration) {
                $update_stats = "UPDATE user_stats SET 
                                 days_active = $days_since_registration,
                                 last_active_date = '$today',
                                 updated_at = NOW()
                                 WHERE user_id = $user_id";
                mysqli_query($bd, $update_stats);
                $userStats['days_active'] = $days_since_registration;
            }
        } else {
            // Создаем новую запись со значением от даты регистрации
            $reg_date = new DateTime($userData['created_at']);
            $today_obj = new DateTime();
            $interval = $reg_date->diff($today_obj);
            $days_since_registration = max(1, $interval->days + 1);
            
            $create_stats = "INSERT INTO user_stats (user_id, tasks_completed, days_active, last_active_date) 
                             VALUES ($user_id, 0, $days_since_registration, '$today')";
            mysqli_query($bd, $create_stats);
            
            $userStats = [
                'tasks_completed' => 0,
                'days_active' => $days_since_registration,
                'last_active_date' => $today
            ];
        }
    } else {
        // Если таблицы нет, считаем дни от регистрации
        $reg_date = new DateTime($userData['created_at']);
        $today_obj = new DateTime();
        $interval = $reg_date->diff($today_obj);
        $userStats['days_active'] = max(1, $interval->days + 1);
    }
} catch (Exception $e) {
    // В случае ошибки считаем дни от регистрации
    $reg_date = new DateTime($userData['created_at']);
    $today_obj = new DateTime();
    $interval = $reg_date->diff($today_obj);
    $userStats['days_active'] = max(1, $interval->days + 1);
    $userStats['tasks_completed'] = 0;
}


// Получаем аватар пользователя
$avatarSql = "SELECT avatar_number FROM user_avatars WHERE user_id = $user_id";
$avatarResult = mysqli_query($bd, $avatarSql);
$userAvatar = 1; // значение по умолчанию

if ($avatarResult && mysqli_num_rows($avatarResult) > 0) {
    $avatarData = mysqli_fetch_assoc($avatarResult);
    $userAvatar = $avatarData['avatar_number'];
} else {
    // Если аватар не найден, создаем запись со случайным аватаром
    $defaultAvatar = rand(1, 20);
    $insertSql = "INSERT INTO user_avatars (user_id, avatar_number) VALUES ($user_id, $defaultAvatar)";
    mysqli_query($bd, $insertSql);
    $userAvatar = $defaultAvatar;
}

// Получаем полную информацию о пользователе
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($bd, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit;
}

$userData = mysqli_fetch_assoc($result);

// Получаем энергозатратность задач
$energyData = [];
$energy_sql = "SELECT task_type, energy_level FROM user_task_energy WHERE user_id = $user_id";
$energy_result = mysqli_query($bd, $energy_sql);
while ($row = mysqli_fetch_assoc($energy_result)) {
    $energyData[$row['task_type']] = $row['energy_level'];
}

// Получаем расписание работы
$workSchedule = [];
$work_sql = "SELECT * FROM user_work_schedule WHERE user_id = $user_id";
$work_result = mysqli_query($bd, $work_sql);
while ($row = mysqli_fetch_assoc($work_result)) {
    $workSchedule[$row['day_of_week']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

// Получаем расписание учебы
$studySchedule = [];
$study_sql = "SELECT * FROM user_study_schedule WHERE user_id = $user_id";
$study_result = mysqli_query($bd, $study_sql);
while ($row = mysqli_fetch_assoc($study_result)) {
    $studySchedule[$row['day_of_week']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

// Получаем фиксированные задачи
$fixedTasks = [];
$tasks_sql = "SELECT * FROM user_fixed_tasks WHERE user_id = $user_id";
$tasks_result = mysqli_query($bd, $tasks_sql);
while ($row = mysqli_fetch_assoc($tasks_result)) {
    $fixedTasks[$row['day_of_week']] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'description' => $row['description']
    ];
}

// Инициализация переменных для сообщений
$success = '';
$error = '';

// Обработка обновления данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $firstName = mysqli_real_escape_string($bd, trim($_POST['firstName'] ?? ''));
        $lastName = mysqli_real_escape_string($bd, trim($_POST['lastName'] ?? ''));
        $middleName = mysqli_real_escape_string($bd, trim($_POST['middleName'] ?? ''));
        $email = mysqli_real_escape_string($bd, trim($_POST['email'] ?? ''));
        $combineWorkStudy = isset($_POST['combine_work_study']) ? 1 : 0;
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = "Все обязательные поля должны быть заполнены";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Некорректный email адрес";
        } else {
            // Проверяем, не занят ли email другим пользователем
            $check_sql = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
            $check_result = mysqli_query($bd, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Этот email уже используется другим пользователем";
            } else {
                $update_sql = "UPDATE users SET 
                    first_name = '$firstName', 
                    last_name = '$lastName', 
                    middle_name = '$middleName', 
                    email = '$email',
                    combine_work_study = $combineWorkStudy,
                    updated_at = NOW()
                    WHERE id = $user_id";
                
                if (mysqli_query($bd, $update_sql)) {
                    // Обновляем данные в сессии
                    $_SESSION['user_email'] = $email;
                    
                    // Обновляем локальные данные
                    $userData['first_name'] = $firstName;
                    $userData['last_name'] = $lastName;
                    $userData['middle_name'] = $middleName;
                    $userData['email'] = $email;
                    $userData['combine_work_study'] = $combineWorkStudy;
                    
                    $success = "Профиль успешно обновлен!";
                } else {
                    $error = "Ошибка обновления профиля: " . mysqli_error($bd);
                }
            }
        }
    }
    
    elseif ($action === 'change_avatar') {
        $newAvatar = (int)($_POST['avatar'] ?? 1);
        
        if ($newAvatar >= 1 && $newAvatar <= 20) {
            $updateSql = "UPDATE user_avatars SET avatar_number = $newAvatar WHERE user_id = $user_id";
            
            if (mysqli_query($bd, $updateSql)) {
                $userAvatar = $newAvatar;
                $success = "Аватар успешно изменен!";
            } else {
                $error = "Ошибка изменения аватара: " . mysqli_error($bd);
            }
        } else {
            $error = "Некорректный номер аватара";
        }
    }
    
    elseif ($action === 'update_limits') {
        $dailyLimit = $_POST['daily_limit'] ?? 15;
        $customDailyLimit = null;
        
        if ($dailyLimit === 'custom') {
            $customDailyLimit = (int)($_POST['custom_daily_limit'] ?? 15);
            $dailyLimitValue = $customDailyLimit;
        } else {
            $dailyLimitValue = (int)$dailyLimit;
        }
        
        if ($dailyLimitValue < 5 || $dailyLimitValue > 50) {
            $error = "Лимит должен быть от 5 до 50 баллов";
        } else {
            $update_sql = "UPDATE users SET 
                daily_limit = $dailyLimitValue,
                custom_daily_limit = " . ($customDailyLimit !== null ? "'$customDailyLimit'" : "NULL") . ",
                updated_at = NOW()
                WHERE id = $user_id";
            
            if (mysqli_query($bd, $update_sql)) {
                $userData['daily_limit'] = $dailyLimitValue;
                $userData['custom_daily_limit'] = $customDailyLimit;
                $success = "Лимиты успешно обновлены!";
            } else {
                $error = "Ошибка обновления лимитов: " . mysqli_error($bd);
            }
        }
    }
    
    elseif ($action === 'update_energy') {
        $energyTypes = ['analytical', 'creative', 'routine', 'social', 'research', 'physical', 'learning', 'planning'];
        
        mysqli_begin_transaction($bd);
        $allSuccess = true;
        
        foreach ($energyTypes as $type) {
            $energyValue = (int)($_POST["energy_$type"] ?? 5);
            $energyValue = max(1, min(10, $energyValue));
            
            $sql = "INSERT INTO user_task_energy (user_id, task_type, energy_level) 
                    VALUES ($user_id, '$type', $energyValue)
                    ON DUPLICATE KEY UPDATE energy_level = $energyValue";
            
            if (!mysqli_query($bd, $sql)) {
                $allSuccess = false;
                break;
            }
            $energyData[$type] = $energyValue;
        }
        
        if ($allSuccess) {
            mysqli_commit($bd);
            $success = "Энергозатратность обновлена!";
        } else {
            mysqli_rollback($bd);
            $error = "Ошибка обновления энергозатратности";
        }
    }
    
    elseif ($action === 'update_schedule') {
        $combineWorkStudy = isset($_POST['combine_work_study']) ? 1 : 0;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        mysqli_begin_transaction($bd);
        $allSuccess = true;
        
        // Обновляем флаг совмещения работы и учебы
        $updateUserSql = "UPDATE users SET combine_work_study = $combineWorkStudy WHERE id = $user_id";
        if (!mysqli_query($bd, $updateUserSql)) {
            $allSuccess = false;
        }
        
        // Обновляем расписание работы
        if ($allSuccess) {
            foreach ($days as $day) {
                $workStart = $_POST["work_{$day}_start"] ?? '';
                $workEnd = $_POST["work_{$day}_end"] ?? '';
                
                if (!empty($workStart) && !empty($workEnd)) {
                    $sql = "INSERT INTO user_work_schedule (user_id, day_of_week, start_time, end_time) 
                            VALUES ($user_id, '$day', '$workStart', '$workEnd')
                            ON DUPLICATE KEY UPDATE start_time = '$workStart', end_time = '$workEnd'";
                } else {
                    $sql = "DELETE FROM user_work_schedule WHERE user_id = $user_id AND day_of_week = '$day'";
                }
                
                if (!mysqli_query($bd, $sql)) {
                    $allSuccess = false;
                    break;
                }
            }
        }
        
        // Обновляем расписание учебы
        if ($allSuccess) {
            foreach ($days as $day) {
                $studyStart = $_POST["study_{$day}_start"] ?? '';
                $studyEnd = $_POST["study_{$day}_end"] ?? '';
                
                if (!empty($studyStart) && !empty($studyEnd)) {
                    $sql = "INSERT INTO user_study_schedule (user_id, day_of_week, start_time, end_time) 
                            VALUES ($user_id, '$day', '$studyStart', '$studyEnd')
                            ON DUPLICATE KEY UPDATE start_time = '$studyStart', end_time = '$studyEnd'";
                } else {
                    $sql = "DELETE FROM user_study_schedule WHERE user_id = $user_id AND day_of_week = '$day'";
                }
                
                if (!mysqli_query($bd, $sql)) {
                    $allSuccess = false;
                    break;
                }
            }
        }
        
        // Обновляем фиксированные задачи
        if ($allSuccess) {
            // Сначала удаляем все существующие фиксированные задачи
            $deleteSql = "DELETE FROM user_fixed_tasks WHERE user_id = $user_id";
            if (!mysqli_query($bd, $deleteSql)) {
                $allSuccess = false;
            }
            
            // Затем добавляем новые
            if ($allSuccess) {
                foreach ($days as $day) {
                    $fixedStart = $_POST["fixed_{$day}_start"] ?? '';
                    $fixedEnd = $_POST["fixed_{$day}_end"] ?? '';
                    
                    if (!empty($fixedStart) && !empty($fixedEnd)) {
                        $sql = "INSERT INTO user_fixed_tasks (user_id, day_of_week, start_time, end_time) 
                                VALUES ($user_id, '$day', '$fixedStart', '$fixedEnd')";
                        
                        if (!mysqli_query($bd, $sql)) {
                            $allSuccess = false;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($allSuccess) {
            mysqli_commit($bd);
            
            // Обновляем локальные данные
            $userData['combine_work_study'] = $combineWorkStudy;
            
            // Перезагружаем расписания
            $workSchedule = [];
            $studySchedule = [];
            $fixedTasks = [];
            
            $work_result = mysqli_query($bd, "SELECT * FROM user_work_schedule WHERE user_id = $user_id");
            while ($row = mysqli_fetch_assoc($work_result)) {
                $workSchedule[$row['day_of_week']] = [
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ];
            }
            
            $study_result = mysqli_query($bd, "SELECT * FROM user_study_schedule WHERE user_id = $user_id");
            while ($row = mysqli_fetch_assoc($study_result)) {
                $studySchedule[$row['day_of_week']] = [
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ];
            }
            
            $tasks_result = mysqli_query($bd, "SELECT * FROM user_fixed_tasks WHERE user_id = $user_id");
            while ($row = mysqli_fetch_assoc($tasks_result)) {
                $fixedTasks[$row['day_of_week']] = [
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'description' => $row['description']
                ];
            }
            
            $success = "Расписание успешно обновлено!";
        } else {
            mysqli_rollback($bd);
            $error = "Ошибка обновления расписания";
        }
    }
    
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "Все поля пароля обязательны для заполнения";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Новые пароли не совпадают";
        } elseif (strlen($newPassword) < 6) {
            $error = "Новый пароль должен содержать минимум 6 символов";
        } else {
            // Проверяем текущий пароль
            $check_sql = "SELECT password FROM users WHERE id = $user_id";
            $check_result = mysqli_query($bd, $check_sql);
            
            if ($row = mysqli_fetch_assoc($check_result)) {
                if (password_verify($currentPassword, $row['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $update_sql = "UPDATE users SET password = '$hashedPassword', updated_at = NOW() WHERE id = $user_id";
                    if (mysqli_query($bd, $update_sql)) {
                        $success = "Пароль успешно изменен!";
                    } else {
                        $error = "Ошибка смены пароля";
                    }
                } else {
                    $error = "Неверный текущий пароль";
                }
            } else {
                $error = "Ошибка проверки пароля";
            }
        }
    }
}

// Перезагружаем данные пользователя после обновлений
if ($success || $error) {
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($bd, $sql);
    $userData = mysqli_fetch_assoc($result);
    
    // Обновляем аватар
    $avatarSql = "SELECT avatar_number FROM user_avatars WHERE user_id = $user_id";
    $avatarResult = mysqli_query($bd, $avatarSql);
    if ($avatarResult && mysqli_num_rows($avatarResult) > 0) {
        $avatarData = mysqli_fetch_assoc($avatarResult);
        $userAvatar = $avatarData['avatar_number'];
    }
    
    $energy_sql = "SELECT task_type, energy_level FROM user_task_energy WHERE user_id = $user_id";
    $energy_result = mysqli_query($bd, $energy_sql);
    $energyData = [];
    while ($row = mysqli_fetch_assoc($energy_result)) {
        $energyData[$row['task_type']] = $row['energy_level'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="image/icon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Умный Планировщик</title>
    <link rel="stylesheet" href="account.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <div class="account-page">
        <div class="account-container">
            <a href="planner.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Назад к планировщику
            </a>
            
            <div class="account-header">
                <h1><i class="fas fa-user-circle"></i> Личный кабинет</h1>
                <p class="subtitle">Умный Планировщик - Управление вашим расписанием</p>
            </div>
            
            <div class="user-profile">
                <div class="avatar">
                    <?php 
                    // Пытаемся отобразить аватарку из файла
                    $avatarFile = "icon_acc/{$userAvatar}.ico";
                    if (file_exists($avatarFile)): ?>
                        <img src="<?php echo $avatarFile; ?>" alt="Аватар пользователя" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-fallback">
                            <?php 
                            echo strtoupper(
                                substr($userData['first_name'] ?? 'И', 0, 1) . 
                                substr($userData['last_name'] ?? 'П', 0, 1)
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')); ?></h2>
                    <p><i class="fas fa-image"></i> Аватар: #<?php echo $userAvatar; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Зарегистрирован: <?php echo date('d.m.Y', strtotime($userData['created_at'] ?? 'now')); ?></p>
                    <?php if ($userData['combine_work_study'] ?? false): ?>
                        <p><i class="fas fa-briefcase"></i> <i class="fas fa-graduation-cap"></i> Режим: Работа + Учеба</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="account-nav">
                <button class="tab-btn active" data-tab="overview">
                    <i class="fas fa-chart-line"></i> Обзор
                </button>
                <button class="tab-btn" data-tab="profile">
                    <i class="fas fa-user-edit"></i> Профиль
                </button>
                <button class="tab-btn" data-tab="avatar">
                    <i class="fas fa-camera"></i> Аватар
                </button>
                <button class="tab-btn" data-tab="limits">
                    <i class="fas fa-tachometer-alt"></i> Лимиты
                </button>
                <button class="tab-btn" data-tab="energy">
                    <i class="fas fa-bolt"></i> Энергия
                </button>
                <button class="tab-btn" data-tab="schedule">
                    <i class="fas fa-calendar"></i> Расписание
                </button>
                <button class="tab-btn" data-tab="password">
                    <i class="fas fa-key"></i> Пароль
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content active" id="overview-tab">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-star"></i>
                        <h3><?php echo $userData['daily_limit'] ?? 15; ?></h3>
                        <p>Дневной лимит</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-fire"></i>
                        <h3>
                            <?php 
                            if (!empty($energyData)) {
                                echo round(array_sum($energyData)/count($energyData), 1);
                            } else {
                                echo '5.0';
                            }
                            ?>
                        </h3>
                        <p>Средняя энергия</p>
                    </div>
                   <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3><?php echo $userStats['days_active'] ?? 0; ?></h3>
                            <p>Дней планирования</p>
                    </div>
                </div>
                
                <div class="daily-planner-tip">
                    <h4><i class="fas fa-lightbulb"></i> Советы по использованию Умного Планировщика:</h4>
                    <ul>
                        <li>Настройте энергозатратность задач для более точного планирования</li>
                        <li>Установите реалистичный дневной лимит</li>
                        <li>Регулярно обновляйте статус задач</li>
                        <li>Используйте фиксированные задачи для повторяющихся событий</li>
                        <li>Выберите аватар, который вам нравится!</li>
                    </ul>
                </div>
            </div>
            
            <div class="tab-content" id="profile-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">Имя *</label>
                            <input type="text" id="firstName" name="firstName" 
                                   value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Фамилия *</label>
                            <input type="text" id="lastName" name="lastName" 
                                   value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="middleName">Отчество</label>
                        <input type="text" id="middleName" name="middleName" 
                               value="<?php echo htmlspecialchars($userData['middle_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="combine_work_study" value="1" 
                                   <?php echo ($userData['combine_work_study'] ?? 0) ? 'checked' : ''; ?>>
                            Совмещаю работу с учебой
                        </label>
                        <small class="form-hint">Включит отображение расписания работы и учебы</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="avatar-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="change_avatar">
                    
                    <div class="current-avatar">
                        <h3><i class="fas fa-user-circle"></i> Текущий аватар</h3>
                        <?php if (file_exists("icon_acc/{$userAvatar}.ico")): ?>
                            <img src="icon_acc/<?php echo $userAvatar; ?>.ico" alt="Аватар <?php echo $userAvatar; ?>" 
                                 class="avatar-img-large">
                        <?php else: ?>
                            <div class="avatar-fallback" style="width: 120px; height: 120px; font-size: 36px; margin: 0 auto 10px;">
                                <?php 
                                echo strtoupper(
                                    substr($userData['first_name'] ?? 'И', 0, 1) . 
                                    substr($userData['last_name'] ?? 'П', 0, 1)
                                );
                                ?>
                            </div>
                        <?php endif; ?>
                        <p>Аватар #<?php echo $userAvatar; ?></p>
                    </div>
                    
                    <div class="avatar-selection">
                        <h3><i class="fas fa-images"></i> Выберите новый аватар</h3>
                        <p>Доступно 20 различных аватаров. Выберите тот, который вам нравится больше всего!</p>
                        
                        <div class="avatar-grid">
                            <?php for ($i = 1; $i <= 20; $i++): 
                                $avatarFile = "icon_acc/{$i}.ico";
                                $fileExists = file_exists($avatarFile);
                            ?>
                            <label class="avatar-option">
                                <input type="radio" name="avatar" value="<?php echo $i; ?>" 
                                       <?php echo $i == $userAvatar ? 'checked' : ''; ?>>
                                <div class="avatar-preview <?php echo $i == $userAvatar ? 'selected' : ''; ?>">
                                    <?php if ($fileExists): ?>
                                        <img src="icon_acc/<?php echo $i; ?>.ico" alt="Аватар <?php echo $i; ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f0f0f0; color: #666; font-weight: bold;">
                                            <?php echo $i; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="avatar-number">#<?php echo $i; ?></span>
                                </div>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить аватар
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="limits-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="update_limits">
                    
                    <div class="form-group">
                        <label for="daily_limit">Максимальное количество баллов сложности в день:</label>
                        <select id="daily_limit" name="daily_limit" class="form-control">
                            <?php 
                            $currentLimit = $userData['daily_limit'] ?? 15;
                            $customLimit = $userData['custom_daily_limit'] ?? null;
                            $options = [
                                10 => '10 баллов (Минимальная нагрузка)',
                                15 => '15 баллов (Легкая нагрузка)',
                                20 => '20 баллов (Средняя нагрузка)',
                                25 => '25 баллов (Высокая нагрузка)',
                                30 => '30 баллов (Максимальная нагрузка)'
                            ];
                            
                            $isCustom = ($customLimit !== null && !array_key_exists($currentLimit, $options));
                            
                            foreach ($options as $value => $label) {
                                $selected = (!$isCustom && $value == $currentLimit) ? 'selected' : '';
                                echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                            }
                            
                            $customSelected = $isCustom ? 'selected' : '';
                            echo '<option value="custom" ' . $customSelected . '>Задать свое значение</option>';
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group custom-limit" style="<?php echo $isCustom ? 'display: block;' : 'display: none;'; ?>">
                        <label for="custom_daily_limit">Свое значение (от 5 до 50):</label>
                        <input type="number" id="custom_daily_limit" name="custom_daily_limit" 
                               min="5" max="50" value="<?php echo $isCustom ? $currentLimit : '15'; ?>">
                    </div>
                    
                    <div class="form-hint">
                        <p><strong>Как это работает в Умном Планировщике:</strong></p>
                        <p>Система будет распределять задачи так, чтобы сумма их баллов сложности не превышала дневной лимит. Учитываются энергозатратность задачи, срочность и важность.</p>
                    </div>
                    <br>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить лимиты
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="energy-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="update_energy">
                    
                    <div class="form-hint">
                        <p><strong>Настройка энергозатратности задач:</strong></p>
                        <p>Установите, насколько энергозатратными для вас являются разные типы задач (1 - минимально, 10 - максимально). Это поможет Умному Планировщику оптимально распределять задачи в течение дня.</p>
                    </div>
                    <br>
                    
                    <div class="energy-grid">
                        <?php 
                        $energyTypes = [
                            'analytical' => ['Аналитические задачи', 'Решение сложных задач, анализ данных'],
                            'creative' => ['Творческие задачи', 'Креативная работа, мозговые штурмы'],
                            'routine' => ['Рутинные задачи', 'Повторяющиеся задачи, документация'],
                            'social' => ['Социальные задачи', 'Встречи, переговоры, общение'],
                            'research' => ['Исследовательские задачи', 'Изучение нового, исследования'],
                            'physical' => ['Физические задачи', 'Спорт, активная деятельность'],
                            'learning' => ['Обучающие задачи', 'Обучение, курсы, чтение'],
                            'planning' => ['Планирование', 'Составление планов, организация']
                        ];
                        
                        foreach ($energyTypes as $key => [$label, $description]): 
                            $value = $energyData[$key] ?? 5;
                        ?>
                        <div class="energy-item">
                            <label for="energy_<?php echo $key; ?>"><?php echo $label; ?>: <span class="slider-value"><?php echo $value; ?></span></label>
                            <div class="energy-slider-container">
                                <span class="slider-labels">1</span>
                                <input type="range" id="energy_<?php echo $key; ?>" name="energy_<?php echo $key; ?>" 
                                       min="1" max="10" value="<?php echo $value; ?>" class="energy-slider">
                                <span class="slider-labels">10</span>
                            </div>
                            <p class="energy-description"><?php echo $description; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить энергозатратность
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="schedule-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="update_schedule">
                    
                    <div class="schedule-section">
                        <div class="checkbox-group">
                            <input type="checkbox" id="combineWorkStudy" name="combine_work_study" value="1" 
                                   <?php echo ($userData['combine_work_study'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="combineWorkStudy">Я совмещаю работу с учебой</label>
                        </div>
                        
                        <h4><i class="fas fa-calendar-alt"></i> Расписание постоянной занятости</h4>
                        
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>День недели</th>
                                    <th>Работа</th>
                                    <th>Учеба</th>
                                    <th>Фиксированные задачи</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleBody">
                                <?php 
                                $days = [
                                    'monday' => 'Понедельник',
                                    'tuesday' => 'Вторник',
                                    'wednesday' => 'Среда',
                                    'thursday' => 'Четверг',
                                    'friday' => 'Пятница',
                                    'saturday' => 'Суббота',
                                    'sunday' => 'Воскресенье'
                                ];
                                
                                foreach ($days as $dayKey => $dayName): 
                                    $workStart = isset($workSchedule[$dayKey]['start_time']) ? substr($workSchedule[$dayKey]['start_time'], 0, 5) : '';
                                    $workEnd = isset($workSchedule[$dayKey]['end_time']) ? substr($workSchedule[$dayKey]['end_time'], 0, 5) : '';
                                    $studyStart = isset($studySchedule[$dayKey]['start_time']) ? substr($studySchedule[$dayKey]['start_time'], 0, 5) : '';
                                    $studyEnd = isset($studySchedule[$dayKey]['end_time']) ? substr($studySchedule[$dayKey]['end_time'], 0, 5) : '';
                                    $fixedStart = isset($fixedTasks[$dayKey]['start_time']) ? substr($fixedTasks[$dayKey]['start_time'], 0, 5) : '';
                                    $fixedEnd = isset($fixedTasks[$dayKey]['end_time']) ? substr($fixedTasks[$dayKey]['end_time'], 0, 5) : '';
                                    
                                    // Устанавливаем значения по умолчанию для рабочих дней
                                    if (empty($workStart) && in_array($dayKey, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])) {
                                        $workStart = '09:00';
                                        $workEnd = '18:00';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $dayName; ?></td>
                                    <td>
                                        <div class="time-input-group work-time">
                                            <input type="time" name="work_<?php echo $dayKey; ?>_start" value="<?php echo $workStart; ?>" class="work-input">
                                            <span>до</span>
                                            <input type="time" name="work_<?php echo $dayKey; ?>_end" value="<?php echo $workEnd; ?>" class="work-input">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-input-group study-time">
                                            <input type="time" name="study_<?php echo $dayKey; ?>_start" value="<?php echo $studyStart; ?>" class="study-input">
                                            <span>до</span>
                                            <input type="time" name="study_<?php echo $dayKey; ?>_end" value="<?php echo $studyEnd; ?>" class="study-input">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-input-group fixed-time">
                                            <input type="time" name="fixed_<?php echo $dayKey; ?>_start" value="<?php echo $fixedStart; ?>" placeholder="Начало">
                                            <span>до</span>
                                            <input type="time" name="fixed_<?php echo $dayKey; ?>_end" value="<?php echo $fixedEnd; ?>" placeholder="Конец">
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-hint">
                        <p><strong>Как заполнять расписание:</strong></p>
                        <ul>
                            <li><strong>Работа</strong> - укажите время начала и окончания рабочего дня</li>
                            <li><strong>Учеба</strong> - заполняется только если отмечен чекбокс "Совмещаю работу с учебой"</li>
                            <li><strong>Фиксированные задачи</strong> - регулярные мероприятия (спорт, хобби и т.д.)</li>
                            <li>Оставьте поля пустыми, если в этот день нет занятости</li>
                        </ul>
                    </div>
                    <br>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить расписание
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="password-tab">
                <form method="POST" class="account-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Текущий пароль *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль *</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small class="form-hint">Минимум 6 символов</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите новый пароль *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Сменить пароль
                    </button>
                </form>
            </div>
            
            <div class="account-actions">
               <button onclick="window.location.href='logout.php'" class="btn btn-logout">
    <i class="fas fa-sign-out-alt"></i> Выйти
</button>
                
                <a href="planner.php" class="btn btn-primary">
                    <i class="fas fa-calendar-day"></i> Перейти к планировщику
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Управление вкладками
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.dataset.tab;
                    
                    // Скрыть все вкладки
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    
                    // Убрать активный класс у всех кнопок
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    
                    // Показать выбранную вкладку
                    document.getElementById(tabId + '-tab').classList.add('active');
                    this.classList.add('active');
                    
                    // Сохранить выбранную вкладку
                    localStorage.setItem('lastTab', tabId);
                });
            });
            
            // Обновление значений слайдеров
            document.querySelectorAll('.energy-slider').forEach(slider => {
                const updateValue = function() {
                    const valueElement = this.closest('.energy-item').querySelector('.slider-value');
                    if (valueElement) {
                        valueElement.textContent = this.value;
                    }
                };
                
                slider.addEventListener('input', updateValue);
                updateValue.call(slider); // Инициализация
            });
            
            // Управление кастомным лимитом
            const dailyLimitSelect = document.getElementById('daily_limit');
            const customLimitDiv = document.querySelector('.custom-limit');
            const customLimitInput = document.getElementById('custom_daily_limit');
            
            function updateCustomLimitVisibility() {
                if (dailyLimitSelect.value === 'custom') {
                    customLimitDiv.style.display = 'block';
                    if (customLimitInput) customLimitInput.required = true;
                } else {
                    customLimitDiv.style.display = 'none';
                    if (customLimitInput) customLimitInput.required = false;
                }
            }
            
            if (dailyLimitSelect) {
                dailyLimitSelect.addEventListener('change', updateCustomLimitVisibility);
                updateCustomLimitVisibility();
            }
            
            // Управление доступностью полей работы/учебы в расписании
            const combineCheckbox = document.getElementById('combineWorkStudy');
            const workInputs = document.querySelectorAll('.work-input');
            const studyInputs = document.querySelectorAll('.study-input');
            
            function updateScheduleAccess() {
                const isCombined = combineCheckbox.checked;
                
                workInputs.forEach(input => {
                    input.disabled = !isCombined;
                    const parent = input.closest('.time-input-group');
                    if (parent) {
                        parent.classList.toggle('disabled', !isCombined);
                    }
                });
                
                studyInputs.forEach(input => {
                    const parent = input.closest('.time-input-group');
                    if (parent) {
                        parent.classList.toggle('enabled', isCombined);
                    }
                });
            }
            
            if (combineCheckbox) {
                combineCheckbox.addEventListener('change', updateScheduleAccess);
                updateScheduleAccess(); // Инициализация
            }
            
            // Обработка выбора аватарки
            document.querySelectorAll('.avatar-option input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Удаляем класс selected у всех аватаров
                    document.querySelectorAll('.avatar-preview').forEach(preview => {
                        preview.classList.remove('selected');
                    });
                    
                    // Добавляем класс selected к выбранному аватару
                    if (this.checked) {
                        const avatarOption = this.closest('.avatar-option');
                        avatarOption.querySelector('.avatar-preview').classList.add('selected');
                        
                        // Обновляем предпросмотр текущего аватара
                        const avatarNumber = this.value;
                        const currentAvatarDiv = document.querySelector('.current-avatar');
                        
                        // Обновляем изображение или фолбэк
                        const avatarFile = `icon_acc/${avatarNumber}.ico`;
                        
                        // Проверяем наличие файла
                        fetch(avatarFile)
                            .then(response => {
                                if (response.ok) {
                                    // Файл существует, обновляем изображение
                                    const avatarImg = currentAvatarDiv.querySelector('img');
                                    if (avatarImg) {
                                        avatarImg.src = avatarFile;
                                        avatarImg.alt = `Аватар ${avatarNumber}`;
                                    } else {
                                        // Создаем изображение, если его нет
                                        const avatarFallback = currentAvatarDiv.querySelector('.avatar-fallback');
                                        if (avatarFallback) {
                                            avatarFallback.style.display = 'none';
                                        }
                                        const newImg = document.createElement('img');
                                        newImg.src = avatarFile;
                                        newImg.alt = `Аватар ${avatarNumber}`;
                                        newImg.className = 'avatar-img-large';
                                        currentAvatarDiv.insertBefore(newImg, currentAvatarDiv.querySelector('p'));
                                    }
                                } else {
                                    // Файл не существует, показываем фолбэк
                                    const avatarImg = currentAvatarDiv.querySelector('img');
                                    if (avatarImg) {
                                        avatarImg.style.display = 'none';
                                    }
                                    let avatarFallback = currentAvatarDiv.querySelector('.avatar-fallback');
                                    if (!avatarFallback) {
                                        avatarFallback = document.createElement('div');
                                        avatarFallback.className = 'avatar-fallback';
                                        avatarFallback.style.cssText = 'width: 120px; height: 120px; font-size: 36px; margin: 0 auto 10px;';
                                        currentAvatarDiv.insertBefore(avatarFallback, currentAvatarDiv.querySelector('p'));
                                    }
                                    avatarFallback.style.display = 'flex';
                                    avatarFallback.textContent = avatarNumber;
                                }
                            })
                            .catch(() => {
                                // Ошибка при проверке файла, показываем фолбэк
                                const avatarImg = currentAvatarDiv.querySelector('img');
                                if (avatarImg) {
                                    avatarImg.style.display = 'none';
                                }
                                let avatarFallback = currentAvatarDiv.querySelector('.avatar-fallback');
                                if (!avatarFallback) {
                                    avatarFallback = document.createElement('div');
                                    avatarFallback.className = 'avatar-fallback';
                                    avatarFallback.style.cssText = 'width: 120px; height: 120px; font-size: 36px; margin: 0 auto 10px;';
                                    currentAvatarDiv.insertBefore(avatarFallback, currentAvatarDiv.querySelector('p'));
                                }
                                avatarFallback.style.display = 'flex';
                                avatarFallback.textContent = avatarNumber;
                            });
                        
                        // Обновляем номер аватара
                        const avatarNumberText = currentAvatarDiv.querySelector('p');
                        if (avatarNumberText) {
                            avatarNumberText.textContent = `Аватар #${avatarNumber}`;
                        }
                    }
                });
            });
            
            // Валидация формы смены пароля
            const passwordForm = document.querySelector('#password-tab form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('Новые пароли не совпадают!');
                        return false;
                    }
                    
                    if (newPassword.length < 6) {
                        e.preventDefault();
                        alert('Новый пароль должен содержать минимум 6 символов!');
                        return false;
                    }
                    
                    return true;
                });
            }
            
           
                // Собираем данные энергозатратности
                document.querySelectorAll('.energy-slider').forEach(slider => {
                    const key = slider.id.replace('energy_', '');
                    userData.settings.energyLevels[key] = slider.value;
                });
            
            
            // Восстановить последнюю вкладку при загрузке
            window.addEventListener('load', function() {
                const lastTab = localStorage.getItem('lastTab') || 'overview';
                const tabBtn = document.querySelector(`.tab-btn[data-tab="${lastTab}"]`);
                if (tabBtn) {
                    tabBtn.click();
                }
            });
        });
    </script>
</body>
</html>