<?php

//Статика сообщений — компаньон-ini [view] + перевод
$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

try
{
    $channel = $_REQUEST['channel'] ?? '';
    if (!$channel) throw new Exception($M['nochannel']['text']);

    // Bulk-режим (POST posts[]) или одиночный (GET post=)
    if (!empty($_POST['posts'])) {
        $posts = array_filter(array_map('trim', (array)$_POST['posts']));
    } elseif (!empty($_GET['post'])) {
        $posts = [$_GET['post']];
    } else {
        throw new Exception($M['nopost']['text']);
    }

    $folder = rtrim($APP->talk->config['upload']['folder'] ?? 'public/talk/');

    foreach ($posts as $post)
    {
        $APP->talk->blog($channel)->post($post)->delete();
        $uploadDir = "$folder/$channel/$post";
        if (is_dir($uploadDir)) $APP->files->remove($uploadDir);
    }

    header('Location: ' . $APP->url->home() . "admin/communication/talk/?channel=$channel");
    exit;

} catch (Exception $e) {
    echo $M['error']['text'] . $e->getMessage();
}
