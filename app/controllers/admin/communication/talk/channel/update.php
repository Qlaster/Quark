<?php

//Статика сообщений — компаньон-ini [view] + перевод
$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

try {
    $channel = $_REQUEST['channel'] ?? '';
    if (!$channel) throw new Exception($M['nochannel']['text']);

    $data = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Форма редактирования — обновляем все поля
        $data['title']    = $_POST['title']    ?: null;
        $data['tags']     = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : null;
        $data['meta']     = $_POST['meta']     ?: null;
        $data['archived'] = isset($_POST['archived']) ? 1 : 0; // checkbox
    } else {
        // GET — обновляем только явно переданные поля (напр. archived)
        if (array_key_exists('archived', $_GET))
            $data['archived'] = (int)$_GET['archived'];
    }

    if (empty($data)) throw new Exception($M['nodata']['text']);

    $APP->talk->blog($channel)->update($data);

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
    exit;

} catch (Exception $e) {
    echo $M['error']['text'] . $e->getMessage();
}
