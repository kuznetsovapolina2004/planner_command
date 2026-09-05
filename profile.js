// profile.js
let originalData = {};
let currentEditSection = null;
let editedData = {};

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем авторизацию
    checkAuth();
    
    // Загружаем данные профиля
    loadProfileData();
    
    // Инициализируем перевод дней недели
    initDayTranslations();
});

// Перевод дней недели
const dayTranslations = {
    'monday': 'Понедельник',
    'tuesday': 'Вторник',
    'wednesday': 'Среда',
    'thursday': 'Четверг',
    'friday': 'Пятница',
    'saturday': 'Суббота',
    'sunday': 'Воскресенье'
};

// Перевод типов задач
const taskTypeTranslations = {
    'analytical': 'Аналитические',
    'creative': 'Творческие',
    'routine': 'Рутинные',
    'social': 'Социальные',
    'research': 'Исследовательские',
    'physical': 'Физические',
    'learning': 'Обучение',
    'planning': 'Планирование'
};

function initDayTranslations() {
    // Инициализация уже сделана в объектах выше
}

function loadProfileData() {
    fetch('php/profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                originalData = data.user;
                displayProfileData(data.user);
            } else {
                showMessage('Ошибка загрузки данных профиля', 'error');
                setTimeout(() => window.location.href = 'index.html', 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Ошибка соединения с сервером', 'error');
        });
}

function displayProfileData(user) {
    // Обновляем аватар
    const avatar = document.getElementById('userAvatar');
    if (user.first_name && user.last_name) {
        const initials = user.first_name[0] + user.last_name[0];
        avatar.textContent = initials.toUpperCase();
    }
    
    // Обновляем основную информацию
    document.getElementById('userName').textContent = `${user.first_name} ${user.last_name}`;
    document.getElementById('userEmail').textContent = user.email;
    
    // Основная информация
    document.getElementById('lastNameValue').textContent = user.last_name || 'Не указано';
    document.getElementById('firstNameValue').textContent = user.first_name || 'Не указано';
    document.getElementById('middleNameValue').textContent = user.middle_name || 'Не указано';
    document.getElementById('emailValue').textContent = user.email || 'Не указано';
    document.getElementById('combineWorkStudyValue').textContent = user.combine_work_study ? 'Да' : 'Нет';
    
    // Настройки планирования
    document.getElementById('dailyLimitValue').textContent = user.daily_limit || '15';
    document.getElementById('customDailyLimitValue').textContent = user.custom_daily_limit || 'Не установлен';
    
    // Расписание работы
    displaySchedule('workScheduleBody', user.work_schedule);
    
    // Расписание учебы
    displaySchedule('studyScheduleBody', user.study_schedule);
    
    // Энергозатратность задач
    displayTaskEnergy(user.task_energy);
    
    // Фиксированные задачи
    displayFixedTasks(user.fixed_tasks);
}

function displaySchedule(tableBodyId, schedule) {
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = '';
    
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    days.forEach(day => {
        const dayData = schedule && schedule[day];
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${dayTranslations[day]}</td>
            <td>
                <span class="info-value">${dayData ? dayData.start_time || '—' : '—'}</span>
                <input type="time" class="edit-input schedule-time" data-day="${day}" data-type="start" 
                       value="${dayData ? dayData.start_time || '' : ''}">
            </td>
            <td>
                <span class="info-value">${dayData ? dayData.end_time || '—' : '—'}</span>
                <input type="time" class="edit-input schedule-time" data-day="${day}" data-type="end" 
                       value="${dayData ? dayData.end_time || '' : ''}">
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function displayTaskEnergy(taskEnergy) {
    const container = document.getElementById('taskEnergyContent');
    container.innerHTML = '';
    
    const taskTypes = [
        'analytical',
        'creative',
        'routine',
        'social',
        'research',
        'physical',
        'learning',
        'planning'
    ];
    
    taskTypes.forEach(type => {
        const energyLevel = taskEnergy && taskEnergy[type] ? taskEnergy[type] : 5;
        
        const sliderContainer = document.createElement('div');
        sliderContainer.className = 'energy-slider-container';
        
        sliderContainer.innerHTML = `
            <div class="task-type-label">
                <span>${taskTypeTranslations[type]}:</span>
                <span class="info-value">${energyLevel}/10</span>
            </div>
            <input type="range" class="edit-input energy-slider" min="1" max="10" value="${energyLevel}" 
                   data-type="${type}" style="display: none;">
            <span class="energy-value" style="display: none;">${energyLevel}</span>
        `;
        
        container.appendChild(sliderContainer);
    });
}

function displayFixedTasks(fixedTasks) {
    const container = document.getElementById('fixedTasksList');
    container.innerHTML = '';
    
    if (!fixedTasks || fixedTasks.length === 0) {
        container.innerHTML = '<p style="color: #7f8c8d; text-align: center;">Нет фиксированных задач</p>';
        return;
    }
    
    fixedTasks.forEach((task, index) => {
        const taskElement = document.createElement('div');
        taskElement.className = 'fixed-task-item';
        taskElement.dataset.index = index;
        
        taskElement.innerHTML = `
            <div class="task-item-header">
                <span class="task-day">${dayTranslations[task.day_of_week]}</span>
                <span class="task-time">${task.start_time} - ${task.end_time}</span>
                <button class="remove-task-btn" onclick="removeFixedTask(${index})" style="display: none;">×</button>
            </div>
            <div class="task-description">${task.description}</div>
            <div class="edit-task-fields" style="display: none;">
                <input type="text" class="edit-input task-description-input" value="${task.description}" 
                       placeholder="Описание задачи">
                <select class="edit-input task-day-input">
                    ${Object.entries(dayTranslations).map(([value, label]) => 
                        `<option value="${value}" ${task.day_of_week === value ? 'selected' : ''}>${label}</option>`
                    ).join('')}
                </select>
                <input type="time" class="edit-input task-start-input" value="${task.start_time}">
                <input type="time" class="edit-input task-end-input" value="${task.end_time}">
            </div>
        `;
        
        container.appendChild(taskElement);
    });
}

function toggleEdit(sectionId) {
    const section = document.getElementById(sectionId);
    const isCurrentlyEditing = section.classList.contains('edit-mode');
    
    // Если уже редактируем другую секцию, отменяем редактирование
    if (currentEditSection && currentEditSection !== sectionId) {
        document.getElementById(currentEditSection).classList.remove('edit-mode');
    }
    
    if (isCurrentlyEditing) {
        // Выходим из режима редактирования
        section.classList.remove('edit-mode');
        currentEditSection = null;
        hideSaveCancelButtons();
    } else {
        // Входим в режим редактирования
        section.classList.add('edit-mode');
        currentEditSection = sectionId;
        showSaveCancelButtons();
        
        // Сохраняем оригинальные значения для возможной отмены
        if (sectionId === 'fixedTasksSection') {
            editedData.fixed_tasks = JSON.parse(JSON.stringify(originalData.fixed_tasks || []));
        }
    }
}

function showSaveCancelButtons() {
    document.getElementById('saveCancelButtons').style.display = 'flex';
}

function hideSaveCancelButtons() {
    document.getElementById('saveCancelButtons').style.display = 'none';
}

function saveChanges() {
    // Собираем все измененные данные
    const updatedData = {};
    
    // Основная информация
    if (currentEditSection === 'basicInfoSection') {
        updatedData.last_name = document.getElementById('lastNameInput').value;
        updatedData.first_name = document.getElementById('firstNameInput').value;
        updatedData.middle_name = document.getElementById('middleNameInput').value;
        updatedData.email = document.getElementById('emailInput').value;
        updatedData.combine_work_study = document.getElementById('combineWorkStudyInput').checked;
    }
    
    // Настройки планирования
    if (currentEditSection === 'planningSettingsSection') {
        updatedData.daily_limit = parseInt(document.getElementById('dailyLimitInput').value);
        updatedData.custom_daily_limit = parseInt(document.getElementById('customDailyLimitInput').value) || null;
    }
    
    // Расписание работы
    if (currentEditSection === 'workScheduleSection') {
        updatedData.work_schedule = collectScheduleData('workScheduleBody');
    }
    
    // Расписание учебы
    if (currentEditSection === 'studyScheduleSection') {
        updatedData.study_schedule = collectScheduleData('studyScheduleBody');
    }
    
    // Энергозатратность задач
    if (currentEditSection === 'taskEnergySection') {
        updatedData.task_energy = collectTaskEnergyData();
    }
    
    // Фиксированные задачи
    if (currentEditSection === 'fixedTasksSection') {
        updatedData.fixed_tasks = collectFixedTasksData();
    }
    
    // Отправляем данные на сервер
    fetch('php/update_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updatedData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Данные успешно обновлены', 'success');
            
            // Обновляем оригинальные данные
            Object.assign(originalData, updatedData);
            
            // Выходим из режима редактирования
            document.getElementById(currentEditSection).classList.remove('edit-mode');
            currentEditSection = null;
            hideSaveCancelButtons();
            
            // Обновляем отображение
            displayProfileData(originalData);
            
        } else {
            showMessage(data.message || 'Ошибка обновления данных', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Ошибка соединения с сервером', 'error');
    });
}

function collectScheduleData(tableBodyId) {
    const schedule = {};
    const timeInputs = document.querySelectorAll(`#${tableBodyId} .schedule-time`);
    
    timeInputs.forEach(input => {
        const day = input.dataset.day;
        const type = input.dataset.type;
        
        if (!schedule[day]) {
            schedule[day] = {};
        }
        
        if (input.value) {
            schedule[day][type === 'start' ? 'start_time' : 'end_time'] = input.value;
        }
    });
    
    return schedule;
}

function collectTaskEnergyData() {
    const taskEnergy = {};
    const sliders = document.querySelectorAll('.energy-slider');
    
    sliders.forEach(slider => {
        const type = slider.dataset.type;
        taskEnergy[type] = parseInt(slider.value);
    });
    
    return taskEnergy;
}

function collectFixedTasksData() {
    const fixedTasks = [];
    const taskElements = document.querySelectorAll('.fixed-task-item');
    
    taskElements.forEach(element => {
        const index = element.dataset.index;
        const descriptionInput = element.querySelector('.task-description-input');
        const dayInput = element.querySelector('.task-day-input');
        const startInput = element.querySelector('.task-start-input');
        const endInput = element.querySelector('.task-end-input');
        
        if (descriptionInput && dayInput && startInput && endInput) {
            fixedTasks.push({
                description: descriptionInput.value,
                day_of_week: dayInput.value,
                start_time: startInput.value,
                end_time: endInput.value
            });
        }
    });
    
    return fixedTasks;
}

function cancelEdit() {
    if (currentEditSection) {
        document.getElementById(currentEditSection).classList.remove('edit-mode');
        currentEditSection = null;
        hideSaveCancelButtons();
        
        // Восстанавливаем оригинальные данные в отображении
        displayProfileData(originalData);
    }
}

function addFixedTask() {
    const container = document.getElementById('fixedTasksList');
    
    // Создаем новую задачу
    const newTask = {
        description: 'Новая задача',
        day_of_week: 'monday',
        start_time: '09:00',
        end_time: '10:00'
    };
    
    // Добавляем в массив
    if (!originalData.fixed_tasks) {
        originalData.fixed_tasks = [];
    }
    originalData.fixed_tasks.push(newTask);
    
    // Обновляем отображение
    displayFixedTasks(originalData.fixed_tasks);
}

function removeFixedTask(index) {
    if (originalData.fixed_tasks && originalData.fixed_tasks[index]) {
        originalData.fixed_tasks.splice(index, 1);
        displayFixedTasks(originalData.fixed_tasks);
    }
}

function logout() {
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Очищаем localStorage
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                
                // Перенаправляем на страницу входа
                window.location.href = 'index.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Ошибка при выходе', 'error');
        });
}

function showMessage(text, type = 'info') {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}

// Экспортируем функции для использования в других файлах
window.logout = logout;