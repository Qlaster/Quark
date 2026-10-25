<?php

	/*
	 * Заглушка обслуживания — dependency-free:
	 * инклюдится из index.php ДО ядра, без $APP и шаблонизатора.
	 * Путь к этому файлу — maintenance.controller в .env.
	 */

	$eta = (int) ($_ENV['maintenance']['eta'] ?? 120);

	http_response_code(503);
	header('Retry-After: '.$eta);
	header('Refresh: '.$eta);
?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="refresh" content="<?=$eta?>">
	<title>Технические работы</title>
	<style>
		body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f3f3f4; color: #333; }
		.card { text-align: center; }
		h1 { font-weight: normal; }
	</style>
</head>
<body>
	<div class="card">
		<h1>Идёт обновление</h1>
		<p>Сайт откроется автоматически примерно через <?=ceil($eta/60)?> мин.</p>
	</div>
</body>
</html>
