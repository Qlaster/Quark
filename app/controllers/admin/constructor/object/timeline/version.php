<?php

    error_reporting(E_ALL & ~E_NOTICE);

    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    //Статическая GUI-структура — компаньон-ini [view] + перевод
    $cfg = $APP->config->get();
    $content = array_replace_recursive($content, (array) $cfg['view']);
    $content = $APP->l10n->translate($content);

    // Получаем снимок на указанную дату
    $records = $APP->objects->timeline->select([
        'collection' => $_GET['collection'],
        'name'       => $_GET['name'],
        'date'       => $_GET['date']
    ]);

    if (!$records)
        exit( $content['messages']['notfound']['text'] );

    $record = $records[0];
    $value  = unserialize($record['value']);

    $content['object'] = [
        'collection'   => $record['collection'],
        'name'         => $record['name'],
        'action'       => $record['action'],
        'date'         => $record['date'],
        'value_ini'    => $APP->config->toString($value), //метод позволяет привести массив к стандарту ini
        'value_raw'    => print_r($value, true),
        'value_json'   => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ];

    $content['title'] = sprintf($content['title']['head'], $record['collection'], $record['name'], $record['date']);

    $APP->template->file('admin/constructor/object/object.version.html')->display($content);
