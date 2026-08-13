<?php

    error_reporting(E_ALL & ~E_NOTICE);

    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    // Получаем историю изменений объекта (DESC по дате)
    $records = $APP->objects->timeline->select([
        'collection' => $_GET['collection'],
        'name'       => $_GET['name']
    ]);

    // Текущее состояние объекта из основной таблицы
    $currentValue      = $APP->objects->collection($_GET['collection'])->get($_GET['name']);
    $currentSerialized = serialize($currentValue);

    // Обрабатываем в хронологическом порядке (ASC) для сравнения
    $records = array_reverse($records);

    $history = [];

    foreach ($records as $record)
    {
        $timestamp = $record['date'];
        $value     = unserialize($record['value']);

        // Представление значения для вывода
        $displayValue = is_scalar($value)
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            //~ : print_r($value, true);

        // Заголовок записи
        $history[$timestamp]['head'] = is_string($value) ? mb_substr($value, 0, 80) : $record['name'];


        // Тип изменения: action хранит что произошло ПОСЛЕ этого снимка
        if ($record['action'] === 'delete')
        {
            $history[$timestamp]['delete']['value'] = [
                'type' => gettype($value),
                'name' => $record['name'],
                'data' => $displayValue
            ];
        }
        else // action = 'update'
        {
            $history[$timestamp]['update']['value'] = [
                'type' => gettype($value),
                'name' => $record['name'],
                'data' => $displayValue,
                'link' => 'admin/constructor/object/timeline/version?collection='.urlencode($_GET['collection'])
                        .'&name='.urlencode($_GET['name'])
                        .'&date='.urlencode($timestamp)
            ];
        }

        // Кнопка просмотра
        $history[$timestamp]['button']['view'] = [
            'head'   => 'Посмотреть эту версию',
            'style'  => 'default',
            'target' => '_blank',
            'link'   => 'admin/constructor/object/timeline/version?collection='.urlencode($_GET['collection'])
                        .'&name='.urlencode($_GET['name'])
                        .'&date='.urlencode($timestamp)
        ];

        // Кнопка восстановления
        $history[$timestamp]['button']['rollback'] = [
            'head'   => 'Восстановить эту версию',
            'style'  => 'primary',
            'target' => '',
            'link'   => 'admin/constructor/object/timeline/rollback?collection='.urlencode($_GET['collection'])
                        .'&name='.urlencode($_GET['name'])
                        .'&date='.urlencode($timestamp)
        ];

        // Если это текущее значение — помечаем активным и убираем кнопку rollback
        if ($record['value'] === $currentSerialized)
        {
            $history[$timestamp]['active'] = true;
            unset($history[$timestamp]['button']['rollback']);
        }
    }

    $content['history']['list'] = array_reverse($history);
    $content['title'] = 'История объекта: '.$_GET['collection'].'/'.$_GET['name'];

    $APP->template->file('admin/constructor/object/object.timeline.html')->display($content);
