<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	file_force_download($_GET['path']);


	/*
		Если Вам потребовалось отдавать файлы не напрямую веб сервером, а с помощью PHP (например для сбора статистики скачиваний), прошу под кат.
		1. Используем readfile()
		Метод хорош тем, что работает с коробки. Надо только написать свою функцию отправки файла (немного измененный пример из официальной документации):

		Таким способом можно отправлять даже большие файлы, так как PHP будет читать файл и сразу отдавать его пользователю по частям. В документации четко сказано, что readfile() не должен создавать проблемы с памятью.

		Особенности:
		Скрипт ждет пока весь файл будет прочитан и отдан пользователю.
		Файл читается в внутренний буфер функции readfile(), размер которого составляет 8кБ (спасибо 2fast4rabbit)
	*/

	function file_force_download($file)
	{
		if (file_exists($file))
		{
			// сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
			// если этого не сделать файл будет читаться в память полностью!
			if (ob_get_level()) {
			ob_end_clean();
			}
			// заставляем браузер показать окно сохранения файла
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($file));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			// читаем файл и отправляем его пользователю
			readfile($file);
			exit;
		}
	}


	exit;
