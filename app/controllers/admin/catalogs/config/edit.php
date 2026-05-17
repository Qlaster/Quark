<?php

    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    $name = $_GET['name'];

    if ($name)
    {
        $content['title']   = 'Редактирование каталога: ' . $name;
        $rawList = $APP->catalog->listing();
        $content['catalog'] = $rawList[$name];
        $content['catalog']['name'] = $name;

        if ($content['catalog']['db'] && $content['catalog']['table'])
            $content['db_columns'] = array_keys(
                (array) $APP->db->connect($content['catalog']['db'])
                                ->table($content['catalog']['table'])
                                ->columns()
            );
    }
    else
    {
        $content['title']      = 'Новый каталог';
        $content['catalog']    = [];
        $content['db_columns'] = [];
    }

    $content['db_list']  = $APP->db->listing();
    $content['patterns'] = $APP->catalog->patterns();
    $content['is_new']   = !$name;

    $APP->template->file('admin/catalogs/config.html')->display($content);
