<?php


	//Добавим нашу конфигурацию в провайдер данных
	$this->db->config = array_replace_recursive($this->db->config, $this->config->get());

	//Вернем свежий интерфейс
	return $this->db;
