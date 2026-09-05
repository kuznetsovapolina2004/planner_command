// Общие функции авторизации

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли форма входа
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Проверяем, есть ли форма регистрации
    const regForm = document.getElementById('registrationForm');
    if (regForm) {
        regForm.addEventListener('submit', handleRegistration);
    }
    
    // Проверяем авторизацию на защищенных страницах
    if (window.location.pathname.includes('account.php') || 
        window.location.pathname.includes('planner.php')) {
        checkAuth();
    }
});

function handleLogin(e) {
    e.preventDefault();
    console.log('Login form submitted');
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // Валидация
    if (!email || !password) {
        showMessage('Пожалуйста, заполните все поля', 'error');
        return;
    }
    
    // Показываем индикатор загрузки
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Вход...';
    submitBtn.disabled = true;
    
    // Отправка запроса на сервер
    fetch('login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showMessage('Вход выполнен успешно! Перенаправление...', 'success');
            
            // Сохраняем данные пользователя в localStorage
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Перенаправляем на планировщик через 1 секунду
            setTimeout(() => {
                window.location.href = 'planner.php';
            }, 1000);
        } else {
            showMessage(data.message || 'Ошибка авторизации', 'error');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Ошибка соединения с сервером', 'error');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function handleRegistration(e) {
    e.preventDefault();
    
    // Собираем данные из формы
    const formData = {
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        dailyLimit: document.getElementById('dailyLimit').value,
        peakStart: document.getElementById('peakStart').value,
        peakEnd: document.getElementById('peakEnd').value,
        schedule: getScheduleData(),
        energyRatings: getEnergyRatings()
    };
    
    // Валидация
    if (!formData.firstName || !formData.lastName || !formData.email || !formData.password) {
        showMessage('Пожалуйста, заполните все обязательные поля', 'error');
        return;
    }
    
    // Отправка запроса на сервер
    fetch('register_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Регистрация успешна! Перенаправление...', 'success');
            
            // Сохраняем данные пользователя в localStorage
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Перенаправляем на планировщик через 2 секунды
            setTimeout(() => {
                window.location.href = 'planner.php';
            }, 2000);
        } else {
            showMessage(data.message || 'Ошибка регистрации', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Ошибка соединения с сервером', 'error');
    });
}

function getScheduleData() {
    const schedule = {};
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    days.forEach(day => {
        schedule[day] = {
            work: document.getElementById(`${day}-work`).value,
            study: document.getElementById(`${day}-study`).value,
            sport: document.getElementById(`${day}-sport`).value,
            other: document.getElementById(`${day}-other`).value
        };
    });
    
    return schedule;
}

function getEnergyRatings() {
    const ratings = {};
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
        ratings[type] = document.getElementById(`energy-${type}`).value;
    });
    
    return ratings;
}

function checkAuth() {
    // Для простоты проверяем только localStorage
    const user = localStorage.getItem('user');
    
    if (!user) {
        // Если нет пользователя в localStorage и мы не на странице входа/регистрации
        if (!window.location.pathname.includes('index.php') && 
            !window.location.pathname.includes('register.php')) {
            window.location.href = 'index.php';
        }
    } else {
        // Обновляем информацию о пользователе на странице
        try {
            const userData = JSON.parse(user);
            
            // Обновляем имя в планировщике
            if (document.getElementById('user-name')) {
                document.getElementById('user-name').textContent = 
                    `${userData.first_name} ${userData.last_name}`;
            }
            
            // Обновляем имя в профиле
            if (document.getElementById('userName')) {
                document.getElementById('userName').textContent = 
                    `${userData.first_name} ${userData.last_name}`;
            }
            
        } catch (error) {
            console.error('Error parsing user data:', error);
        }
    }
}

function logout() {
    // Отправляем запрос на выход
    fetch('logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Очищаем localStorage
                localStorage.removeItem('user');
                
                // Перенаправляем на страницу входа
                window.location.href = 'index.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // В любом случае очищаем и перенаправляем
            localStorage.removeItem('user');
            window.location.href = 'index.php';
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
    } else {
        // Если нет элемента для сообщений, показываем alert
        alert(`${type.toUpperCase()}: ${text}`);
    }
}

// Экспортируем функции для использования в других файлах
window.logout = logout;
window.checkAuth = checkAuth;
window.showMessage = showMessage;