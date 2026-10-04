<?php

//Статика сообщений — компаньон-ini [view] + перевод
$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

try {
    $originalName = trim($_POST['original_name'] ?? '');
    $name         = trim($_POST['name'] ?? '');

    if (!$name && !$originalName) throw new Exception($M['noname']['text']);

    $data = [
        'title'    => $_POST['title']    ?: null,
        'tags'     => !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null,
        'meta'     => $_POST['meta']     ?: null,
        'archived' => isset($_POST['archived']) ? 1 : 0,
    ];

    if ($originalName) {
        // Обновление: name readonly в форме, редиректим на оригинальное имя
        $APP->talk->blog($originalName)->update($data);
        $redirect = $APP->url->home() . "admin/communication/talk/?channel=$originalName";
    } else {
        // Создание нового канала
        $APP->talk->blog($name)->create($data);
        $redirect = $APP->url->home() . "admin/communication/talk/?channel=$name";
    }

    header("Location: $redirect");
    exit;

} catch (Exception $e) {
    echo $M['error']['text'] . $e->getMessage();
}

