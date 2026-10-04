<?php

    header('Content-Type: application/json');

    //Статика сообщений — компаньон-ini [view] + перевод
    $cfg = $APP->config->get();
    $M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

    try
    {
        $db = trim($_GET['db']);
        if (!$db) throw new Exception($M['noconnect']['text']);

        $orm = $APP->db->connect($db);
        if (!$orm) throw new Exception(sprintf($M['nodb']['text'], $db));

        $tables = $orm->tables();
        echo json_encode(array_values((array) $tables));
    }
    catch (Exception $e)
    {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
