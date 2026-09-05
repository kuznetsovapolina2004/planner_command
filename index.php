<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="image/icon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Планировщик задач</h1>
            <h2>Вход в систему</h2>
            
            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            </div>
            
            <div id="message" class="message"></div>
        </div>
    </div>
    
    <script src="auth.js"></script>
</body>
</html>