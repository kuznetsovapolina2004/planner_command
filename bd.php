<?php
// bd.php
$bd = mysqli_connect("MySQL-8.0", "root", "", "daily-planner");

// Проверка подключения
if (!$bd) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($bd, "utf8");

// Можно также добавить обработку ошибок
function db_query($sql, $params = []) {
    global $bd;
    
    $stmt = mysqli_prepare($bd, $sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // предполагаем все строки
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}
?>