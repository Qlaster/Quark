<?php

try
{
    $channel = $_POST['channel'] ?? '';
    $name    = trim($_POST['name'] ?? '');
    if (!$channel) throw new Exception("Не указан канал");
    if (!$name)    throw new Exception("Поле 'name' обязательно");

    $data = [
        'title'    => $_POST['title']    ?: null,
        'author'   => $_POST['author']   ?: null,
        'status'   => $_POST['status']   ?: 'open',
        'tags'     => !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null,
        'meta'     => $_POST['meta']     ?: null,
        'archived' => isset($_POST['archived']) ? 1 : 0,
    ];

    $APP->talk->blog($channel)->post($name)->create($data);

    // Загрузка файлов
    $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/', '/');
    $uploadDir = "$folder/$channel/$name";
    $FILES = $APP->utils->files->uploadMove($uploadDir, false);

    $fileList = [];
    foreach ($FILES as $files) {
        foreach ((array)$files as $file) {
            if (!empty($file['new_name'])) $fileList[] = $file['new_name'];
        }
    }
    if ($fileList) {
        $APP->talk->blog($channel)->post($name)->update(['files' => $fileList]);
    }

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$name");
    exit;

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
