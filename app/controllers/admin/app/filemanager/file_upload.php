<?php                                                                                               

$content = $APP->controller->run('admin/autoinclude', $APP);

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

				if (is_file($uploaddir."/".$hash.".original")) unlink($uploaddir."/".$hash.".original");

				rename($uploaddir."/".$hash.".html5upload", $uploaddir."/".$hash.".original");

				$fw=fopen($uploaddir."/".$hash.".original_ready","wb");if ($fw) fclose($fw);
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
