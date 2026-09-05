<?php
header('Content-Type: application/json');
session_start();

// Уничтожаем сессию
$_SESSION = array();

// Удаляем сессионную cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header('Location: index.php');
exit;
// echo json_encode(['success' => true, 'message' => 'Выход выполнен успешно']);

?>