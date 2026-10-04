<?php

//Статика сообщений — компаньон-ini [view] + перевод
$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

try
{
    $channel = $_REQUEST['channel'] ?? '';
    if (!$channel) throw new Exception($M['nochannel']['text']);

    $data = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        // Bulk-режим: POST с posts[] — применяем только archived из GET
        if (!empty($_POST['posts'])) {
            $posts = array_filter(array_map('trim', (array)$_POST['posts']));
            if (!$posts) throw new Exception($M['noposts']['text']);
            if (!array_key_exists('archived', $_GET)) throw new Exception($M['nodata']['text']);

            $data['archived'] = (int)$_GET['archived'];
            foreach ($posts as $post) {
                $APP->talk->blog($channel)->post($post)->update($data);
            }

            header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
            exit;
        }

        // Одиночный режим: полное редактирование через форму
        $post = $_REQUEST['post'] ?? '';
        if (!$post) throw new Exception($M['nopost']['text']);

        // Валидация slug'ов до любых файловых операций — фасад бросит на невалидных именах
        $postCtx = $APP->talk->blog($channel)->post($post);

        $data['title']    = $_POST['title']    ?: null;
        $data['author']   = $_POST['author']   ?: null;
        $data['status']   = $_POST['status']   ?: null;  // null = очистить статус
        $data['tags']     = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null;
        $data['meta']     = $_POST['meta']     ?: null;
        $data['archived'] = isset($_POST['archived']) ? 1 : 0;

        $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/', '/');
        $FILES  = $APP->files->uploadMove("$folder/$channel/$post", false);

        $newFiles = [];
        foreach ($FILES as $files) {
            foreach ((array)$files as $file) {
                if (!empty($file['new_name'])) $newFiles[] = $file['new_name'];
            }
        }
        if ($newFiles) {
            $current  = $postCtx->select();
            $existing = (array)($current[0]['files'] ?? []);
            $data['files'] = array_merge($existing, $newFiles);
        }

        $postCtx->update($data);

        header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$post");
        exit;

    } else {
        // GET — одиночный, только явно переданные поля
        $post = $_GET['post'] ?? '';
        if (!$post) throw new Exception($M['nopost']['text']);

        if (array_key_exists('archived', $_GET))
            $data['archived'] = (int)$_GET['archived'];

        if (empty($data)) throw new Exception($M['nodata']['text']);

        $APP->talk->blog($channel)->post($post)->update($data);

        header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel&post=$post");
        exit;
    }

} catch (Exception $e) {
    echo $M['error']['text'] . $e->getMessage();
}
