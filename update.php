#!/usr/bin/php
<?php

	const DistributionZIP = "https://codeload.github.com/Qlaster/Quark/zip/refs/heads/main";
	const TempAlias       = "quark";


	//Получаем временную директорию
	$workDir  = sys_get_temp_dir().DIRECTORY_SEPARATOR.TempAlias.DIRECTORY_SEPARATOR;
	$distFile = "$workDir/quark.zip";
	if (!file_exists($workDir))	mkdir($workDir);

	file_put_contents($distFile, file_get_contents(DistributionZIP));

	system("unzip $distFile -d $workDir");

	//~ $zipArchive = new ZipArchive();
	 //~ $zip = new \ZipArchive;
	//Загрузим дистрибутив для обновления
	//~ copy(DistributionZIP, "$workDir/quark.zip");

	# 2-ой способ (скачивание картинки)
	//~ $file = file_get_contents(DistributionZIP);
	//~ file_put_contents("$workDir/quark.zip", file_get_contents(DistributionZIP));

	if (function_exists('curl_init'))
	{


		//~ $url = DistributionZIP; // откуда скачиваем файл
		//~ $path = "$workDir/quark.zip";  // куда сохраняем файл

		//~ $fp = fopen($path, 'w');
		//~ $ch = curl_init($url);
		//~ curl_setopt($ch, CURLOPT_FILE, $fp);
		//~ curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//~ curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		//~ curl_exec($ch);
		//~ curl_close($ch);
		//~ fclose($fp);









	}
	else
	{
		exit('Библиотека cURL не доступна в этой системе');
	}
	//~ curl_setopt($ch, CURLOPT_HEADER, 0);
	//~ curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

	//~ curl_exec($ch);



	echo round(memory_get_peak_usage()/1024, 0)." kb \r\n";
	echo round(memory_get_usage()/1024, 0)."kb \r\n";


	exit();
	$fp = fopen("$workDir/quark.zip", 'w'); // создание файла
		$ch = curl_init(DistributionZIP);


		curl_setopt($ch, CURLOPT_FILE, $fp); // запись в файл
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_STDERR, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);








	print_r($argv);

	echo "input#";
	while ($c = fread(STDIN, 1))
	{
		if (ord($c) == 10)
		{
			echo "Enter";
			break;
		}

		echo $c;
		//~ echo "Read from STDIN: " . $c . "\ninput# ";
	}

	//~ system("stty -icanon");
	//~ echo "input# ";
	//~ while ($c = fread(STDIN, 1))
	//~ {

		//~ echo "Read from STDIN: " . $c . "\ninput# ";
	//~ }

	echo $c;












	function readchar($prompt)
	{
		readline_callback_handler_install($prompt, function() {});
		$char = stream_get_contents(STDIN, 1);
		readline_callback_handler_remove();
		return $char;
	}

	// example:
	if (!in_array(
		readchar('Continue? [Y/n] '), ["\n", 'y', 'Y']
		// enter/return key ("\n") for default 'Y'
	)) die("Good Bye\n");
	$name = readline("Name: ");
	echo "Hello {$name}.\n";
