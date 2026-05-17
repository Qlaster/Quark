<?php

    header('Content-Type: application/json');

    try
    {
        $db    = trim($_GET['db']);
        $table = trim($_GET['table']);

        if (!$db || !$table) throw new Exception('Не указаны параметры');

        $orm = $APP->db->connect($db);
        if (!$orm) throw new Exception('Подключение не найдено: ' . $db);

        $columns = (array) $orm->table($table)->columns();
        echo json_encode(array_keys($columns));
    }
    catch (Exception $e)
    {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
