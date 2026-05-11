<?php

try
{
    $channel = $_REQUEST['channel'] ?? '';
    if (!$channel) throw new Exception("Не указан канал");

    $data = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        // Bulk-режим: POST с posts[] — применяем только archived из GET
        if (!empty($_POST['posts'])) {
            $posts = array_filter(array_map('trim', (array)$_POST['posts']));
            if (!$posts) throw new Exception("Не выбраны посты");
            if (!array_key_exists('archived', $_GET)) throw new Exception("Нет данных для обновления");

            $data['archived'] = (int)$_GET['archived'];
            foreach ($posts as $post) {
                $APP->talk->blog($channel)->post($post)->update($data);
            }

            header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
            exit;
        }

        // Одиночный режим: полное редактирование через форму
        $post = $_REQUEST['post'] ?? '';
        if (!$post) throw new Exception("Не указан пост");

        $data['title']    = $_POST['title']    ?: null;
        $data['author']   = $_POST['author']   ?: null;
        $data['status']   = $_POST['status']   ?: null;  // null = очистить статус
        $data['tags']     = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null;
        $data['meta']     = $_POST['meta']     ?: null;
        $data['archived'] = isset($_POST['archived']) ? 1 : 0;

        $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/', '/');
        $FILES  = $APP->utils->files->uploadMove("$folder/$channel/$post", false);

        $newFiles = [];
        foreach ($FILES as $files) {
            foreach ((array)$files as $file) {
                if (!empty($file['new_name'])) $newFiles[] = $file['new_name'];
            }
        }
        if ($newFiles) {
            $current  = $APP->talk->blog($channel)->post($post)->select();
            $existing = (array)($current[0]['files'] ?? []);
            $data['files'] = array_merge($existing, $newFiles);
        }

        $APP->talk->blog($channel)->post($post)->update($data);

        header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$post");
        exit;

    } else {
        // GET — одиночный, только явно переданные поля
        $post = $_GET['post'] ?? '';
        if (!$post) throw new Exception("Не указан пост");

        if (array_key_exists('archived', $_GET))
            $data['archived'] = (int)$_GET['archived'];

        if (empty($data)) throw new Exception("Нет данных для обновления");

        $APP->talk->blog($channel)->post($post)->update($data);

        header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$post");
        exit;
    }

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
