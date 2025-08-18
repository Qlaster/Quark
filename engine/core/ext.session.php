<?php
/*
 * ext.session.php
 *
 * Copyright 2025 vladimir <vladimir@MacBookAir>
 *
 * Суть этого расширения ядра - убрать блокировку сессий,
 * которая длится на всем протяжении исполнения скрипта, мешая параллельным
 * потокам исполнятся и работать с файлом сессий
 *
 *
 */


class QSessionHandlerUnlock implements SessionHandlerInterface
{
	private $savePath;

	#[\ReturnTypeWillChange]
	public function open($savePath, $sessionName)
	{
		$this->savePath = $savePath;
		if (!is_dir($this->savePath))
			mkdir($this->savePath, 0777);

		return true;
	}

	#[\ReturnTypeWillChange]
	public function close()
	{
		return true;
	}

	#[\ReturnTypeWillChange]
	public function read($id)
	{
		return (string) @file_get_contents("$this->savePath/sess_$id");
	}

	#[\ReturnTypeWillChange]
	public function write($id, $data)
	{
		return file_put_contents("$this->savePath/sess_$id", $data, LOCK_EX) === false ? false : true;
	}

	#[\ReturnTypeWillChange]
	public function destroy($id)
	{
		$file = "$this->savePath/sess_$id";
		if (file_exists($file))
			unlink($file);

		return true;
	}


	#[\ReturnTypeWillChange]
	public function gc($maxlifetime)
	{
		foreach (glob("$this->savePath/sess_*") as $file)
			if (filemtime($file) + $maxlifetime < time() && file_exists($file))
				unlink($file);

		return true;
	}
}

session_set_save_handler(new QSessionHandlerUnlock(), true);
