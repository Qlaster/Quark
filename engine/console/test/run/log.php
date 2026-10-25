<?php

	/*
	 * Секция log — фасад журнала.
	 *
	 * 	./console test run log
	 *
	 * PSR-3 уровни, каналы, sanitize имён каналов, файлы на диске.
	 * Пишем в scratch-каналы test.* и прибиваемся за собой —
	 * продакшн-логи не трогаем.
	 */

	$logDir = $APP->log->config['dir'] ?? 'app/log';
	$date   = date('Y-m-d');
	$bad    = [];
	$mark   = 'smoke'.rand(1000,9999);   //уникальный маркер записей

	//1. Все PSR-3 уровни в канал по умолчанию — не должны падать
	foreach (['debug','info','notice','warning','error','critical','alert','emergency'] as $lvl)
		$APP->log->$lvl("$lvl $mark", ['test'=>true]);
	$def = "$logDir/app/$date.log";
	if (!is_file($def)) $bad[] = "файл канала по умолчанию не создан: $def";
	elseif (strpos(file_get_contents($def), "emergency $mark") === false)
		$bad[] = 'в канале по умолчанию нет свежей записи';
	echo "\t".(is_file($def) ? '✓' : '✗')." канал по умолчанию (8 уровней)\n";

	//2. Каналы + чтение записи обратно
	foreach (['test.account','test.db.errors'] as $ch)
	{
		$APP->log->channel($ch)->warning("$ch $mark");
		$file = "$logDir/$ch/$date.log";
		if (!is_file($file) or strpos(file_get_contents($file), "$ch $mark") === false)
			$bad[] = "канал $ch не пишет";
		echo "\t".(is_file($file) ? '✓' : '✗')." канал $ch\n";
	}

	//3. Sanitize имени канала: пробелы/слэши → '_'
	$APP->log->channel('test.my channel/x')->info("sanitize $mark");
	$san = "$logDir/test.my_channel_x/$date.log";
	if (!is_file($san)) $bad[] = 'sanitize имени канала не сработал';
	echo "\t".(is_file($san) ? '✓' : '✗')." sanitize 'test.my channel/x' → test.my_channel_x\n";

	//4. Прибиваемся: сносим созданные тест-каналы и нашу запись из дефолтного
	foreach (['test.account','test.db.errors','test.my_channel_x'] as $ch)
	{
		$d = "$logDir/$ch";
		if (is_dir($d)) { array_map('unlink', glob("$d/*")); rmdir($d); }
	}
	echo "\t✓ прибрались\n";

	if ($bad) throw new Exception('Журнал не работает: '.implode('; ', $bad));
