<?php

    header('Content-Type: application/json');

    try
    {
        $db = trim($_GET['db']);
        if (!$db) throw new Exception('Не указано подключение');

        $orm = $APP->db->connect($db);
        if (!$orm) throw new Exception('Подключение не найдено: ' . $db);

        $tables = $orm->tables();
        echo json_encode(array_values((array) $tables));
    }
    catch (Exception $e)
    {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
