<?php


	use QyberTech\View\Presenter;


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Создаем шаблонизотор
	return new Presenter( $this->config->get(__file__) );





