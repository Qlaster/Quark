<?php

try
{
    $channel = $_REQUEST['channel'] ?? '';
    if (!$channel) throw new Exception("Не указан канал");

    // Bulk-режим (POST posts[]) или одиночный (GET post=)
    if (!empty($_POST['posts'])) {
        $posts = array_filter(array_map('trim', (array)$_POST['posts']));
    } elseif (!empty($_GET['post'])) {
        $posts = [$_GET['post']];
    } else {
        throw new Exception("Не указан пост");
    }

    $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/');

    foreach ($posts as $post)
    {
        $APP->talk->blog($channel)->post($post)->delete();
        $uploadDir = "$folder/$channel/$post";
        if (is_dir($uploadDir)) $APP->utils->files->remove($uploadDir);
    }

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
    exit;

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
