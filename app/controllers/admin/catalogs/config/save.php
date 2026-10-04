<?php

    //Статика сообщений — компаньон-ini [view] + перевод
    $cfg = $APP->config->get();
    $M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

    try
    {
        $isNew = ($_POST['_action'] == 'create');

        $catalog = [
            'name'    => trim($_POST['name']),
            'db'      => trim($_POST['db']),
            'table'   => trim($_POST['table']),
            'icon'    => trim($_POST['icon']),
            'head'    => trim($_POST['head'])?:trim($_POST['name']),
            'info'    => trim($_POST['info']),
            'html'    => $_POST['html'],
            'help'    => trim($_POST['help']),
            'refresh' => (int) $_POST['refresh'] ?: null,
            'events'  => [
                'view' => [
                    'column'  => trim($_POST['events_view_column']),
                    'groupby' => trim($_POST['events_view_groupby']),
                    'orderby' => trim($_POST['events_view_orderby']),
                    'where'   => trim($_POST['events_view_where']),
                ]
            ],
            'field' => [],
        ];

        $orm = $APP->db->connect($catalog['db']);
        $existingColumns = array_keys((array) $orm->table($catalog['table'])->columns());
        $patterns        = $APP->catalog->patterns();

        foreach ((array) $_POST['field'] as $fieldName => $fieldConfig)
        {
            $fieldName = trim($fieldName);
            if (!$fieldName) continue;

            $fieldEntry = [];
            if ($fieldConfig['alias'])  $fieldEntry['alias']  = trim($fieldConfig['alias']);
            if ($fieldConfig['type'])   $fieldEntry['type']   = trim($fieldConfig['type']);
            if ($fieldConfig['source']) $fieldEntry['source'] = trim($fieldConfig['source']);
            if ($fieldConfig['input'])  $fieldEntry['input']  = trim($fieldConfig['input']);
            if ($fieldConfig['ignore']) $fieldEntry['ignore'] = 1;

            $catalog['field'][$fieldName] = $fieldEntry;

            // Новое поле — добавляем колонку в таблицу БД
            if (!in_array($fieldName, $existingColumns))
            {
                $sqlType = _fieldTypeToSQL($fieldConfig['type'], $patterns);
                $orm->SQL("ALTER TABLE \"{$catalog['table']}\" ADD COLUMN \"{$fieldName}\" {$sqlType}");
            }
        }

        if ($isNew)
            $APP->catalog->create($catalog);
        else
            $APP->catalog->update($catalog);

        //~ header('Location: admin/catalogs/');
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit;
    }
    catch (Exception $e)
    {
        http_response_code(500);
        echo '<div style="padding:20px;color:red">'.$M['error']['text'].' ' . htmlspecialchars($e->getMessage()) . '</div>';
    }


    function _fieldTypeToSQL($type, $patterns)
    {
        //Тип колонки объявляет сам паттерн ([patterns] <type>.sql).
        //Типы вне паттернов (легаси, из UI не выбрать) — жёсткий маппинг
        $legacy = ['id'=>'INTEGER', 'integer'=>'INTEGER', 'select'=>'TEXT', 'textarea'=>'TEXT'];
        return $patterns[$type]['sql'] ?? $legacy[$type] ?? 'TEXT';
    }
