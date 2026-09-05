<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="image/icon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Умный планировщик</title>
    <style>
        /* Добавляем стили для выпадающего меню */
        .user-menu {
            position: relative;
            cursor: pointer;
        }
        
        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .dropdown-item {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
            color: #3498db;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-item.logout {
            color: #e74c3c;
        }
        
        .dropdown-item.logout:hover {
            background: #ffebee;
        }
        
        /* Стили для выполненных задач */
        .task-card.completed {
            opacity: 0.7;
            background: #f8f9fa;
            border-left: 4px solid #4CAF50;
        }
        
        .task-card.completed .task-title {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .complete-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            font-size: 16px;
            padding: 4px;
            transition: color 0.3s;
        }
        
        .complete-btn:hover {
            color: #4CAF50;
        }
        
        .complete-btn .fa-check-circle {
            color: #4CAF50;
        }
    </style>
</head>
<body>
<?php
session_start();
require_once 'bd.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем информацию о пользователе
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($bd, $sql);
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
    $fixedTasks[] = [
        'day_of_week' => $row['day_of_week'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'description' => $row['description']
    ];
}

// Получаем лимит пользователя
$daily_limit = $userData['daily_limit'] ?? 15;
?>

    <!-- Верхняя панель навигации -->
    <header class="header">
        <div class="header-left">
            <h1><i class="fas fa-calendar-alt"></i> Умный Планировщик</h1>
        </div>
        <div class="header-center">
            <button id="prev-week" class="nav-btn"><i class="fas fa-chevron-left"></i></button>
            <div class="week-display">
                <span id="current-week"><?php echo date('d.m.Y', strtotime('monday this week')) . ' - ' . date('d.m.Y', strtotime('sunday this week')); ?></span>
            </div>
            <button id="next-week" class="nav-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="header-right">
            <button id="add-task" class="btn-primary"><i class="fas fa-plus"></i> Добавить задачу</button>
            <button id="optimize" class="btn-secondary"><i class="fas fa-magic"></i> Оптимизировать</button>
            <div class="user-menu" id="user-menu">
                <span id="user-name"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></span>
                <i class="fas fa-user-circle"></i>
                <div class="user-dropdown" id="user-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <i class="fas fa-user"></i> Личный кабинет
                    </a>
                    <a href="javascript:void(0)" class="dropdown-item logout" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Основной контейнер -->
    <div class="container">
        <!-- Боковая панель с нераспределенными задачами -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-tasks"></i> Нераспределенные задачи</h3>
                <span class="badge" id="pending-count">0</span>
            </div>
            <div class="task-filters">
                <input type="text" id="task-search" placeholder="Поиск задач...">
                <select id="task-filter">
                    <option value="all">Все типы</option>
                    <option value="analytical">Аналитические</option>
                    <option value="creative">Творческие</option>
                    <option value="routine">Рутинные</option>
                    <option value="social">Социальные</option>
                    <option value="research">Исследовательские</option>
                    <option value="physical">Физические</option>
                    <option value="learning">Обучение</option>
                    <option value="planning">Планирование</option>
                </select>
            </div>
            <div class="pending-tasks" id="pending-tasks">
                <!-- Нераспределенные задачи будут добавляться сюда -->
            </div>
            <div class="sidebar-footer">
                <div class="daily-limit-info">
                    <i class="fas fa-chart-line"></i>
                    <span>Дневной лимит: <strong><?php echo $daily_limit; ?></strong> баллов</span>
                </div>
                <button id="clear-schedule" class="btn-warning"><i class="fas fa-trash"></i> Очистить расписание</button>
                <button id="export-btn" class="btn-secondary"><i class="fas fa-download"></i> Экспорт</button>
            </div>
        </aside>

        <!-- Основная область с недельным расписанием -->
        <main class="main-content">
            <div class="week-grid" id="week-grid">
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
                
                $currentDate = date('Y-m-d', strtotime('monday this week'));
                
                foreach ($days as $dayKey => $dayName):
                    $dayDate = date('Y-m-d', strtotime($dayKey . ' this week'));
                ?>
                <div class="day-column" data-day="<?php echo $dayKey; ?>" data-date="<?php echo $dayDate; ?>">
                    <div class="day-header">
                        <h3><?php echo $dayName; ?></h3>
                        <span class="day-date"><?php echo date('d.m', strtotime($dayDate)); ?></span>
                        <div class="day-stats">
                            <span class="energy-used">0/<?php echo $daily_limit; ?> баллов</span>
                        </div>
                    </div>
                    
                    <!-- Фиксированные блоки -->
                    <div class="fixed-blocks">
                        <!-- Работа -->
                        <?php if (isset($workSchedule[$dayKey]) && $workSchedule[$dayKey]['start_time'] != '00:00:00'): ?>
                        <div class="fixed-block work-block">
                            <i class="fas fa-briefcase"></i>
                            <span>Работа: <?php echo substr($workSchedule[$dayKey]['start_time'], 0, 5); ?> - <?php echo substr($workSchedule[$dayKey]['end_time'], 0, 5); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Учеба -->
                        <?php if (isset($studySchedule[$dayKey]) && $studySchedule[$dayKey]['start_time'] != '00:00:00'): ?>
                        <div class="fixed-block study-block">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Учеба: <?php echo substr($studySchedule[$dayKey]['start_time'], 0, 5); ?> - <?php echo substr($studySchedule[$dayKey]['end_time'], 0, 5); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Фиксированные задачи -->
                        <?php foreach ($fixedTasks as $task): 
                            if ($task['day_of_week'] == $dayKey): ?>
                        <div class="fixed-block task-block">
                            <i class="fas fa-calendar-check"></i>
                            <span>Задача: <?php echo substr($task['start_time'], 0, 5); ?> - <?php echo substr($task['end_time'], 0, 5); ?></span>
                        </div>
                        <?php endif;
                        endforeach; ?>
                    </div>
                    
                    <!-- Контейнер для задач -->
                    <div class="tasks-container" id="tasks-<?php echo $dayKey; ?>">
                        <!-- Задачи будут добавляться сюда -->
                    </div>
                    
                    <!-- Кнопка "Не могу сегодня" -->
                    <button class="cant-today-btn" data-day="<?php echo $dayKey; ?>">
                        <i class="fas fa-ban"></i> Не могу сегодня
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Модальное окно для добавления/редактирования задачи -->
    <div id="task-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Добавить новую задачу</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="task-form">
                    <input type="hidden" id="task-id">
                    <input type="hidden" id="task-user-id" value="<?php echo $user_id; ?>">
                    
                    <div class="form-section">
                        <h3>Основная информация</h3>
                        <div class="form-group">
                            <label for="task-name">Название задачи *</label>
                            <input type="text" id="task-name" required placeholder="Например: Подготовить отчет">
                        </div>
                        <div class="form-group">
                            <label for="task-type">Тип задачи *</label>
                            <select id="task-type" required>
                                <option value="">Выберите тип</option>
                                <option value="analytical">Аналитические</option>
                                <option value="creative">Творческие</option>
                                <option value="routine">Рутинные</option>
                                <option value="social">Социальные</option>
                                <option value="research">Исследовательские</option>
                                <option value="physical">Физические</option>
                                <option value="learning">Обучение</option>
                                <option value="planning">Планирование</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Приоритеты</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Срочность *</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="urgency" value="10" required> Критическая (10)</label>
                                    <label><input type="radio" name="urgency" value="7"> Высокая (7)</label>
                                    <label><input type="radio" name="urgency" value="4" checked> Средняя (4)</label>
                                    <label><input type="radio" name="urgency" value="1"> Низкая (1)</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Важность *</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="importance" value="10"> Высокая (10)</label>
                                    <label><input type="radio" name="importance" value="5" checked> Средняя (5)</label>
                                    <label><input type="radio" name="importance" value="2"> Низкая (2)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Время и детали</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="duration">Длительность (часы) *</label>
                                <input type="number" id="duration" min="0.5" max="8" step="0.5" value="1" required>
                            </div>
                            <div class="form-group">
                                <label for="deadline">Дедлайн</label>
                                <input type="date" id="deadline">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="preferred-day">Предпочтительный день</label>
                            <select id="preferred-day">
                                <option value="any">Любой день</option>
                                <option value="monday">Понедельник</option>
                                <option value="tuesday">Вторник</option>
                                <option value="wednesday">Среда</option>
                                <option value="thursday">Четверг</option>
                                <option value="friday">Пятница</option>
                                <option value="saturday">Суббота</option>
                                <option value="sunday">Воскресенье</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="preferred-time">Предпочтительное время</label>
                            <select id="preferred-time">
                                <option value="any">Любое</option>
                                <option value="morning">Утро (06:00-12:00)</option>
                                <option value="day">День (12:00-18:00)</option>
                                <option value="evening">Вечер (18:00-23:00)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Описание / Заметки</label>
                            <textarea id="description" rows="3" placeholder="Дополнительные детали..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="calculated-field">
                            <strong>Вес задачи:</strong>
                            <span id="task-weight">0</span> баллов
                        </div>
                        <small class="form-hint">Вес = (Срочность + Важность) × Энергозатратность типа задачи</small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary close-form">Отмена</button>
                        <button type="submit" class="btn-primary" id="submit-task-btn">Сохранить задачу</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для утреннего опроса -->
    <div id="morning-modal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2><i class="fas fa-sun"></i> Доброе утро!</h2>
            </div>
            <div class="modal-body">
                <p>Как вы себя чувствуете сегодня?</p>
                <div class="mood-options">
                    <button class="mood-btn" data-mood="good">
                        <i class="fas fa-smile"></i>
                        <span>Хорошо (100%)</span>
                    </button>
                    <button class="mood-btn" data-mood="tired">
                        <i class="fas fa-meh"></i>
                        <span>Устал (80%)</span>
                    </button>
                    <button class="mood-btn" data-mood="bad">
                        <i class="fas fa-frown"></i>
                        <span>Плохо (60%)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="javascript.js"></script>
    <script>
        // ТОЛЬКО передача данных из PHP в JavaScript
        // Вся логика находится в javascript.js
        window.userData = {
            id: <?php echo $user_id; ?>,
            firstName: '<?php echo $userData['first_name']; ?>',
            lastName: '<?php echo $userData['last_name']; ?>',
            email: '<?php echo $userData['email']; ?>',
            dailyLimit: <?php echo $daily_limit; ?>,
            energyLevels: <?php echo json_encode($energyData); ?>,
            workSchedule: <?php echo json_encode($workSchedule); ?>,
            studySchedule: <?php echo json_encode($studySchedule); ?>,
            fixedTasks: <?php echo json_encode($fixedTasks); ?>
        };
        
        // Текущая неделя для отображения
        window.currentWeekStart = '<?php echo date('Y-m-d', strtotime('monday this week')); ?>';
    </script>
</body>
</html>