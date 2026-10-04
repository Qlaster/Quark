<?php

//Статика сообщений — компаньон-ini [view] + перевод
$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

try {
    $channel = $_GET['channel'] ?? '';
    if (!$channel) throw new Exception($M['nochannel']['text']);

    $APP->talk->blog($channel)->delete();

    header('Location: ' . $APP->url->home() . 'admin/communication/talk/');
    exit;

} catch (Exception $e) {
    echo $M['error']['text'] . $e->getMessage();
}
