<?php
	error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$content['title'] = $content['title']['head'];

	//Действие над службой: start|stop — после него редирект на чистую страницу
	if (($action = $_GET['action'] ?? null) and ($sname = $_GET['service'] ?? null))
	{
		try
		{
			if ($action === 'start')     $APP->service->start($sname);
			elseif ($action === 'stop')  $APP->service->stop($sname);

			//абсолютный путь — относительный Location дублирует сегменты URL
			header('Location: '.$APP->url->home().$APP->url->page());
			exit;
		}
		catch (\Exception $e)
		{
			$content['error']['message'] = $e->getMessage();
		}
	}

	//Вывод журнала службы по запросу ?output=<name>
	if ($oname = $_GET['output'] ?? null)
	{
		$data = $APP->service->output($oname, 200) ?: $content['output']['empty'];

		//AJAX-спойлер строки — отдаём сырой текст
		if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
			exit($data);

		$content['services']['output'] = [
			'name' => $oname,
			'data' => $data,
		];
	}

	//Список служб — фасад отдаёт name/file/size/mtime/running/pids
	foreach ($APP->service->list() as $key => $svc)
	{
		$content['services']['list'][$key]['name']    = $svc['name'];
		$content['services']['list'][$key]['file']    = str_replace('/', ' / ', $svc['file']);
		$content['services']['list'][$key]['size']    = round($svc['size']/1024, 2) . $content['suffix']['kb'];
		$content['services']['list'][$key]['updated'] = date('d.m.Y H:i:s', $svc['mtime']);
		$content['services']['list'][$key]['running'] = $svc['running'];
		$content['services']['list'][$key]['pids']    = implode(', ', $svc['pids']);

		$content['services']['list'][$key]['status']  = $svc['running'] ? $content['status']['running'] : $content['status']['stopped'];

		$content['services']['list'][$key]['link']['start']  = $APP->url->page()."?action=start&service=".rawurlencode($svc['name']);
		$content['services']['list'][$key]['link']['stop']   = $APP->url->page()."?action=stop&service=".rawurlencode($svc['name']);
		$content['services']['list'][$key]['link']['output'] = $APP->url->page()."?output=".rawurlencode($svc['name']);
		$content['services']['list'][$key]['link']['code']   = "admin/tools/codeeditor/?file=".$svc['file'];

		//кнопка «Конфигурация» — только если у службы есть компаньон .ini
		$ini = dirname($svc['file']).DIRECTORY_SEPARATOR.$svc['name'].'.ini';
		if (is_file($ini))
			$content['services']['list'][$key]['link']['config'] = "admin/tools/codeeditor/?config=".rawurlencode($ini);
	}

	$content['button']['refresh']['link'] = $APP->url->page();

	$APP->template->file('admin/components/services.html')->display($content);
