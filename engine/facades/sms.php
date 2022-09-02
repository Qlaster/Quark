<?php


	//Заюзываем библиотечку sms.ru
	lib('sms/sms.ru/sms.ru.php');




	
	class Q_Sms_Interface
	{
		public $config;
		public $adapter;
		
		public function __construct($config = null) 
		{
			if ($config) $this->config = $config;
		}
		
		public function Send($number, $text)
		{
			
			
		}
		
		
	}




	class Q_Sms_Adapter_Smsru
	{
		
		public function __construct($config = null) 
		{
			if ($config) $this->config = $config;
		}
		
		public function Send($number, $text, $from=null)
		{
			$smsru = new SMSRU($this->config['smsru']['key']); // Ваш уникальный программный ключ, который можно получить на главной странице

			$data = new stdClass();
			$data->to = $number;
			$data->text = $text; // Текст сообщения
			$data->from = $from;
			
			// $data->from = ''; // Если у вас уже одобрен буквенный отправитель, его можно указать здесь, в противном случае будет использоваться ваш отправитель по умолчанию
			// $data->time = time() + 7*60*60; // Отложить отправку на 7 часов
			// $data->translit = 1; // Перевести все русские символы в латиницу (позволяет сэкономить на длине СМС)
			// $data->test = 1; // Позволяет выполнить запрос в тестовом режиме без реальной отправки сообщения
			// $data->partner_id = '1'; // Можно указать ваш ID партнера, если вы интегрируете код в чужую систему
			$sms = $smsru->send_one($data); // Отправка сообщения и возврат данных в переменную

			if ($sms->status == "OK") 
			{ 
				return true;
				// Запрос выполнен успешно
				//~ echo "Сообщение отправлено успешно. ";
				//~ echo "ID сообщения: $sms->sms_id. ";
				//~ echo "Ваш новый баланс: $sms->balance";
			} 
			else 
			{
				//~ echo "Сообщение не отправлено. ";
				//~ echo "Код ошибки: $sms->status_code. ";
				//~ echo "Текст ошибки: $sms->status_text.";
				return false;
			}

			
		}
		
	}







	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ] --------------- #
	# ---------------------------------------------------------------- #


	//Создаем экземпляр смс шлюза
	//~ $Sms = new Q_Sms_Interface();
	return new Q_Sms_Adapter_Smsru( $this->config->get(__file__) );

