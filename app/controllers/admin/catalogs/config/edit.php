<?php

    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    //Подгружаем конфигурацию
    $cfg = $APP->config->get();

    //Статическая GUI-структура — компаньон-ini, секция [view]
    $content = array_replace_recursive($content, (array) $cfg['view']);
    $content = $APP->l10n->translate($content);

    $name = $_GET['name'];

    if ($name)
    {
        $content['title']   = sprintf($content['title']['edit']['head'], $name);
        $rawList = $APP->catalog->listing();
        $content['catalog'] = $rawList[$name];
        $content['catalog']['name'] = $name;

        if ($content['catalog']['db'] && $content['catalog']['table'])
        {

			if ($APP->db->connect($content['catalog']['db']))
			{
				$content['db_columns'] = array_keys(
					(array) $APP->db->connect($content['catalog']['db'])
									->table($content['catalog']['table'])
									->columns()
				);
			}
        }
    }
    else
    {
        $content['title']      = $content['title']['new']['head'];
        $content['catalog']    = [];
        $content['db_columns'] = [];
    }

    $content['db_list']  = $APP->db->listing();
    $content['patterns'] = $APP->catalog->patterns();
    $content['is_new']   = !$name;

    $APP->template->file('admin/catalogs/config.html')->display($content);
