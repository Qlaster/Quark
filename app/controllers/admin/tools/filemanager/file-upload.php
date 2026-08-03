<?php

$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

$config = $APP->config->get();

$uploaddir = "./uploaddir";
$uploaddir = $config["path"]["uploaddir"];

//Создаем директорию
mkdir($uploaddir, 0777, true);

$hash=$_SERVER["HTTP_UPLOAD_ID"];

openlog("html5upload.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

if (preg_match("/^[0123456789abcdef]{32}$/i",$hash))
{

	if ($_SERVER["REQUEST_METHOD"]=="GET")
	{
		if ($_GET["action"]=="abort")
			{
				if (is_file($uploaddir."/".$hash.".html5upload")) unlink($uploaddir."/".$hash.".html5upload");
				print "ok abort";
				return;
			}

		if ($_GET["action"]=="done")
			{
				syslog(LOG_INFO, "Finished for hash ".$hash);

				//Целевая директория и имя — только внутри разрешённых директорий
				$targetDir = $APP->files->jailPath($_GET['path'] ?? '');
				$filename  = basename((string) ($_GET['name'] ?? ''));
				$src       = $uploaddir."/".$hash.".html5upload";

				if (!$targetDir or in_array($filename, ['','.','..']))
				{
					header("HTTP/1.0 500 Internal Server Error");
					print "Wrong upload target.";
					return;
				}
				if (!is_file($src))
				{
					header("HTTP/1.0 500 Internal Server Error");
					print "Uploaded content not found.";
					return;
				}
				//Контроль целостности — размер собранного файла
				if (isset($_GET['size']) and filesize($src) != (int) $_GET['size'])
				{
					header("HTTP/1.0 500 Internal Server Error");
					print "Uploaded size mismatch.";
					return;
				}

				$dest = $targetDir.DIRECTORY_SEPARATOR.$filename;
				if (filter_var($_GET['uniq'], FILTER_VALIDATE_BOOLEAN))
					$dest = $targetDir.DIRECTORY_SEPARATOR.uniqid().'_'.$filename;

				rename($src, $dest);
			}
	}
	elseif ($_SERVER["REQUEST_METHOD"]=="POST")
	{

		syslog(LOG_INFO, "Uploading chunk. Hash ".$hash." (".intval($_SERVER["HTTP_PORTION_FROM"])."-".intval($_SERVER["HTTP_PORTION_FROM"]+$_SERVER["HTTP_PORTION_SIZE"]).", size: ".intval($_SERVER["HTTP_PORTION_SIZE"]).")");

		$filename=$uploaddir."/".$hash.".html5upload";

		if (intval($_SERVER["HTTP_PORTION_FROM"])==0)
			$fout=fopen($filename,"wb");
		else
			$fout=fopen($filename,"ab");

		if (!$fout)
		{
			syslog(LOG_INFO, "Can't open file for writing: ".$filename);
			header("HTTP/1.0 500 Internal Server Error");
			print "Can't open file for writing.";
			return;
		}

		$fin = fopen("php://input", "rb");
		if ($fin)
		{
			while (!feof($fin))
			{
				$data=fread($fin, 1024*1024);
				fwrite($fout,$data);
			}
			fclose($fin);
		}

		fclose($fout);
	}

	header("HTTP/1.0 200 OK");
	print "ok\n";
}
else
{
	syslog(LOG_INFO, "Uploading chunk. Wrong hash ".$hash);
	header("HTTP/1.0 500 Internal Server Error");
	print "Wrong session hash.";
}

closelog();

return true;
