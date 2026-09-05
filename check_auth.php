<?php
header('Content-Type: application/json');
session_start();
require_once 'bd.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user_id = $_SESSION['user_id'];
    
    // Получаем данные пользователя
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($bd, $sql);
    
    if ($user = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'last_name' => $user['last_name'],
                'first_name' => $user['first_name'],
                'email' => $user['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
}
?>