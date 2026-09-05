// account.js - JavaScript для личного кабинета

document.addEventListener('DOMContentLoaded', function() {
    // Обновление значений слайдеров
    document.querySelectorAll('.energy-slider').forEach(slider => {
        slider.addEventListener('input', function() {
            const valueElement = this.closest('.energy-item').querySelector('.slider-value');
            if (valueElement) {
                valueElement.textContent = this.value;
            }
        });
        
        // Инициализация значений
        const valueElement = slider.closest('.energy-item').querySelector('.slider-value');
        if (valueElement) {
            valueElement.textContent = slider.value;
        }
    });
    
    // Управление кастомным лимитом
    const dailyLimitSelect = document.getElementById('daily_limit');
    const customLimitDiv = document.querySelector('.custom-limit');
    
    if (dailyLimitSelect && customLimitDiv) {
        function updateCustomLimitVisibility() {
            customLimitDiv.style.display = dailyLimitSelect.value === 'custom' ? 'block' : 'none';
        }
        
        dailyLimitSelect.addEventListener('change', updateCustomLimitVisibility);
        updateCustomLimitVisibility();
    }
    
    // Валидация формы смены пароля
    const passwordForm = document.querySelector('#password-tab form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showError('Новые пароли не совпадают');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                showError('Новый пароль должен содержать минимум 6 символов');
                return false;
            }
            
            return true;
        });
    }
    
    // Восстановление активной вкладки
    const lastTab = localStorage.getItem('lastTab') || 'overview';
    const tabBtn = document.querySelector(`.tab-btn[data-tab="${lastTab}"]`);
    if (tabBtn) {
        tabBtn.click();
    }
});

function showError(message) {
    alert('Ошибка: ' + message);
}

// Экспорт данных
window.exportData = function() {
    const userData = {
        profile: {
            firstName: document.getElementById('firstName')?.value || '',
            lastName: document.getElementById('lastName')?.value || '',
            email: document.getElementById('email')?.value || ''
        },
        settings: {
            dailyLimit: document.getElementById('daily_limit')?.value || '15',
            energyLevels: {}
        },
        exportDate: new Date().toISOString()
    };
    
    // Собираем данные энергозатратности
    document.querySelectorAll('.energy-slider').forEach(slider => {
        const key = slider.id.replace('energy_', '');
        userData.settings.energyLevels[key] = slider.value;
    });
    
    // Создаем и скачиваем файл
    const dataStr = JSON.stringify(userData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    
    const downloadUrl = URL.createObjectURL(dataBlob);
    const downloadLink = document.createElement('a');
    downloadLink.href = downloadUrl;
    downloadLink.download = `user_data_${new Date().toISOString().slice(0,10)}.json`;
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(downloadUrl);
    
    alert('Данные успешно экспортированы!');
};