<?php

    //Статика сообщений — компаньон-ini [view] + перевод
    $cfg = $APP->config->get();
    $M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

    try
    {
        $name = trim($_GET['name']);
        if (!$name) throw new Exception($M['nocatalog']['text']);

        $APP->catalog->delete($name);
        //~ header('Location: admin/catalogs/config/');
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit;
    }
    catch (Exception $e)
    {
        echo $M['error']['text'].' ' . htmlspecialchars($e->getMessage());
    }
