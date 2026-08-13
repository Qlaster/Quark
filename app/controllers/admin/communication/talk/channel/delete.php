<?php

try {
    $channel = $_GET['channel'] ?? '';
    if (!$channel) throw new Exception("Не указан канал");

    $APP->talk->blog($channel)->delete();

    header('Location: ' . $APP->url->home() . 'admin/communication/talk/');
    exit;

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
