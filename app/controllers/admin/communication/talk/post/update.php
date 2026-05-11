<?php

try
{
    $channel = $_REQUEST['channel'] ?? '';
    $post    = $_REQUEST['post']    ?? '';
    if (!$channel) throw new Exception("Не указан канал");
    if (!$post)    throw new Exception("Не указан пост");

    $data = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $data['title']    = $_POST['title']    ?: null;
        $data['author']   = $_POST['author']   ?: null;
        $data['status']   = $_POST['status']   ?: 'open';
        $data['tags']     = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null;
        $data['meta']     = $_POST['meta']     ?: null;
        $data['archived'] = isset($_POST['archived']) ? 1 : 0;

        // Загрузка новых файлов (добавляем к существующим)
        $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/', '/');
        $uploadDir = "$folder/$channel/$post";
        $FILES = $APP->utils->files->uploadMove($uploadDir, false);

        $newFiles = [];
        foreach ($FILES as $files) {
            foreach ((array)$files as $file) {
                if (!empty($file['new_name'])) $newFiles[] = $file['new_name'];
            }
        }
        if ($newFiles) {
            $current = $APP->talk->blog($channel)->post($post)->select();
            $existing = (array)($current[0]['files'] ?? []);
            $data['files'] = array_merge($existing, $newFiles);
        }

    } else {
        // GET — только явно переданные поля (напр. archived)
        if (array_key_exists('archived', $_GET))
            $data['archived'] = (int)$_GET['archived'];
    }

    if (empty($data)) throw new Exception("Нет данных для обновления");

    $APP->talk->blog($channel)->post($post)->update($data);

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$post");
    exit;

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
