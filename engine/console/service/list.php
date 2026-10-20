<?php

	/*
	 * Список служб и их состояние.
	 *
	 * 	./console service list
	 *
	 * ⚪ запущена, ⚫ остановлена.
	 */

	foreach ($APP->service->list() as $svc)
	{
		$mark  = $svc['running'] ? '▶️ ' : '🔴'; //⚪ ⬜
		$state = $svc['running'] ? ' (running: '.implode(',', $svc['pids']).')' : '';
		echo " $mark ⣏ {$svc['name']} ⣹\t→ {$svc['file']} (".date('Y-m-d H:i:s', $svc['mtime']).")$state\n";
	}
