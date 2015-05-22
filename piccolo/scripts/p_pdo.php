<?php

	final class piccolo_pdo {
		//Настройки PDO по умолчанию
		private static $dsn;
		private static $connect_info;
		private static $opt = [
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			PDO::ATTR_EMULATE_PREPARES => false,
		];
	
		//Выдает ссылку на PDO
		public static function getPDO(){
			//Если функция вызывается не в первый раз, то выдаем ссылку на объект PDO
			if(self::$db != null) return self::$db;
			//Грузим настройки БД
			$cfg = WSE_ENGINE::loadConfig("piccolo_pdo");
			self::$dsn = $cfg['dsn'];
			self::$connect_info = $cfg['connect_info'];
			//Пытаемся подключиться, в случае ошибки, по возможности, логируем ошибку
			try{
				self::$db = new PDO(self::$dsn,self::$connect_info['username'],self::$connect_info['password'],self::$opt);
			}catch(PDOException $e){
				if(WSE_ENGINE::checkScript('piccolo_log')) {
					piccolo_log::add($e->getMessage(),reports_log::LEVEL_ERROR,$e->getTraceAsString());
				}
			}
		}  
	}