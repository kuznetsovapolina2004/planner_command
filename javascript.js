document.addEventListener('DOMContentLoaded', function() {
    // Переменные состояния
    let currentWeek = 0; // 0 = текущая неделя
    let tasks = [];
    let pendingTasks = [];
    let dailyLimits = {};
    let isTaskSaving = false; // Флаг для предотвращения дублирования
    
    // DOM элементы
    const weekGrid = document.querySelector('.week-grid');
    const pendingTasksContainer = document.getElementById('pending-tasks');
    const pendingCount = document.getElementById('pending-count');
    const taskModal = document.getElementById('task-modal');
    const morningModal = document.getElementById('morning-modal');
    const taskForm = document.getElementById('task-form');
    const modalTitle = document.getElementById('modal-title');
    const taskWeightElement = document.getElementById('task-weight');
    
    // Элементы управления
    const addTaskBtn = document.getElementById('add-task');
    const optimizeBtn = document.getElementById('optimize');
    const clearScheduleBtn = document.getElementById('clear-schedule');
    const exportBtn = document.getElementById('export-btn');
    const prevWeekBtn = document.getElementById('prev-week');
    const nextWeekBtn = document.getElementById('next-week');
    const closeModalBtns = document.querySelectorAll('.close-btn, .close-form');
    const logoutBtn = document.getElementById('logout-btn');
    
    // Инициализация
    initApp();
    
    async function initApp() {
        console.log('Инициализация приложения...');
        
        // Проверяем данные пользователя
        if (!window.userData || !window.userData.id) {
            console.error('Нет данных пользователя!');
            showNotification('Ошибка загрузки данных пользователя', 'error');
            return;
        }
        
        // Проверяем, показывать ли утренний опрос
        checkMorningSurvey();
        
        // Инициализируем дневные лимиты
        initDailyLimits();
        
        // Загружаем задачи для текущей недели
        await loadTasksForWeek();
        
        // Отображаем неделю
        renderWeek();
        renderPendingTasks();
        
        // Настройка обработчиков событий
        setupEventListeners();
        
        // Инициализация перетаскивания
        initDragAndDrop();
        
        console.log('Приложение инициализировано');
    }
    
    function initDailyLimits() {
        const baseLimit = window.userData.dailyLimit || 25;
        dailyLimits = {
            monday: baseLimit,
            tuesday: baseLimit,
            wednesday: baseLimit,
            thursday: baseLimit,
            friday: baseLimit,
            saturday: Math.round(baseLimit * 0.8),
            sunday: Math.round(baseLimit * 0.6)
        };
    }
    
    function checkMorningSurvey() {
        const lastLogin = localStorage.getItem('lastLogin');
        const today = new Date().toDateString();
        
        if (lastLogin !== today) {
            setTimeout(() => {
                if (morningModal) morningModal.classList.add('active');
            }, 1000);
            localStorage.setItem('lastLogin', today);
        }
    }
    
    // Загрузка задач для текущей недели
    async function loadTasksForWeek() {
    try {
        if (!window.userData || !window.userData.id) {
            console.error('Нет данных пользователя для загрузки задач');
            return;
        }
        
        const weekDates = getWeekDates(currentWeek);
        const weekStart = weekDates[0].toISOString().split('T')[0];
        
        console.log(`Загрузка задач для недели: ${weekStart}`);
        
        const response = await fetch(`get_weekly_tasks.php?user_id=${window.userData.id}&week_start=${weekStart}`);
        const data = await response.json();
        
        if (data.success && data.tasks && Array.isArray(data.tasks)) {
            // Очищаем старые данные
            tasks = [];
            pendingTasks = [];
            
            // Обрабатываем задачи
            const processedTasks = data.tasks.map(task => ({
                ...task,
                weight: Math.round(parseFloat(task.weight) || 0),
                completed: task.completed == 1 // преобразуем в boolean
            }));
            
            // РАЗДЕЛЯЕМ НА ТРИ КАТЕГОРИИ:
            // 1. Выполненные задачи - НЕ показываем их вообще
            // 2. Распределенные и активные
            // 3. Нераспределенные
            
            // Фильтруем выполненные задачи - они не должны показываться
            const activeTasks = processedTasks.filter(task => !task.completed);
            
            // Разделяем активные задачи
            tasks = activeTasks.filter(task => task.scheduled);
            pendingTasks = activeTasks.filter(task => !task.scheduled);
            
            console.log(`Загружено: ${data.tasks.length} задач, активных: ${activeTasks.length} (${tasks.length} распределены, ${pendingTasks.length} нераспределены)`);
            
            // Сохраняем информацию о выполненных задачах для статистики
            const completedTasks = processedTasks.filter(task => task.completed);
            if (completedTasks.length > 0) {
                console.log(`Выполненных задач: ${completedTasks.length}`);
            }
        } else {
            console.warn('Нет задач в базе данных или ошибка загрузки:', data.message);
            tasks = [];
            pendingTasks = [];
        }
    } catch (error) {
        console.error('Ошибка загрузки задач:', error);
        tasks = [];
        pendingTasks = [];
        showNotification('Ошибка загрузки задач', 'error');
    }
}
    
    function setupEventListeners() {
        // Кнопки навигации по неделям
        prevWeekBtn.addEventListener('click', async () => {
            currentWeek--;
            await loadTasksForWeek();
            renderWeek();
            renderPendingTasks();
        });
        
        nextWeekBtn.addEventListener('click', async () => {
            currentWeek++;
            await loadTasksForWeek();
            renderWeek();
            renderPendingTasks();
        });
        
        // Открытие модального окна добавления задачи
        addTaskBtn.addEventListener('click', () => {
            openTaskModal();
        });
        
        // Оптимизация расписания
        optimizeBtn.addEventListener('click', optimizeSchedule);
        
        // Очистка расписания
        clearScheduleBtn.addEventListener('click', clearSchedule);
        
        // Экспорт
        exportBtn.addEventListener('click', exportSchedule);
        
        // Выход из системы
        if (logoutBtn) {
            logoutBtn.addEventListener('click', logout);
        }
        
        // Закрытие модальных окон
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (taskModal) taskModal.classList.remove('active');
                if (morningModal) morningModal.classList.remove('active');
            });
        });
        
        // Клик вне модального окна
        window.addEventListener('click', (e) => {
            if (e.target === taskModal) {
                taskModal.classList.remove('active');
            }
            if (e.target === morningModal) {
                morningModal.classList.remove('active');
            }
        });
        
        // Обработка формы задачи
        taskForm.addEventListener('submit', handleTaskSubmit);
        
        // Обновление веса задачи при изменении параметров
        const formInputs = taskForm.querySelectorAll('input, select');
        formInputs.forEach(input => {
            input.addEventListener('change', updateTaskWeight);
        });
        
        // Кнопки утреннего опроса
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const mood = this.getAttribute('data-mood');
                adjustDailyLimits(mood);
                if (morningModal) morningModal.classList.remove('active');
            });
        });
        
        // Поиск задач
        document.getElementById('task-search').addEventListener('input', filterPendingTasks);
        document.getElementById('task-filter').addEventListener('change', filterPendingTasks);
        
        // Делегирование событий для кнопок выполнения задач
        document.addEventListener('click', function(e) {
            // Кнопки выполнения задач
            if (e.target.closest('.complete-btn')) {
                const btn = e.target.closest('.complete-btn');
                const taskId = btn.dataset.taskId;
                toggleTaskComplete(taskId);
            }
            
            // Кнопки "Не могу сегодня"
            if (e.target.closest('.cant-today-btn')) {
                const btn = e.target.closest('.cant-today-btn');
                const day = btn.dataset.day;
                redistributeTasksFromDay(day);
            }
            
            // Выпадающее меню пользователя
            if (e.target.closest('#user-menu')) {
                const dropdown = document.getElementById('user-dropdown');
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            } else {
                const dropdown = document.getElementById('user-dropdown');
                if (dropdown && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });
    }
    
    function renderWeek() {
        if (!weekGrid) return;
        
        const weekDates = getWeekDates(currentWeek);
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
        
        weekGrid.innerHTML = '';
        
        // Обновляем отображение текущей недели
        const currentWeekElement = document.getElementById('current-week');
        if (currentWeekElement) {
            const startDate = formatDate(weekDates[0]);
            const endDate = formatDate(weekDates[6]);
            currentWeekElement.textContent = `${startDate} - ${endDate}`;
        }
        
        weekDates.forEach((date, index) => {
            const dayKey = weekDays[index];
            const dayTasks = tasks.filter(task => task.day === dayKey);
            const totalWeight = dayTasks.reduce((sum, task) => sum + task.weight, 0);
            const loadPercentage = Math.min(100, (totalWeight / dailyLimits[dayKey]) * 100);
            
            const dayColumn = document.createElement('div');
            dayColumn.className = 'day-column';
            dayColumn.dataset.day = dayKey;
            dayColumn.dataset.date = date.toISOString().split('T')[0];
            
            dayColumn.innerHTML = `
                <div class="day-header">
                    <div class="day-name">${dayNames[index]}</div>
                    <div class="day-date">${formatDate(date)}</div>
                    <div class="day-load">
                        <span>Загрузка:</span>
                        <span><strong>${totalWeight}/${dailyLimits[dayKey]}</strong> баллов</span>
                    </div>
                    <div class="load-bar">
                        <div class="load-fill" style="width: ${loadPercentage}%"></div>
                    </div>
                </div>
                <div class="tasks-container" id="tasks-${dayKey}">
                    ${renderDayTasks(dayTasks)}
                </div>
                <button class="cant-today-btn" data-day="${dayKey}">
                    <i class="fas fa-ban"></i> Не могу сегодня
                </button>
            `;
            
            weekGrid.appendChild(dayColumn);
        });
        
        // Настройка зон перетаскивания
        setupDropZones();
    }
    
    function renderDayTasks(dayTasks) {
        if (dayTasks.length === 0) {
            return '<div class="empty-day">Нет запланированных задач</div>';
        }
        
        // Сортируем задачи по времени
        dayTasks.sort((a, b) => {
            const timeA = a.scheduled_time || '00:00';
            const timeB = b.scheduled_time || '00:00';
            return timeA.localeCompare(timeB);
        });
        
        return dayTasks.map(task => {
            const isCompleted = task.completed || false;
            
            return `
                <div class="task-card draggable ${isCompleted ? 'completed' : ''}" 
                     draggable="true" data-task-id="${task.id}">
                    <div class="task-header">
                        <div class="task-title">${task.name}</div>
                        <div class="task-points">${task.weight} баллов</div>
                        <button class="complete-btn" data-task-id="${task.id}" 
                                title="${isCompleted ? 'Выполнено' : 'Отметить выполненной'}">
                            <i class="fas ${isCompleted ? 'fa-check-circle' : 'fa-circle'}"></i>
                        </button>
                    </div>
                    <div class="task-time">${task.scheduled_time || '--:--'}</div>
                    <div class="task-meta">
                        <span class="task-type ${task.type}">${getTypeName(task.type)}</span>
                        <span class="task-duration">${task.duration}ч</span>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function renderPendingTasks() {
        if (!pendingTasksContainer) return;
        
        pendingTasksContainer.innerHTML = '';
        
        if (pendingTasks.length === 0) {
            pendingTasksContainer.innerHTML = '<div class="empty-tasks">Все задачи распределены!</div>';
            if (pendingCount) pendingCount.textContent = '0';
            return;
        }
        
        pendingTasks.forEach(task => {
            const taskElement = document.createElement('div');
            taskElement.className = 'pending-task draggable';
            taskElement.draggable = true;
            taskElement.dataset.taskId = task.id;
            
            taskElement.innerHTML = `
                <div class="task-header">
                    <div class="task-title">${task.name}</div>
                    <div class="task-points">${task.weight} баллов</div>
                </div>
                <div class="task-meta">
                    <span class="task-type ${task.type}">${getTypeName(task.type)}</span>
                    <span>Срочность: ${task.urgency}/10</span>
                </div>
                ${task.deadline ? `<div class="task-deadline">До: ${formatDate(new Date(task.deadline))}</div>` : ''}
                <div class="task-actions">
                    <button class="btn-icon edit-pending-task" data-task-id="${task.id}" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete-pending-task" data-task-id="${task.id}" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            pendingTasksContainer.appendChild(taskElement);
        });
        
        if (pendingCount) pendingCount.textContent = pendingTasks.length.toString();
        
        // Добавляем обработчики для кнопок редактирования и удаления
        document.querySelectorAll('.edit-pending-task').forEach(btn => {
            btn.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                openTaskModalForEdit(taskId);
            });
        });
        
        document.querySelectorAll('.delete-pending-task').forEach(btn => {
            btn.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                deleteTask(taskId);
            });
        });
        
        // Настройка перетаскивания для новых элементов
        initDragAndDrop();
    }
    
    function filterPendingTasks() {
        const searchTerm = document.getElementById('task-search').value.toLowerCase();
        const filterType = document.getElementById('task-filter').value;
        
        const filteredTasks = pendingTasks.filter(task => {
            const matchesSearch = task.name.toLowerCase().includes(searchTerm) ||
                                 (task.description && task.description.toLowerCase().includes(searchTerm));
            const matchesType = filterType === 'all' || task.type === filterType;
            
            return matchesSearch && matchesType;
        });
        
        // Временное отображение отфильтрованных задач
        pendingTasksContainer.innerHTML = '';
        
        filteredTasks.forEach(task => {
            const taskElement = document.createElement('div');
            taskElement.className = 'pending-task';
            taskElement.dataset.taskId = task.id;
            
            taskElement.innerHTML = `
                <div class="task-header">
                    <div class="task-title">${task.name}</div>
                    <div class="task-points">${task.weight} баллов</div>
                </div>
                <div class="task-meta">
                    <span class="task-type ${task.type}">${getTypeName(task.type)}</span>
                    <span>Срочность: ${task.urgency}/10</span>
                </div>
            `;
            
            pendingTasksContainer.appendChild(taskElement);
        });
        
        if (filteredTasks.length === 0) {
            pendingTasksContainer.innerHTML = '<div class="empty-tasks">Задачи не найдены</div>';
        }
    }
    
    function openTaskModal(editingTask = null) {
        if (!taskModal) return;
        
        if (editingTask) {
            modalTitle.textContent = 'Редактировать задачу';
            populateTaskForm(editingTask);
        } else {
            modalTitle.textContent = 'Добавить новую задачу';
            taskForm.reset();
            document.getElementById('preferred-day').value = 'any';
            document.getElementById('preferred-time').value = 'any';
            updateTaskWeight();
        }
        
        taskModal.classList.add('active');
    }
    
    function openTaskModalForEdit(taskId) {
        const task = [...tasks, ...pendingTasks].find(t => t.id == taskId);
        if (task) {
            openTaskModal(task);
        }
    }
    
    function populateTaskForm(task) {
        document.getElementById('task-id').value = task.id;
        document.getElementById('task-name').value = task.name;
        document.getElementById('task-type').value = task.type;
        document.getElementById('description').value = task.description || '';
        document.getElementById('duration').value = task.duration;
        document.getElementById('deadline').value = task.deadline || '';
        document.getElementById('preferred-day').value = task.preferred_day || 'any';
        document.getElementById('preferred-time').value = task.preferred_time || 'any';
        
        // Устанавливаем срочность
        const urgencyRadio = document.querySelector(`input[name="urgency"][value="${task.urgency}"]`);
        if (urgencyRadio) urgencyRadio.checked = true;
        
        // Устанавливаем важность
        const importanceRadio = document.querySelector(`input[name="importance"][value="${task.importance}"]`);
        if (importanceRadio) importanceRadio.checked = true;
        
        updateTaskWeight();
    }
    
    function updateTaskWeight() {
        const urgency = parseInt(document.querySelector('input[name="urgency"]:checked')?.value || 4);
        const importance = parseInt(document.querySelector('input[name="importance"]:checked')?.value || 5);
        const taskType = document.getElementById('task-type').value;
        
        // Получаем энергозатратность типа задачи
        let energyCoefficient = 0.5;
        if (window.userData && window.userData.energyLevels && window.userData.energyLevels[taskType]) {
            energyCoefficient = window.userData.energyLevels[taskType] / 10.0;
        }
        
        const duration = parseFloat(document.getElementById('duration').value || 1);
        const durationMinutes = duration * 60;
        
        // Расчет баллов за длительность
        let durationScore = 1;
        if (durationMinutes > 30 && durationMinutes <= 120) {
            durationScore = 3;
        } else if (durationMinutes > 120) {
            durationScore = 6;
        }
        
        // Формула: (срочность + важность) × энергозатратность + баллы_длительности
        const weight = (urgency + importance) * energyCoefficient + durationScore;
        
        // Округляем до целого числа
        if (taskWeightElement) {
            taskWeightElement.textContent = Math.round(weight);
        }
    }
    
    async function handleTaskSubmit(e) {
        e.preventDefault();
        
        if (isTaskSaving) {
            console.log('Задача уже сохраняется...');
            return;
        }
        
        isTaskSaving = true;
        const submitBtn = document.getElementById('submit-task-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Сохранение...';
        
        try {
            const taskId = document.getElementById('task-id').value;
            const taskData = {
                name: document.getElementById('task-name').value,
                type: document.getElementById('task-type').value,
                urgency: parseInt(document.querySelector('input[name="urgency"]:checked').value),
                importance: parseInt(document.querySelector('input[name="importance"]:checked').value),
                duration: parseFloat(document.getElementById('duration').value),
                deadline: document.getElementById('deadline').value || null,
                preferred_day: document.getElementById('preferred-day').value,
                preferred_time: document.getElementById('preferred-time').value,
                description: document.getElementById('description').value
            };
            
            const url = taskId ? 'update_task.php' : 'add_task.php';
            const method = taskId ? 'PUT' : 'POST';
            
            const requestData = taskId ? 
                { task_id: taskId, ...taskData } : 
                taskData;
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                taskModal.classList.remove('active');
                
                // Перезагружаем задачи
                await loadTasksForWeek();
                renderWeek();
                renderPendingTasks();
                
                showNotification(`Задача "${taskData.name}" ${taskId ? 'обновлена' : 'добавлена'}!`, 'success');
            } else {
                showNotification(`Ошибка: ${result.message}`, 'error');
            }
        } catch (error) {
            console.error('Ошибка при сохранении задачи:', error);
            showNotification('Ошибка при сохранении задачи', 'error');
        } finally {
            isTaskSaving = false;
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
    
    async function deleteTask(taskId) {
        if (!confirm('Вы уверены, что хотите удалить эту задачу?')) {
            return;
        }
        
        try {
            const response = await fetch('delete_task.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ task_id: taskId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadTasksForWeek();
                renderWeek();
                renderPendingTasks();
                
                showNotification('Задача удалена!', 'success');
            } else {
                showNotification(`Ошибка: ${result.message}`, 'error');
            }
        } catch (error) {
            console.error('Ошибка при удалении задачи:', error);
            showNotification('Ошибка при удалении задачи', 'error');
        }
    }
    
    async function toggleTaskComplete(taskId) {
    try {
        // Находим задачу
        const task = [...tasks, ...pendingTasks].find(t => t.id == taskId);
        if (!task) return;
        
        // Определяем новый статус
        const newStatus = task.completed ? 0 : 1;
        
        const response = await fetch('update_task_completed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                task_id: taskId,
                completed: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadTasksForWeek();
            renderWeek();
            renderPendingTasks();
            
            showNotification(`Задача ${newStatus ? 'отмечена выполненной' : 'возвращена в работу'}`, 'success');
        } else {
            showNotification(`Ошибка: ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Ошибка при обновлении статуса задачи:', error);
        showNotification('Ошибка при обновлении статуса', 'error');
    }
}
    
    function optimizeSchedule() {
        console.log('Оптимизация расписания...');
        console.log('Нераспределенных задач:', pendingTasks.length);
        
        if (pendingTasks.length === 0) {
            showNotification('Нет задач для оптимизации!', 'info');
            return;
        }
        
        runOptimization();
    }
    
  async function runOptimization() {
    try {
        showNotification('Начинаем оптимизацию расписания...', 'info');
        
        // Получаем даты текущей отображаемой недели
        const weekDates = getWeekDates(currentWeek);
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Создаем маппинг дня недели на дату
        const dayToDateMap = {};
        weekDays.forEach((day, index) => {
            dayToDateMap[day] = weekDates[index];
        });
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        console.log('Текущая неделя:', {
            start: weekDates[0].toISOString().split('T')[0],
            end: weekDates[6].toISOString().split('T')[0],
            today: today.toISOString().split('T')[0],
            days: weekDays.map((day, i) => ({ day, date: weekDates[i].toISOString().split('T')[0] }))
        });
        
        // 1. Сортируем задачи по приоритету с учетом дедлайна
        const sortedTasks = [...pendingTasks].sort((a, b) => {
            // Приоритет: просроченные → с дедлайном → без дедлайна
            const getTaskPriority = (task) => {
                let priority = task.weight * task.urgency * 10;
                
                if (task.deadline) {
                    const deadlineDate = new Date(task.deadline);
                    deadlineDate.setHours(0, 0, 0, 0);
                    const daysUntilDeadline = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
                    
                    if (daysUntilDeadline < 0) {
                        priority += 100000; // Просроченные - самый высокий приоритет
                    } else if (daysUntilDeadline === 0) {
                        priority += 80000; // Сегодня
                    } else if (daysUntilDeadline <= 3) {
                        priority += 60000; // В течение 3 дней
                    } else if (daysUntilDeadline <= 7) {
                        priority += 40000; // В течение недели
                    } else {
                        priority += 20000; // Дальше недели
                    }
                }
                
                return priority;
            };
            
            return getTaskPriority(b) - getTaskPriority(a);
        });
        
        console.log('Сортировка задач по приоритету:');
        sortedTasks.forEach((task, i) => {
            console.log(`${i+1}. "${task.name}" - дедлайн: ${task.deadline || 'нет'}, вес: ${task.weight}, срочность: ${task.urgency}`);
        });
        
        // Массив для хранения локально запланированных задач
        const newlyScheduledTasks = [];
        const placementResults = [];
        
        // 2. Распределяем каждую задачу
        for (const task of sortedTasks) {
            let placed = false;
            const preferredDay = task.preferred_day || 'any';
            
            console.log(`\n=== Распределение задачи: "${task.name}" ===`);
            console.log(`Дедлайн: ${task.deadline || 'нет'}, Предпочтительный день: ${preferredDay}`);
            
            // Определяем допустимые дни С УЧЕТОМ ДЕДЛАЙНА
            let allowedDays = [];
            
            if (task.deadline) {
                const deadlineDate = new Date(task.deadline);
                deadlineDate.setHours(0, 0, 0, 0);
                
                console.log(`Дедлайн задачи: ${deadlineDate.toISOString().split('T')[0]}`);
                
                // Проверяем каждый день недели
                for (const day of weekDays) {
                    const dayDate = dayToDateMap[day];
                    dayDate.setHours(0, 0, 0, 0);
                    
                    // День подходит если его дата НЕ позже дедлайна
                    // И для текущей недели (currentWeek === 0) - день не в прошлом
                    const isBeforeDeadline = dayDate <= deadlineDate;
                    const isNotPast = currentWeek !== 0 || dayDate >= today;
                    
                    if (isBeforeDeadline && isNotPast) {
                        allowedDays.push(day);
                        console.log(`  День ${day} (${dayDate.toISOString().split('T')[0]}) подходит`);
                    } else {
                        console.log(`  День ${day} (${dayDate.toISOString().split('T')[0]}) не подходит: ${!isBeforeDeadline ? 'после дедлайна' : 'в прошлом'}`);
                    }
                }
                
                // Если нет допустимых дней
                if (allowedDays.length === 0) {
                    const reason = deadlineDate < today ? 'просроченный дедлайн' : 'дедлайн раньше доступных дней';
                    placementResults.push({
                        task: task,
                        success: false,
                        reason: 'deadline_issue',
                        message: `Задача "${task.name}" не может быть распределена: ${reason} (${formatDateFull(task.deadline)})`
                    });
                    console.log(`❌ Задача не может быть распределена: ${reason}`);
                    continue;
                }
                
                console.log(`Допустимые дни (до дедлайна ${formatDateFull(task.deadline)}): ${allowedDays.map(d => getDayName(d)).join(', ')}`);
            } else {
                // Без дедлайна - все дни доступны (кроме прошедших для текущей недели)
                allowedDays = weekDays.filter(day => {
                    if (currentWeek === 0) {
                        const dayDate = dayToDateMap[day];
                        dayDate.setHours(0, 0, 0, 0);
                        return dayDate >= today;
                    }
                    return true;
                });
                
                console.log(`Допустимые дни (без дедлайна): ${allowedDays.map(d => getDayName(d)).join(', ')}`);
            }
            
            // Упорядочиваем дни для проверки
            let daysToCheck = [...allowedDays];
            
            // Приоритет: предпочтительный день → дни по порядку
            if (preferredDay !== 'any' && allowedDays.includes(preferredDay)) {
                daysToCheck = [preferredDay, ...allowedDays.filter(day => day !== preferredDay)];
            } else if (preferredDay !== 'any') {
                console.log(`Предпочтительный день "${preferredDay}" недоступен`);
            }
            
            // Для текущей недели начинаем с сегодняшнего дня
            if (currentWeek === 0) {
                const todayDayName = getTodayDayName();
                const todayIndex = daysToCheck.indexOf(todayDayName);
                if (todayIndex > -1) {
                    // Ставим сегодняшний день первым
                    daysToCheck = [
                        todayDayName,
                        ...daysToCheck.filter(day => day !== todayDayName)
                    ];
                }
            }
            
            console.log(`Порядок проверки дней: ${daysToCheck.map(d => getDayName(d)).join(' → ')}`);
            
            // Пробуем разместить в подходящий день
            for (const day of daysToCheck) {
                // Проверка лимита
                const currentDayTasks = [
                    ...tasks.filter(t => t.day === day),
                    ...newlyScheduledTasks.filter(t => t.day === day)
                ];
                const currentWeight = currentDayTasks.reduce((sum, t) => sum + t.weight, 0);
                const dayLimit = dailyLimits[day] || window.userData.dailyLimit;
                
                if (currentWeight + task.weight > dayLimit) {
                    console.log(`  День ${getDayName(day)}: превышен лимит (${currentWeight + task.weight}/${dayLimit})`);
                    continue;
                }
                
                // Расчет времени
                const time = calculateTaskTimeWithScheduleOptimization(
                    day,
                    task.duration,
                    task.preferred_time || 'any',
                    newlyScheduledTasks
                );
                
                if (!time) {
                    console.log(`  День ${getDayName(day)}: нет свободного времени`);
                    continue;
                }
                
                // Планирование через API
                try {
                    const weekStart = weekDates[0].toISOString().split('T')[0];
                    
                    const response = await fetch('schedule_task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: task.id,
                            day: day,
                            time: time,
                            week_start: weekStart
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        placed = true;
                        
                        // Добавляем в локальный массив
                        newlyScheduledTasks.push({
                            ...task,
                            day: day,
                            scheduled_time: time,
                            scheduled_date: result.scheduled_date
                        });
                        
                        placementResults.push({
                            task: task,
                            success: true,
                            day: day,
                            time: time,
                            date: dayToDateMap[day].toISOString().split('T')[0],
                            message: `Задача "${task.name}" запланирована на ${getDayName(day)} (${formatDate(dayToDateMap[day])}) в ${time}`
                        });
                        
                        console.log(`✅ Задача запланирована на ${getDayName(day)} в ${time}`);
                        break;
                    }
                } catch (error) {
                    console.error('Ошибка при планировании:', error);
                }
            }
            
            if (!placed) {
                const deadlineInfo = task.deadline ? ` (дедлайн: ${formatDateFull(task.deadline)})` : '';
                placementResults.push({
                    task: task,
                    success: false,
                    reason: 'no_space',
                    message: `Задача "${task.name}"${deadlineInfo} не может быть распределена: дни перегружены или нет времени`
                });
                console.log(`❌ Задача не распределена: дни перегружены или нет времени`);
            }
        }
        
        // 3. Обновляем интерфейс
        await loadTasksForWeek();
        renderWeek();
        renderPendingTasks();
        
        // 4. Показываем результаты
        const successful = placementResults.filter(r => r.success).length;
        const total = sortedTasks.length;
        
        if (successful > 0) {
            const successMessage = `Оптимизация завершена! ${formatTaskMessage(successful, 'распределено')} из ${total}`;
            
            // Проверяем проблемы с дедлайнами
            const deadlineProblems = placementResults.filter(r => 
                !r.success && r.task.deadline
            ).length;
            
            if (deadlineProblems > 0) {
                showNotification(`${successMessage} (${deadlineProblems} с проблемами дедлайна)`, 'warning', 5000);
                
                // Детальные уведомления
                setTimeout(() => {
                    placementResults
                        .filter(r => !r.success && r.task.deadline)
                        .slice(0, 2)
                        .forEach((result, index) => {
                            setTimeout(() => {
                                showNotification(result.message, 'error', 4000);
                            }, index * 1500);
                        });
                }, 1000);
            } else {
                showNotification(successMessage, 'success', 4000);
            }
        } else if (total > 0) {
            showNotification('Ни одна задача не распределена', 'error', 4000);
            
            // Показываем причины
            setTimeout(() => {
                const deadlineTasks = placementResults.filter(r => 
                    !r.success && r.reason === 'deadline_issue'
                );
                
                if (deadlineTasks.length > 0) {
                    showNotification(
                        `${deadlineTasks.length} ${pluralize(deadlineTasks.length, 'задача', 'задачи', 'задач')} с проблемами дедлайна`,
                        'error',
                        5000
                    );
                }
            }, 1000);
        } else {
            showNotification('Нет задач для оптимизации', 'info', 3000);
        }
        
        // 5. Логирование для отладки
        console.log('\n=== ИТОГИ ОПТИМИЗАЦИИ ===');
        console.log(`Всего задач: ${total}`);
        console.log(`Успешно: ${successful}`);
        console.log(`Не удалось: ${total - successful}`);
        
        placementResults.forEach((result, i) => {
            console.log(`${i+1}. "${result.task.name}": ${result.success ? '✅' : '❌'} ${result.message}`);
        });
        
    } catch (error) {
        console.error('Ошибка оптимизации:', error);
        showNotification('Ошибка при оптимизации: ' + error.message, 'error', 5000);
    }
}

// Добавьте вспомогательную функцию для получения сегодняшнего дня недели
function getTodayDayName() {
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const today = new Date().getDay();
    return days[today];
}

// НОВАЯ функция для расчета времени с учетом задач, запланированных в текущей оптимизации
function calculateTaskTimeWithScheduleOptimization(day, durationHours, preferredTime = 'any', newlyScheduledTasks = []) {
    console.log(`=== РАСЧЕТ ВРЕМЕНИ ДЛЯ ОПТИМИЗАЦИИ: ${day}, длительность: ${durationHours}ч ===`);
    
    // 1. Получаем ВСЕ задачи на этот день (старые + новые из этой оптимизации)
    const existingDayTasks = tasks.filter(task => task.day === day && task.scheduled_time);
    const newDayTasks = newlyScheduledTasks.filter(task => task.day === day && task.scheduled_time);
    const allDayTasks = [...existingDayTasks, ...newDayTasks];
    
    console.log(`Задач на день ${day}: существующие=${existingDayTasks.length}, новые=${newDayTasks.length}, всего=${allDayTasks.length}`);
    
    // 2. Собираем ВСЕ блоки времени
    const timeBlocks = [];
    
    // Добавляем фиксированные блоки (работа, учеба)
    if (window.userData.workSchedule && window.userData.workSchedule[day]) {
        const workStart = window.userData.workSchedule[day].start_time.substring(0, 5);
        const workEnd = window.userData.workSchedule[day].end_time.substring(0, 5);
        if (workStart !== '00:00' && workEnd !== '00:00') {
            timeBlocks.push({
                start: workStart,
                end: workEnd,
                type: 'work',
                fixed: true
            });
            console.log(`Фиксированное: работа ${workStart}-${workEnd}`);
        }
    }
    
    if (window.userData.studySchedule && window.userData.studySchedule[day]) {
        const studyStart = window.userData.studySchedule[day].start_time.substring(0, 5);
        const studyEnd = window.userData.studySchedule[day].end_time.substring(0, 5);
        if (studyStart !== '00:00' && studyEnd !== '00:00') {
            timeBlocks.push({
                start: studyStart,
                end: studyEnd,
                type: 'study',
                fixed: true
            });
            console.log(`Фиксированное: учеба ${studyStart}-${studyEnd}`);
        }
    }
    
    // Фиксированные задачи пользователя
    if (window.userData.fixedTasks) {
        window.userData.fixedTasks.forEach(task => {
            if (task.day_of_week === day) {
                const taskStart = task.start_time.substring(0, 5);
                const taskEnd = task.end_time.substring(0, 5);
                if (taskStart !== '00:00' && taskEnd !== '00:00') {
                    timeBlocks.push({
                        start: taskStart,
                        end: taskEnd,
                        type: 'fixed_task',
                        fixed: true
                    });
                    console.log(`Фиксированное: задача ${taskStart}-${taskEnd}`);
                }
            }
        });
    }
    
    // Добавляем все запланированные задачи (старые и новые)
    allDayTasks.forEach(task => {
        if (task.scheduled_time) {
            const taskStart = task.scheduled_time;
            const taskDuration = task.duration || 1;
            const taskEndMinutes = timeToMinutes(taskStart) + Math.ceil(taskDuration * 60);
            const taskEnd = minutesToTime(taskEndMinutes);
            
            timeBlocks.push({
                start: taskStart,
                end: taskEnd,
                type: 'task',
                taskId: task.id,
                fixed: false,
                duration: taskDuration
            });
            console.log(`Запланированная задача: ${taskStart}-${taskEnd} (${taskDuration}ч)`);
        }
    });
    
    // Если нет блоков, начинаем с утра
    if (timeBlocks.length === 0) {
        console.log('Нет блоков, начинаем с 09:00');
        const candidateTime = '09:00';
        
        // Проверяем предпочтительное время
        if (preferredTime !== 'any') {
            const preferredSlot = getPreferredTimeSlot(preferredTime);
            const slotStart = timeToMinutes(preferredSlot.start);
            const candidateMinutes = timeToMinutes(candidateTime);
            
            if (candidateMinutes >= slotStart) {
                console.log(`✓ Нашли время с учетом предпочтений: ${candidateTime}`);
                return candidateTime;
            } else {
                // Возвращаем начало предпочтительного слота
                console.log(`✓ Используем начало предпочтительного слота: ${preferredSlot.start}`);
                return preferredSlot.start;
            }
        }
        return candidateTime;
    }
    
    // Преобразуем все время в минуты и сортируем
    const blocksInMinutes = timeBlocks.map(block => ({
        ...block,
        startMinutes: timeToMinutes(block.start),
        endMinutes: timeToMinutes(block.end)
    }));
    
    blocksInMinutes.sort((a, b) => a.startMinutes - b.startMinutes);
    
    // Параметры рабочего дня
    const workDayStart = 9 * 60; // 9:00
    const workDayEnd = 22 * 60;  // 22:00
    const taskDurationMinutes = Math.ceil(durationHours * 60);
    const breakTime = 15; // 15 минут перерыва
    
    console.log(`Ищем место для задачи (${taskDurationMinutes}мин)`);
    
    // Ищем свободное время между блоками
    let lastEndTime = workDayStart;
    
    for (const block of blocksInMinutes) {
        console.log(`Проверяем блок: ${block.start} (${block.startMinutes})-${block.end} (${block.endMinutes})`);
        
        // Проверяем промежуток между концом последнего блока и началом текущего
        const gapStart = lastEndTime;
        const gapEnd = block.startMinutes;
        const gapSize = gapEnd - gapStart;
        
        console.log(`  Промежуток: ${minutesToTime(gapStart)}-${minutesToTime(gapEnd)} (${gapSize} мин)`);
        
        if (gapSize >= taskDurationMinutes + breakTime) {
            // Нашли достаточно места!
            const candidateTime = minutesToTime(gapStart);
            
            // Проверяем предпочтительное время
            if (preferredTime !== 'any') {
                const preferredSlot = getPreferredTimeSlot(preferredTime);
                const slotStart = timeToMinutes(preferredSlot.start);
                const slotEnd = timeToMinutes(preferredSlot.end);
                
                if (gapStart >= slotStart && gapStart + taskDurationMinutes <= slotEnd) {
                    console.log(`  ✓ Нашли время в предпочтительном слоте: ${candidateTime}`);
                    return candidateTime;
                }
            } else {
                console.log(`  ✓ Нашли свободное время: ${candidateTime}`);
                return candidateTime;
            }
        }
        
        // Обновляем последнее время окончания
        lastEndTime = Math.max(lastEndTime, block.endMinutes + breakTime);
        console.log(`  Обновляем последнее время: ${minutesToTime(lastEndTime)}`);
    }
    
    // Проверяем время после последнего блока
    if (lastEndTime + taskDurationMinutes <= workDayEnd) {
        const candidateTime = minutesToTime(lastEndTime);
        console.log(`  ✓ Время после последнего блока: ${candidateTime}`);
        return candidateTime;
    }
    
    // Если ничего не нашли
    console.log(`  ✗ Не нашли свободного времени на день ${day}`);
    return null;
}

// Добавьте эту функцию для форматирования полной даты
function formatDateFull(dateStr) {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    } catch (e) {
        return dateStr;
    }
}
function formatDateFull(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

// Вспомогательная функция для получения текущего дня недели
function getTodayDayOfWeek() {
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const today = new Date().getDay();
    return days[today];
}
    // Функция для расчета времени задачи с учетом фиксированного расписания
function calculateTaskTimeWithSchedule(day, durationHours, preferredTime = 'any') {
    console.log(`=== РАСЧЕТ ВРЕМЕНИ для ${day}, длительность: ${durationHours}ч ===`);
    
    // 1. Получаем ВСЕ запланированные задачи на этот день (включая те, что только что запланировали)
    const dayTasks = tasks.filter(task => task.day === day && task.scheduled_time);
    console.log(`Уже запланировано задач на ${day}:`, dayTasks.length);
    
    // 2. Собираем ВСЕ блоки времени
    const timeBlocks = [];
    
    // Добавляем фиксированные блоки
    // Работа
    if (window.userData.workSchedule && window.userData.workSchedule[day]) {
        const workStart = window.userData.workSchedule[day].start_time.substring(0, 5);
        const workEnd = window.userData.workSchedule[day].end_time.substring(0, 5);
        if (workStart !== '00:00' && workEnd !== '00:00') {
            timeBlocks.push({
                start: workStart,
                end: workEnd,
                type: 'work',
                fixed: true
            });
            console.log(`Фиксированное: работа ${workStart}-${workEnd}`);
        }
    }
    
    // Учеба
    if (window.userData.studySchedule && window.userData.studySchedule[day]) {
        const studyStart = window.userData.studySchedule[day].start_time.substring(0, 5);
        const studyEnd = window.userData.studySchedule[day].end_time.substring(0, 5);
        if (studyStart !== '00:00' && studyEnd !== '00:00') {
            timeBlocks.push({
                start: studyStart,
                end: studyEnd,
                type: 'study',
                fixed: true
            });
            console.log(`Фиксированное: учеба ${studyStart}-${studyEnd}`);
        }
    }
    
    // Фиксированные задачи пользователя
    if (window.userData.fixedTasks) {
        window.userData.fixedTasks.forEach(task => {
            if (task.day_of_week === day) {
                const taskStart = task.start_time.substring(0, 5);
                const taskEnd = task.end_time.substring(0, 5);
                if (taskStart !== '00:00' && taskEnd !== '00:00') {
                    timeBlocks.push({
                        start: taskStart,
                        end: taskEnd,
                        type: 'fixed_task',
                        fixed: true
                    });
                    console.log(`Фиксированное: задача ${taskStart}-${taskEnd}`);
                }
            }
        });
    }
    
    // Добавляем уже запланированные задачи на этот день
    dayTasks.forEach(task => {
        if (task.scheduled_time) {
            const taskStart = task.scheduled_time;
            const taskDuration = task.duration || 1;
            const taskEndMinutes = timeToMinutes(taskStart) + Math.ceil(taskDuration * 60);
            const taskEnd = minutesToTime(taskEndMinutes);
            
            timeBlocks.push({
                start: taskStart,
                end: taskEnd,
                type: 'task',
                taskId: task.id,
                fixed: false,
                duration: taskDuration
            });
            console.log(`Запланированная задача: ${taskStart}-${taskEnd} (${taskDuration}ч)`);
        }
    });
    
    // Сортируем блоки по времени начала
    const sortedBlocks = [...timeBlocks].sort((a, b) => 
        timeToMinutes(a.start) - timeToMinutes(b.start)
    );
    
    // Параметры рабочего дня
    const workDayStart = 9 * 60; // 9:00
    const workDayEnd = 22 * 60;  // 22:00
    const taskDurationMinutes = Math.ceil(durationHours * 60);
    const breakTime = 15; // 15 минут перерыва
    
    // Проверяем промежутки между блоками
    let lastEndTime = workDayStart;
    
    for (const block of sortedBlocks) {
        const blockStartMinutes = timeToMinutes(block.start);
        const blockEndMinutes = timeToMinutes(block.end);
        
        // Проверяем промежуток между последним временем и началом текущего блока
        if (blockStartMinutes - lastEndTime >= taskDurationMinutes + breakTime) {
            // Нашли подходящее время
            const candidateTime = minutesToTime(lastEndTime);
            
            // Проверяем предпочтительное время
            if (preferredTime !== 'any') {
                const preferredSlot = getPreferredTimeSlot(preferredTime);
                const slotStart = timeToMinutes(preferredSlot.start);
                const slotEnd = timeToMinutes(preferredSlot.end);
                
                if (lastEndTime >= slotStart && lastEndTime + taskDurationMinutes <= slotEnd) {
                    console.log(`✓ Нашли время в предпочтительном слоте: ${candidateTime}`);
                    return candidateTime;
                }
            } else {
                console.log(`✓ Нашли свободное время: ${candidateTime}`);
                return candidateTime;
            }
        }
        
        // Обновляем последнее время окончания
        lastEndTime = Math.max(lastEndTime, blockEndMinutes + breakTime);
    }
    
    // Проверяем время после последнего блока
    if (lastEndTime + taskDurationMinutes <= workDayEnd) {
        const candidateTime = minutesToTime(lastEndTime);
        console.log(`✓ Время после последнего блока: ${candidateTime}`);
        return candidateTime;
    }
    
    // Если ничего не нашли
    console.log(`✗ Не нашли свободного времени на день ${day}`);
    return null;
}

// Вспомогательные функции для работы со временем
function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + (minutes || 0);
}

function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

function addTime(startTime, durationHours) {
    const [hours, minutes] = startTime.split(':').map(Number);
    const totalMinutes = hours * 60 + minutes + Math.ceil(durationHours * 60);
    return minutesToTime(totalMinutes);
}

function getPreferredTimeSlot(preferredTime) {
    const timeSlots = {
        morning: { start: '06:00', end: '12:00' },
        day: { start: '12:00', end: '18:00' },
        evening: { start: '18:00', end: '22:00' }
    };
    return timeSlots[preferredTime] || { start: '09:00', end: '22:00' };
}
    // Функция для расчета времени задачи с учетом фиксированного расписания
    function calculateTaskTime(day, durationHours, preferredTime = 'any') {
    return calculateTaskTimeWithSchedule(day, durationHours, preferredTime) || '10:00';
}
	
    
    async function clearSchedule() {
        if (!confirm('Вы уверены, что хотите очистить расписание на текущую неделю? Все распределенные задачи вернутся в список нераспределенных.')) {
            return;
        }
        
        try {
            const weekDates = getWeekDates(currentWeek);
            const weekStart = weekDates[0].toISOString().split('T')[0];
            
            const response = await fetch('clear_weekly_schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_id: window.userData.id,
                    week_start: weekStart 
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadTasksForWeek();
                renderWeek();
                renderPendingTasks();
                
                showNotification('Расписание очищено', 'warning');
            } else {
                showNotification('Ошибка при очистке расписания', 'error');
            }
        } catch (error) {
            console.error('Ошибка при очистке расписания:', error);
            showNotification('Ошибка при очистке расписания', 'error');
        }
    }
    
    async function redistributeTasksFromDay(day) {
        const dayTasks = tasks.filter(task => task.day === day);
        
        if (dayTasks.length === 0) {
            showNotification(`В ${getDayName(day)} нет задач для переноса`, 'info');
            return;
        }
        
        try {
            const requests = dayTasks.map(task => 
                fetch('unschedule_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: task.id })
                })
            );
            
            await Promise.all(requests);
            
            await loadTasksForWeek();
            renderWeek();
            renderPendingTasks();
            
            showNotification(formatTaskMessage(taskCount, 'перенесено', {from: dayName}), 'info');
        } catch (error) {
            console.error('Ошибка при переносе задач:', error);
            showNotification('Ошибка при переносе задач', 'error');
        }
    }
    
    function exportSchedule() {
        const weekDates = getWeekDates(currentWeek);
        const startDate = formatDate(weekDates[0]);
        const endDate = formatDate(weekDates[6]);
        
        let exportText = `РАСПИСАНИЕ НА НЕДЕЛЮ ${startDate} - ${endDate}\n`;
        exportText += '========================================\n\n';
        
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
        
        let totalWeight = 0;
        let totalTasks = 0;
        
        weekDays.forEach((day, index) => {
            const dayTasks = tasks.filter(task => task.day === day);
            const dayWeight = dayTasks.reduce((sum, task) => sum + task.weight, 0);
            totalWeight += dayWeight;
            totalTasks += dayTasks.length;
            
            exportText += `${dayNames[index]} (${dayWeight}/${dailyLimits[day]} баллов):\n`;
            
            if (dayTasks.length === 0) {
                exportText += '  Нет запланированных задач\n';
            } else {
                dayTasks.forEach(task => {
                    exportText += `  ${task.scheduled_time || '--:--'} - ${task.name} (${task.weight} баллов, ${getTypeName(task.type)})\n`;
                });
            }
            exportText += '\n';
        });
        
        exportText += `\nИтого: ${totalTasks} задач, ${totalWeight} баллов\n`;
        exportText += `Нераспределенные задачи: ${pendingTasks.length}`;
        
        const blob = new Blob([exportText], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `расписание_${formatDate(weekDates[0], 'YYYY-MM-DD')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showNotification('Расписание экспортировано!', 'success');
    }
    
    function adjustDailyLimits(mood) {
        const multipliers = {
            good: 1.0,
            tired: 0.8,
            bad: 0.6
        };
        
        const multiplier = multipliers[mood];
        
        Object.keys(dailyLimits).forEach(day => {
            dailyLimits[day] = Math.round(dailyLimits[day] * multiplier);
        });
        
        renderWeek();
        
        showNotification(`Лимиты скорректированы до ${multiplier * 100}%`, 'success');
    }
    
    // Функции перетаскивания
    function initDragAndDrop() {
        const draggables = document.querySelectorAll('.draggable');
        
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', () => {
                draggable.classList.add('dragging');
            });
            
            draggable.addEventListener('dragend', () => {
                draggable.classList.remove('dragging');
            });
        });
    }
    function initDragAndDrop() {
        const draggables = document.querySelectorAll('.draggable');
        
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', () => {
                draggable.classList.add('dragging');
            });
            
            draggable.addEventListener('dragend', () => {
                draggable.classList.remove('dragging');
            });
        });
    }
	function checkDailyLimit(day, taskWeight) {
        // Получаем текущую загрузку дня
        const dayTasks = tasks.filter(task => task.day === day);
        const currentWeight = dayTasks.reduce((sum, task) => sum + task.weight, 0);
        
        // Получаем лимит для этого дня
        const dayLimit = dailyLimits[day] || window.userData.dailyLimit;
        
        // Проверяем, не превысит ли новая задача лимит
        if (currentWeight + taskWeight > dayLimit) {
            const dayName = getDayName(day);
            return {
                allowed: false,
                current: currentWeight,
                limit: dayLimit,
                wouldBe: currentWeight + taskWeight,
                message: `День "${dayName}" перегружен! Текущая загрузка: ${currentWeight}/${dayLimit} баллов. Задача добавит ${taskWeight} баллов.`
            };
        }
        
        return {
            allowed: true,
            current: currentWeight,
            limit: dayLimit,
            wouldBe: currentWeight + taskWeight
        };
    }
    function setupDropZones() {
        const dropZones = document.querySelectorAll('.tasks-container, .pending-tasks');
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', e => {
                e.preventDefault();
                zone.classList.add('drag-over');
            });
            
            zone.addEventListener('dragleave', () => {
                zone.classList.remove('drag-over');
            });
            
            zone.addEventListener('drop', async (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                
                const draggable = document.querySelector('.dragging');
                if (!draggable) return;
                
                const taskId = parseInt(draggable.dataset.taskId);
                
                if (zone.classList.contains('pending-tasks')) {
                    await moveTaskToPending(taskId);
                } else {
                    const day = zone.id.replace('tasks-', '');
                    await moveTaskToDay(taskId, day);
                }
            });
        });
    }
    
    async function moveTaskToPending(taskId) {
        try {
            const response = await fetch('unschedule_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ task_id: taskId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadTasksForWeek();
                renderWeek();
                renderPendingTasks();
                
                showNotification('Задача перенесена в нераспределенные', 'success');
            } else {
                showNotification('Ошибка при перемещении задачи', 'error');
            }
        } catch (error) {
            console.error('Ошибка при перемещении задачи:', error);
            showNotification('Ошибка при перемещении задачи', 'error');
        }
    }
    
     async function moveTaskToDay(taskId, day) {
    try {
        const task = [...tasks, ...pendingTasks].find(t => t.id === taskId);
        if (!task) return;
        
        // ПРОВЕРКА ЛИМИТА
        const limitCheck = checkDailyLimit(day, task.weight);
        if (!limitCheck.allowed) {
            showNotification(limitCheck.message, 'error');
            return;
        }
        
        // Рассчитываем время С УЧЕТОМ РАСПИСАНИЯ
        const time = calculateTaskTimeWithSchedule(day, task.duration);
        if (!time) {
            showNotification(`На день "${getDayName(day)}" нет свободного времени для задачи`, 'error');
            return;
        }
        
        const weekDates = getWeekDates(currentWeek);
        const weekStart = weekDates[0].toISOString().split('T')[0];
        
        const response = await fetch('schedule_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                day: day,
                time: time,
                week_start: weekStart
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadTasksForWeek();
            renderWeek();
            renderPendingTasks();
            
            showNotification(`Задача запланирована на ${time}`, 'success');
        } else {
            showNotification('Ошибка при планировании задачи', 'error');
        }
    } catch (error) {
        console.error('Ошибка при планировании задачи:', error);
        showNotification('Ошибка при планировании задачи', 'error');
    }
}
    
    function logout() {
        fetch('logout.php')
            .then(response => response.json())
            .then(data => {
                localStorage.removeItem('user');
                window.location.href = 'index.php';
            })
            .catch(error => {
                console.error('Error:', error);
                localStorage.removeItem('user');
                window.location.href = 'index.php';
            });
    }
    
    // Вспомогательные функции
    function getWeekDates(weekOffset = 0) {
        const now = new Date();
        const currentDay = now.getDay();
        const diff = now.getDate() - currentDay + (currentDay === 0 ? -6 : 1);
        const monday = new Date(now.setDate(diff));
        
        monday.setDate(monday.getDate() + (weekOffset * 7));
        
        const weekDates = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(monday);
            date.setDate(monday.getDate() + i);
            weekDates.push(date);
        }
        
        return weekDates;
    }
    
    function formatDate(date, format = 'DD.MM') {
        if (!date) return '';
        
        const d = new Date(date);
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        
        if (format === 'YYYY-MM-DD') {
            return `${year}-${month}-${day}`;
        }
        
        return `${day}.${month}`;
    }
    
    function getTypeName(type) {
        const typeNames = {
            analytical: 'Аналитические',
            creative: 'Творческие',
            routine: 'Рутинные',
            social: 'Социальные',
            research: 'Исследовательские',
            physical: 'Физические',
            learning: 'Обучение',
            planning: 'Планирование'
        };
        
        return typeNames[type] || type;
    }
    
    function getDayName(dayKey) {
        const dayNames = {
            monday: 'понедельник',
            tuesday: 'вторник',
            wednesday: 'среда',
            thursday: 'четверг',
            friday: 'пятница',
            saturday: 'суббота',
            sunday: 'воскресенье'
        };
        
        return dayNames[dayKey] || dayKey;
    }
    function pluralize(number, one, few, many) {
    number = Math.abs(number);
    
    if (number % 10 === 1 && number % 100 !== 11) {
        return one; // 1, 21, 31, ... задача
    } else if (number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20)) {
        return few; // 2, 3, 4, 22, 23, 24 задачи
    } else {
        return many; // 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, ... задач
    }
}
function formatTaskMessage(count, action = 'распределено', options = {}) {
    const taskWord = pluralize(count, 'задача', 'задачи', 'задач');
    
    const actionMap = {
        'распределено': { 1: 'распределена', 2: 'распределены' },
        'перенесено': { 1: 'перенесена', 2: 'перенесены' },
        'удалено': { 1: 'удалена', 2: 'удалены' },
        'добавлено': { 1: 'добавлена', 2: 'добавлены' },
        'обновлено': { 1: 'обновлена', 2: 'обновлены' },
        'отмечено': { 1: 'отмечена', 2: 'отмечены' }
    };
    
    const actionForms = actionMap[action] || { 1: 'обработана', 2: 'обработаны' };
    const actionWord = count === 1 ? actionForms[1] : actionForms[2];
    
    let message = `${count} ${taskWord} ${actionWord}`;
    
    // Добавляем дополнительные параметры
    if (options.from) {
        message += ` из ${options.from}`;
    }
    if (options.to) {
        message += ` на ${options.to}`;
    }
    if (options.time) {
        message += ` в ${options.time}`;
    }
    
    return message;
}
    function showNotification(message, type = 'info', duration = 3000) {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                          type === 'error' ? 'exclamation-circle' : 
                          type === 'warning' ? 'exclamation-triangle' : 
                          'info-circle'}"></i>
        <span>${message}</span>
        <button class="close-notification">&times;</button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: ${80 + (document.querySelectorAll('.notification').length * 70)}px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : 
                     type === 'error' ? '#F44336' : 
                     type === 'warning' ? '#ff9800' : 
                     '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
        min-width: 300px;
    `;
    
    // Кнопка закрытия
    const closeBtn = notification.querySelector('.close-notification');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: auto;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    closeBtn.addEventListener('click', () => {
        removeNotification(notification);
    });
    
    document.body.appendChild(notification);
    
    // Автоматическое закрытие
    const timeout = setTimeout(() => {
        removeNotification(notification);
    }, duration);
    
    // Функция удаления уведомления
    function removeNotification(element) {
        element.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }
    
    // Обновляем стили для анимаций
    const style = document.createElement('style');
    style.id = 'notification-styles-' + Date.now();
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Очищаем старые стили
    setTimeout(() => {
        const oldStyles = document.querySelectorAll('[id^="notification-styles-"]');
        oldStyles.forEach((oldStyle, index) => {
            if (index < oldStyles.length - 3) { // Оставляем последние 3 стиля
                oldStyle.remove();
            }
        });
    }, 10000);
}
    
    // Экспортируем функции для отладки
    window.refreshTasks = async function() {
        await loadTasksForWeek();
        renderWeek();
        renderPendingTasks();
    };
    
    window.debugTasks = function() {
        console.log('Текущие задачи:', {
            tasks: tasks,
            pendingTasks: pendingTasks,
            currentWeek: currentWeek,
            dailyLimits: dailyLimits
        });
    };
	  // Экспортируем функции для отладки
    window.refreshTasks = async function() {
        await loadTasksForWeek();
        renderWeek();
        renderPendingTasks();
    };
    
    window.debugTasks = function() {
        console.log('Текущие задачи:', {
            tasks: tasks,
            pendingTasks: pendingTasks,
            currentWeek: currentWeek,
            dailyLimits: dailyLimits
        });
    };
    
    // Отладочная функция для проверки лимитов
    window.checkLimits = function(day) {
        if (day) {
            return checkDailyLimit(day, 0);
        }
        return dailyLimits;
    };
});