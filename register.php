<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="image/icon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .error-field {
            border-color: #e74c3c !important;
        }
        
        .error-summary {
            background: #ffeaea;
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="error-summary" id="errorSummary"></div>
        
        <form class="registration-form" id="registrationForm">
            <div class="registration-header">
                <h1>Регистрация в системе планирования</h1>
                <p>Заполните форму для создания аккаунта</p>
            </div>

            <!-- Основная информация -->
            <div class="form-section">
                <h3>Основная информация</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="lastName">Фамилия</label>
                        <input type="text" id="lastName" name="lastName" required>
                        <div class="error-message" id="lastNameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="firstName">Имя</label>
                        <input type="text" id="firstName" name="firstName" required>
                        <div class="error-message" id="firstNameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="middleName">Отчество</label>
                        <input type="text" id="middleName" name="middleName">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                        <div class="error-message" id="emailError"></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <div class="error-message" id="passwordError"></div>
                    </div>
                </div>
            </div>

            <!-- Расписание постоянной занятости -->
            <div class="form-section" id="schedule-section">
                <div class="checkbox-group">
                    <input type="checkbox" id="combineWorkStudy" name="combineWorkStudy">
                    <label for="combineWorkStudy">Я совмещаю работу с учебой</label>
                </div>
                
                <h3>Расписание постоянной занятости</h3>
                
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
                        <!-- Строки будут сгенерированы JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Оценка энергозатратности типов задач -->
            <div class="form-section">
                <h3>Оценка энергозатратности типов задач</h3>
                
                <div class="task-energy-section">
                    <div class="energy-grid" id="energySliders">
                        <!-- Элементы будут сгенерированы JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Ежедневный лимит сложности -->
            <div class="form-section">
                <h3>Ежедневный лимит сложности</h3>
                
                <div class="difficulty-limit">
                    <div class="form-group">
                        <label for="daily_limit">Максимальное количество баллов сложности в день:</label>
                        <select id="daily_limit" name="daily_limit" class="form-control">
                            <option value="10">10 баллов (Минимальная нагрузка)</option>
                            <option value="15" selected>15 баллов (Легкая нагрузка)</option>
                            <option value="20">20 баллов (Средняя нагрузка)</option>
                            <option value="25">25 баллов (Высокая нагрузка)</option>
                            <option value="30">30 баллов (Максимальная нагрузка)</option>
                            <option value="custom">Задать свое значение</option>
                        </select>
                    </div>
                    
                    <div class="form-group custom-limit" style="display: none;">
                        <label for="custom_daily_limit">Свое значение (от 5 до 50):</label>
                        <input type="number" id="custom_daily_limit" name="custom_daily_limit" min="5" max="50" value="15">
                    </div>
                    
                    <div class="difficulty-explanation">
                        <p><strong>Как это работает:</strong></p>
                        <p>Система будет распределять задачи так, чтобы сумма их баллов сложности не превышала дневной лимит.</p>
                        <p><strong>Пример:</strong> Если лимит 20 баллов, а у вас есть задачи на 8, 7 и 6 баллов, система предложит только две из них.</p>
                    </div>
                </div>
            </div>

            <!-- Согласие и кнопка регистрации -->
            <div class="registration-footer">
                <div class="terms-agreement">
                    <div class="terms-checkbox">
                        <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                        <label for="agreeTerms">
                            Я согласен с <a href="#" onclick="return false;">условиями использования</a> и 
                            <a href="#" onclick="return false;">политикой конфиденциальности</a>
                        </label>
                        <div class="error-message" id="agreeTermsError"></div>
                    </div>
                </div>
                
                <div class="register-button-container">
                    <button type="submit" class="btn-register" id="submitBtn">
                        Зарегистрироваться
                    </button>
                </div>

                <div class="auth-links">
                    <p>Уже есть аккаунт? <a href="index.php">Войти</a></p>
                </div>
            </div>
        </form>
    </div>

<!-- Затемнение фона -->
<div id="successOverlay" class="success-overlay hidden"></div>

<!-- Сообщение об успешной регистрации -->
<div id="successMessage" class="success-message hidden">
    <div class="success-icon">✓</div>
    <h2>Регистрация успешно завершена!</h2>
    <p>Ваш аккаунт был успешно создан.</p>
    <p>Теперь вы можете войти в систему.</p>
    <p class="countdown-message">
        Через <span class="countdown-number" id="countdown">5</span> секунд вы будете перенаправлены...
    </p>
    <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
        <a href="index.php" class="dashboard-button" style="padding: 12px 25px;">Войти сейчас</a>
        <button id="stayOnPage" class="dashboard-button" style="padding: 12px 25px; background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);">Остаться</button>
    </div>
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Принудительно скрываем модальные окна при загрузке страницы
            const overlay = document.getElementById('successOverlay');
            const successMsg = document.getElementById('successMessage'); // Исправлено название переменной
            
            // Убираем класс hidden (если он есть) и принудительно скрываем
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.style.display = 'none';
            }
            if (successMsg) {
                successMsg.classList.add('hidden');
                successMsg.style.display = 'none';
            }
            
            // Инициализация таблицы расписания
            initializeScheduleTable();
            
            // Инициализация слайдеров энергии
            initializeEnergySliders();
            
            // Управление доступностью полей работы/учебы
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
            
            // Инициализация при загрузке
            updateScheduleAccess();
            
            // Обработка изменений чекбокса
            combineCheckbox.addEventListener('change', updateScheduleAccess);
            
            // Управление кастомным лимитом сложности
            const dailyLimitSelect = document.getElementById('daily_limit');
            const customLimitDiv = document.querySelector('.custom-limit');
            const customLimitInput = document.getElementById('custom_daily_limit');
            
            function updateCustomLimitVisibility() {
                if (dailyLimitSelect.value === 'custom') {
                    customLimitDiv.style.display = 'block';
                    customLimitInput.required = true;
                } else {
                    customLimitDiv.style.display = 'none';
                    customLimitInput.required = false;
                    customLimitInput.value = dailyLimitSelect.value;
                }
            }
            
            dailyLimitSelect.addEventListener('change', updateCustomLimitVisibility);
            updateCustomLimitVisibility();
            
            // Валидация кастомного лимита
            customLimitInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (value < 5) value = 5;
                if (value > 50) value = 50;
                this.value = value;
            });
            
            // Функция для инициализации таблицы расписания
            function initializeScheduleTable() {
                const days = [
                    { id: 'mon', name: 'Понедельник', fullName: 'monday' },
                    { id: 'tue', name: 'Вторник', fullName: 'tuesday' },
                    { id: 'wed', name: 'Среда', fullName: 'wednesday' },
                    { id: 'thu', name: 'Четверг', fullName: 'thursday' },
                    { id: 'fri', name: 'Пятница', fullName: 'friday' },
                    { id: 'sat', name: 'Суббота', fullName: 'saturday' },
                    { id: 'sun', name: 'Воскресенье', fullName: 'sunday' }
                ];
                
                const scheduleBody = document.getElementById('scheduleBody');
                if (!scheduleBody) return;
                
                scheduleBody.innerHTML = '';
                
                days.forEach(day => {
                    const row = document.createElement('tr');
                    
                    // Устанавливаем разные значения по умолчанию для рабочих и выходных дней
                    const workDefaultStart = (day.id === 'sat' || day.id === 'sun') ? '' : '09:00';
                    const workDefaultEnd = (day.id === 'sat' || day.id === 'sun') ? '' : '18:00';
                    
                    row.innerHTML = `
                        <td>${day.name}</td>
                        <td>
                            <div class="time-input-group work-time">
                                <input type="time" name="work_${day.id}_start" value="${workDefaultStart}" class="work-input">
                                <span>до</span>
                                <input type="time" name="work_${day.id}_end" value="${workDefaultEnd}" class="work-input">
                            </div>
                        </td>
                        <td>
                            <div class="time-input-group study-time">
                                <input type="time" name="study_${day.id}_start" value="" class="study-input">
                                <span>до</span>
                                <input type="time" name="study_${day.id}_end" value="" class="study-input">
                            </div>
                        </td>
                        <td>
                            <div class="time-input-group fixed-time">
                                <input type="time" name="fixed_${day.id}_start" value="" placeholder="Начало">
                                <span>до</span>
                                <input type="time" name="fixed_${day.id}_end" value="" placeholder="Конец">
                            </div>
                        </td>
                    `;
                    
                    scheduleBody.appendChild(row);
                });
            }
            
            // Функция для инициализации слайдеров энергии
            function initializeEnergySliders() {
                const taskTypes = [
                    { id: 'analytical', label: 'Аналитические задачи', defaultValue: 8, description: 'Решение сложных задач, анализ данных' },
                    { id: 'creative', label: 'Творческие задачи', defaultValue: 6, description: 'Креативная работа, мозговые штурмы' },
                    { id: 'routine', label: 'Рутинные задачи', defaultValue: 3, description: 'Повторяющиеся задачи, документация' },
                    { id: 'social', label: 'Социальные задачи', defaultValue: 5, description: 'Встречи, переговоры, общение' },
                    { id: 'research', label: 'Исследовательские задачи', defaultValue: 7, description: 'Изучение нового, исследования' },
                    { id: 'physical', label: 'Физические задачи', defaultValue: 4, description: 'Спорт, активная деятельность' },
                    { id: 'learning', label: 'Обучающие задачи', defaultValue: 5, description: 'Обучение, курсы, чтение' },
                    { id: 'planning', label: 'Планирование', defaultValue: 2, description: 'Составление планов, организация' }
                ];
                
                const slidersContainer = document.getElementById('energySliders');
                if (!slidersContainer) return;
                
                slidersContainer.innerHTML = '';
                
                taskTypes.forEach(type => {
                    const sliderDiv = document.createElement('div');
                    sliderDiv.className = 'energy-item';
                    
                    sliderDiv.innerHTML = `
                        <label for="energy_${type.id}">${type.label}:</label>
                        <div class="energy-controls">
                            <input type="range" id="energy_${type.id}" 
                                   name="energy_${type.id}" 
                                   min="1" max="10" value="${type.defaultValue}" 
                                   class="energy-slider">
                            <span class="slider-value" id="energy_${type.id}_value">${type.defaultValue}</span>
                        </div>
                        <div class="energy-description">${type.description}</div>
                    `;
                    
                    slidersContainer.appendChild(sliderDiv);
                    
                    // Добавляем обработчик изменения значения
                    const slider = sliderDiv.querySelector('.energy-slider');
                    const valueSpan = sliderDiv.querySelector('.slider-value');
                    
                    if (slider && valueSpan) {
                        slider.addEventListener('input', function() {
                            valueSpan.textContent = this.value;
                        });
                    }
                });
            }
            
            // Функция для отображения ошибок
            function showErrors(errors) {
                // Скрываем все ошибки
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                
                document.querySelectorAll('.error-field').forEach(el => {
                    el.classList.remove('error-field');
                });
                
                // Отображаем сводку ошибок
                const errorSummary = document.getElementById('errorSummary');
                if (errors.length > 0 && errorSummary) {
                    errorSummary.innerHTML = '<strong>Ошибки при заполнении формы:</strong><br>' + 
                        errors.map(error => `• ${error}`).join('<br>');
                    errorSummary.style.display = 'block';
                    
                    // Прокручиваем к ошибкам
                    errorSummary.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else if (errorSummary) {
                    errorSummary.style.display = 'none';
                }
                
                // Отображаем конкретные ошибки полей
                errors.forEach(error => {
                    if (error.includes('Фамилия')) {
                        showFieldError('lastName', error);
                    } else if (error.includes('Имя')) {
                        showFieldError('firstName', error);
                    } else if (error.includes('Email')) {
                        showFieldError('email', error);
                    } else if (error.includes('Пароль')) {
                        showFieldError('password', error);
                    } else if (error.includes('согласиться')) {
                        showFieldError('agreeTerms', error);
                    }
                });
            }
            
            function showFieldError(fieldId, message) {
                const errorElement = document.getElementById(fieldId + 'Error');
                const inputElement = document.getElementById(fieldId);
                
                if (errorElement && inputElement) {
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                    inputElement.classList.add('error-field');
                    
                    // Прокручиваем к полю с ошибкой
                    inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            
            // AJAX отправка формы
            document.getElementById('registrationForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Валидация на клиенте
                const errors = [];
                const formData = new FormData(this);
                
                // Проверка обязательных полей
                if (!formData.get('lastName').trim()) errors.push('Фамилия обязательна для заполнения');
                if (!formData.get('firstName').trim()) errors.push('Имя обязательно для заполнения');
                if (!formData.get('email').trim()) errors.push('Email обязателен для заполнения');
                if (!formData.get('password').trim()) errors.push('Пароль обязателен для заполнения');
                
                // Проверка email
                const email = formData.get('email').trim();
                if (email && !validateEmail(email)) {
                    errors.push('Некорректный email адрес');
                }
                
                // Проверка пароля
                const password = formData.get('password');
                if (password && password.length < 6) {
                    errors.push('Пароль должен содержать минимум 6 символов');
                }
                
                // Проверка согласия с условиями
                if (!formData.get('agreeTerms')) {
                    errors.push('Необходимо согласиться с условиями использования');
                }
                
                // Если есть ошибки на клиенте, показываем их
                if (errors.length > 0) {
                    showErrors(errors);
                    return;
                }
                
                // Показываем индикатор загрузки
                const submitBtn = document.getElementById('submitBtn');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Регистрация...';
                submitBtn.disabled = true;
                document.getElementById('registrationForm').classList.add('loading');
                
                try {
                    // Отправляем AJAX запрос
                    const response = await fetch('register_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Успешная регистрация - скрываем форму
                        document.getElementById('registrationForm').style.display = 'none';
                        
                        // Показываем оверлей и сообщение
                        const overlay = document.getElementById('successOverlay');
                        const successMsg = document.getElementById('successMessage');
                        
                        if (overlay) {
                            overlay.classList.remove('hidden');
                            overlay.style.display = 'block';
                        }
                        if (successMsg) {
                            successMsg.classList.remove('hidden');
                            successMsg.style.display = 'block';
                        }
                        
                        // Таймер обратного отсчета
                        let countdown = 5;
                        let countdownInterval;
                        const countdownElement = document.getElementById('countdown');
                        
                        // Функция для перенаправления
                        function redirectToLogin() {
                            window.location.href = 'index.php';
                        }
                        
                        // Запускаем таймер
                        if (countdownElement) {
                            countdownInterval = setInterval(() => {
                                countdown--;
                                countdownElement.textContent = countdown;
                                
                                if (countdown <= 0) {
                                    clearInterval(countdownInterval);
                                    redirectToLogin();
                                }
                            }, 1000);
                        }
                        
                        // Обработчик для кнопки "Остаться"
                        const stayButton = document.getElementById('stayOnPage');
                        if (stayButton) {
                            // Удаляем старый обработчик, если он был
                            const newStayButton = stayButton.cloneNode(true);
                            stayButton.parentNode.replaceChild(newStayButton, stayButton);
                            
                            newStayButton.addEventListener('click', function() {
                                if (countdownInterval) clearInterval(countdownInterval);
                                if (overlay) {
                                    overlay.classList.add('hidden');
                                    overlay.style.display = 'none';
                                }
                                if (successMsg) {
                                    successMsg.classList.add('hidden');
                                    successMsg.style.display = 'none';
                                }
                            });
                        }
                        
                        // Закрытие по клику на оверлей
                        if (overlay) {
                            overlay.addEventListener('click', function() {
                                if (countdownInterval) clearInterval(countdownInterval);
                                this.classList.add('hidden');
                                this.style.display = 'none';
                                if (successMsg) {
                                    successMsg.classList.add('hidden');
                                    successMsg.style.display = 'none';
                                }
                            });
                        }
                        
                    } else {
                        // Ошибка регистрации
                        showErrors(data.errors || [data.message]);
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    showErrors(['Произошла ошибка при отправке формы: ' + error.message]);
                } finally {
                    // Восстанавливаем кнопку
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    document.getElementById('registrationForm').classList.remove('loading');
                }
            });
            
            // Функция валидации email
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Реальная валидация полей при изменении
            document.querySelectorAll('input, select').forEach(element => {
                element.addEventListener('blur', function() {
                    validateField(this);
                });
            });
            
            function validateField(field) {
                const errorElement = document.getElementById(field.id + 'Error');
                if (!errorElement) return;
                
                let isValid = true;
                let message = '';
                
                switch (field.id) {
                    case 'lastName':
                    case 'firstName':
                        if (!field.value.trim()) {
                            isValid = false;
                            message = 'Это поле обязательно для заполнения';
                        }
                        break;
                    case 'email':
                        if (!field.value.trim()) {
                            isValid = false;
                            message = 'Email обязателен для заполнения';
                        } else if (!validateEmail(field.value)) {
                            isValid = false;
                            message = 'Некорректный email адрес';
                        }
                        break;
                    case 'password':
                        if (!field.value) {
                            isValid = false;
                            message = 'Пароль обязателен для заполнения';
                        } else if (field.value.length < 6) {
                            isValid = false;
                            message = 'Пароль должен содержать минимум 6 символов';
                        }
                        break;
                }
                
                if (!isValid) {
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                    field.classList.add('error-field');
                } else {
                    errorElement.style.display = 'none';
                    field.classList.remove('error-field');
                }
            }
        });
</script>
</body>
</html>