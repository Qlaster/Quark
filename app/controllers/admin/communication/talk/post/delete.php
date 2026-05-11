<?php

try
{
    $channel = $_GET['channel'] ?? '';
    $post    = $_GET['post']    ?? '';
    if (!$channel) throw new Exception("Не указан канал");
    if (!$post)    throw new Exception("Не указан пост");

    $APP->talk->blog($channel)->post($post)->delete();

    // Удаляем директорию с файлами поста
    $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/', '/');
    $uploadDir = "$folder/$channel/$post";
    if (is_dir($uploadDir)) $APP->utils->files->remove($uploadDir);

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
    exit;

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
