<?php

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
                $sqlType = _fieldTypeToSQL($fieldConfig['type']);
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
        echo '<div style="padding:20px;color:red">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }


    function _fieldTypeToSQL($type)
    {
        $map = [
            'text'      => 'TEXT',
            'int'       => 'INTEGER',
            'integer'   => 'INTEGER',
            'real'      => 'REAL',
            'html'      => 'TEXT',
            'link'      => 'TEXT',
            'image'     => 'TEXT',
            'video'     => 'TEXT',
            'audio'     => 'TEXT',
            'blob'      => 'BLOB',
            'check'     => 'INTEGER DEFAULT 0',
            'date'      => 'TEXT',
            'datetime'  => 'TEXT',
            'checkdate' => 'TEXT',
            'input'     => 'TEXT',
            'relation'  => 'INTEGER',
            'files'     => 'TEXT',
            'select'    => 'TEXT',
            'textarea'  => 'TEXT',
            'id'        => 'INTEGER',
        ];
        return $map[$type] ?? 'TEXT';
    }
