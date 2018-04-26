<?php

namespace danilko09\minecraft\rcon;
use \xPaw\MinecraftRcon;

class ConnectorException extends \Exception {} //Exception коннектора

/**
 * API для работы с minecraft-сервером
 *
 * @author danilko09
 */
class connector {

    private static $connections = array();
    private static $cfg;
    
    public static function onLoad(){
	self::$cfg = \PICCOLO_ENGINE::loadConfig('minecraft/rcon');
	register_shutdown_function(array(__CLASS__,'unLoad'));
    }

    /**
     * Отправляет команду $command на сервер
     * @param integer $profile_id id профиля сервера
     * @param string $command команда для отправки
     * @return string возвращает результат выполнения команды
     * @throws ConnectorException
     */
    public static function sendCommand($profile_id,$command,$prepare_for_html_out = true){

	self::checkConnection($profile_id);
	
	self::prepareCommand($command);

	if($prepare_for_html_out){
	    return self::toHTML(self::$connections[$profile_id]->Command($command));
	}
	
	return self::$connections[$profile_id]->Command($command);
	
    }

    /**
     * Пробует установить соединение, если оно ещё не установлено. Функция ничего не возвращает, в случае ошибки соединения может выкинуть Exception.
     * @param int $profile_id ID профиля
     * @throws ConnectorException Может выбрасывать Exception`ы, в случае ошибок
     */
    public static function checkConnection($profile_id){
	if(!isset(self::$cfg['profiles'][$profile_id])){ throw new ConnectorException('Bad profile id ('.$profile_id.')'); }
	if(!isset(self::$connections[$profile_id])){
	    self::$connections[$profile_id] = new MinecraftRcon;
	    self::$connections[$profile_id]->Connect( self::$cfg['profiles'][$profile_id]['address'], self::$cfg['profiles'][$profile_id]['port'], self::$cfg['profiles'][$profile_id]['password']);
	}
    }
    
    private static function prepareCommand(&$command){
	$command = trim($command);
	$command = str_replace(array("\r\n", "\n", "\r"),'', $command);
	$command = preg_replace('| +|', ' ', $command);
    }

    private static function toHTML($str){

	$str = str_replace(array('<','>'),array('&lt;','&gt;'),$str);

	$str = str_replace(array("\r\n", "\n", "\r"),'<br>', $str);

	if (!strncmp(substr($str, 2),'Usage',5)) $str = substr($str, 2);

	$str = str_replace(chr(167)."4", "</font><font color='rgb(190,0,0)'>", $str);
	$str = str_replace(chr(167)."c", "</font><font color='rgb(254,63,63)'>", $str);
	$str = str_replace(chr(167)."e", "</font><font color='rgb(254,254,63)'>", $str);
	$str = str_replace(chr(167)."6", "</font><font color='rgb(190,190,0)'>", $str);
	$str = str_replace(chr(167)."2", "</font><font color='rgb(0,0,190)'>", $str);
	$str = str_replace(chr(167)."a", "</font><font color='rgb(63,254,63)'>", $str);
	$str = str_replace(chr(167)."b", "</font><font color='rgb(63,254,254)'>", $str);
	$str = str_replace(chr(167)."3", "</font><font color='rgb(0,190,190)'>", $str);
	$str = str_replace(chr(167)."1", "</font><font color='rgb(0,0,190)'>", $str);
	$str = str_replace(chr(167)."9", "</font><font color='rgb(63,63,254)'>", $str);
	$str = str_replace(chr(167)."d", "</font><font color='rgb(254,63,254)'>", $str);
	$str = str_replace(chr(167)."5", "</font><font color='rgb(190,0,190)'>", $str);
	$str = str_replace(chr(167)."f", "</font><font color='rgb(255,255,255)'>", $str);
	$str = str_replace(chr(167)."7", "</font><font color='rgb(190,190,190)'>", $str);
	$str = str_replace(chr(167)."8", "</font><font color='rgb(63,63,63)'>", $str);
	$str = str_replace(chr(167)."0", "</font><font color='rgb(0,0,0)'>", $str);
	
	$str = str_replace(array(chr(167)), '', $str); 

	return '<font>'.$str.'</font>';
	
    }
    
    public static function unLoad(){
	foreach(self::$connections as $connection){
	    $connection->Disconnect( );
	}
    }
    
}