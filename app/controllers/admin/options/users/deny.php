<?php

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = $APP->l10n->translate(array_replace_recursive((array) $content, (array) $cfg['view']));

	$content['title'] = $content['title']['head'];
	$content['head']  = $content['error']['head'];
	$content['text']  = $content['error']['text'];

	$APP->template->file('admin/error.html')->display($content);

	
