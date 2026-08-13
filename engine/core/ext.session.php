<?php
/*
 * ext.session.php
 *
 * Copyright 2025 vladimir <vladimir@MacBookAirM1>
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
	public $option = ['attempts'=>10, 'delay'=>20000]; //delay 20 ms

	#[\ReturnTypeWillChange]
	public function open($savePath, $sessionName): bool
	{
		$this->savePath = $savePath;
		if (!is_dir($this->savePath))
			mkdir($this->savePath, 0777, true);

		return true;
	}

	#[\ReturnTypeWillChange]
	public function close(): bool
	{
		return true;
	}

	#[\ReturnTypeWillChange]
	public function read($id): string
	{
		$file = "$this->savePath/sess_$id";
		if (!file_exists($file)) return '';

		for ($attempt = 0; $attempt < $this->option['attempts']; $attempt++)
		{
			$fh = @fopen($file, 'r');
			if ($fh === false)
			{
				// Не удалось открыть файл, пробуем снова
				usleep($this->option['delay']);
				continue;
			}

			//Если сессия в данный момент заблокирована - ожидаем разблокировки
			if (@flock($fh, LOCK_SH) === false)
			{
				@fclose($fh);
				usleep($this->option['delay']);
				continue;
			}

			$contents = @stream_get_contents($fh);
			@flock($fh, LOCK_UN);
			@fclose($fh);

			// Прерываемся после удачной попытки чтения
			break;
		}

		// Гарантируем возвращение строки
		return (string) $contents;
	}

	#[\ReturnTypeWillChange]
	public function write($id, $data): bool
	{
		$file = "$this->savePath/sess_$id";

		$fh = @fopen($file, 'c'); // создать файл если не существует
		if ($fh === false)
			return false;

		//Попытка залочить файл сессии
		if (@flock($fh, LOCK_EX) === false)
		{
			@fclose($fh);
			return false;
		}

		if ($success = @ftruncate($fh, 0) && @rewind($fh))
		{
			$bytes = @fwrite($fh, $data);
			$success = ($bytes !== false && $bytes === strlen($data));
		}

		@flock($fh, LOCK_UN);
		@fclose($fh);

		return $success;
	}

	#[\ReturnTypeWillChange]
	public function destroy($id): bool
	{
		$file = "$this->savePath/sess_$id";
		if (file_exists($file))
			unlink($file);

		return true;
	}

	#[\ReturnTypeWillChange]
	public function gc($maxlifetime)
	{
		$erasedSessionsCount = 0;
		foreach (glob("$this->savePath/sess_*") as $file)
		{
			if (filemtime($file) + $maxlifetime < time() && file_exists($file))
			{
				unlink($file);
				$erasedSessionsCount++;
			}
		}
		return $erasedSessionsCount;
	}
}

session_set_save_handler(new QSessionHandlerUnlock(), true);
