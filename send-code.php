<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Функция для записи в файл с обработкой ошибок
function saveCodeToFile($email, $code) {
    $filename = 'codes.json';
    $data = [
        'email' => $email,
        'code' => $code,
        'timestamp' => time()
    ];
    
    // Проверяем, можно ли записать в файл
    if (file_put_contents($filename, json_encode($data)) === false) {
        // Если не получается, пробуем создать файл
        if (!file_exists($filename)) {
            $initialData = json_encode([]);
            file_put_contents($filename, $initialData);
        }
        // Пробуем еще раз
        if (file_put_contents($filename, json_encode($data)) === false) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    
    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Неверный формат email']);
        exit;
    }
    
    // Генерация 6-значного кода
    $code = sprintf('%06d', mt_rand(1, 999999));
    
    // Сохранение кода
    if (!saveCodeToFile($email, $code)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения кода. Проверьте права доступа.']);
        exit;
    }
    
    // Настройки email
    $to = $email;
    $subject = 'Код подтверждения для bsk';
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(255, 20, 147, 0.3); max-width: 500px; margin: 0 auto; }
            .code { font-size: 2rem; font-weight: bold; color: #ff1493; text-align: center; margin: 20px 0; padding: 15px; background: #fff5f7; border-radius: 10px; }
            .footer { color: #666; font-size: 0.9rem; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2 style='color: #ff1493; text-align: center;'>Код подтверждения</h2>
            <p>Здравствуйте!</p>
            <p>Для восстановления пароля введите следующий код:</p>
            <div class='code'>$code</div>
            <p><strong>Этот код действителен в течение 10 минут.</strong></p>
            <p>Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.</p>
            <div class='footer'>
                С уважением,<br>
                Команда bsk
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: bsk <noreply@bsk.ru>\r\n";
    
    // Отправка email
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Код отправлен на вашу почту', 'code' => $code]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при отправке email']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}
?>