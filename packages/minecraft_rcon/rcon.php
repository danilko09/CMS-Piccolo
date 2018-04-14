<?php

namespace danilko09\minecraft;
use PICCOLO_ENGINE;

/**
 * Интерфейсная часть rcon. Настройка профилей и сеанс подключения.
 * @author Данил
 */
class rcon {

    private static $config;
    
    public static function onLoad(){
	self::$config = PICCOLO_ENGINE::loadConfig('minecraft/rcon');
	rcon\connector::onLoad();
    }
    
    /**
     * @return возвращает список профилей серверов (id=>название)
     */
    public static function getProfilesList(){
	$ret = array();
	if(isset(self::$config['profiles'])){
	    foreach(self::$config['profiles'] as $id=>$profile){
		$ret[$id] = $profile['title'];
	    }
	}
	return $ret;
    }
    
    /**
     * Отсылает команду на сервер
     * @param integer $profile ID профиля (можно получить список профилей через метод getProfilesList) 
     * @param string $command комманда для отправки на сервер
     * @return string возвращает результат выполнения команды, может выкинуть Exception, если возникнет ошибка
     */
    public static function sendCommand($profile,$command,$prepare_for_html_out = true){
	return \danilko09\minecraft\rcon\connector::sendCommand($profile, $command,$prepare_for_html_out);
    }
    
    public static function bindAdminPage($b,$p){
	if(!isset($p[0])){
	    return self::index($b);
	}
	$act = array_shift($p);
	switch($act){
	    case 'setup':
		return self::setup($b.'/'.$act,$p);
	    case 'console':
		return self::console($b.'/'.$act,$p);
	    default: 
		return '404';
	}
    }

    private static function index($b){
	self::breadcrumbs($b, PICCOLO_ENGINE::translate('INDEX_TITLE','minecraft_rcon'));
	$menu = new \danilko09\menus\menu(array(
	    array('link_title'=>PICCOLO_ENGINE::translate('MENU_SETUP_TITLE','minecraft_rcon'),'link_url'=>PICCOLO_ENGINE::getIndex().$b.'/setup'),
	    array('link_title'=>PICCOLO_ENGINE::translate('MENU_CONSOLE_TITLE','minecraft_rcon'),'link_url'=>PICCOLO_ENGINE::getIndex().$b.'/console')
	));
	return $menu->getHTML();
    }

    private static function setup($b){
	self::breadcrumbs($b, PICCOLO_ENGINE::translate('SETUP_TITLE','minecraft_rcon'));
	if(filter_input(INPUT_POST,'rcon_add_server_posted') !== null){
	    if(!isset(self::$config['profiles'])){self::$config['profiles'] = array();}
	    self::$config['profiles'][] = filter_input_array(INPUT_POST);
	    PICCOLO_ENGINE::updateConfig('minecraft/rcon',self::$config);
	}elseif(filter_input(INPUT_GET,'delete') !== null){
	    unset(self::$config['profiles'][filter_input(INPUT_GET,'delete')]);
	    PICCOLO_ENGINE::updateConfig('minecraft/rcon',self::$config);
	    header('Location: '.PICCOLO_ENGINE::getURL());
	}
	return PICCOLO_ENGINE::getRTmpl('minecraft/rcon/setup',array('servers'=>  isset(self::$config['profiles']) ? \PICCOLO_ENGINE::getMTmpl('minecraft/rcon/setup_list', 
		self::$config['profiles'], 
		array('id'=>'id','title'=>'','address'=>'','port'=>''), 'id') : ''));
    }
    
    private static function breadcrumbs($uri,$title,$override = true){
	if(PICCOLO_ENGINE::checkScript('breadcrumbs')){
	    \breadcrumbs::setTitle($uri, $title, $override);
	}
    }
    
    private static function console($b,$p){
	
	self::breadcrumbs($b, PICCOLO_ENGINE::translate('CONSOLE_TITLE','minecraft_rcon'));
	if(!isset($_SESSION['rconConsole']) || $_SESSION['rconConsole'] == "") {
	    $_SESSION['rconConsole'] = "Добро пожаловать в RCON консоль.<br/><br/>Для подключения к серверу введите команду:<br/>connect [ID профиля]<br/>";
	    $_SESSION['rconConsole'] .= 'Доступные профили:<br/>';
			foreach(self::getProfilesList() as $id=>$profile){
			    $_SESSION['rconConsole'] .= $id.' '.$profile.'<br/>';
			}
	}
	
	if(isset($_POST['command']) && $_POST['command'] != ""){
	
		$_SESSION['rconConsole'] .= "<br/>[".date("h:i")."] ".$_POST['command']."<br/>";
		
		$cmd = explode(" ", $_POST['command']);
		
		if($_POST['command'] == "cls"){
		
			$_SESSION['rconConsole'] = "Консоль была очищена.<br/>";
		
		}elseif($cmd['0'] == "connect"){
			
		    if(!isset($cmd[1])){
			$_SESSION['rconConsole'] .= 'Доступные профили:<br/>';
			foreach(self::getProfilesList() as $id=>$profile){
			    $_SESSION['rconConsole'] .= $id.' '.$profile.'<br/>';
			}
		    }else{
		    
		    $_SESSION['rcon_profile'] = $cmd[1];
		    try{
			\danilko09\minecraft\rcon\connector::checkConnection($cmd[1]);
		    $_SESSION['rconConnected'] = 1;$_SESSION['rconConsole'] .= 'Соединение установлено.<br/><br/>';
		    }catch(\xPaw\MinecraftRconException $e){
						$_SESSION['rconConsole'] .= 'ОШИБКА: '.$e->getMessage();
		    }
		    }
		    
		}elseif($cmd['0'] == "reset"){
		
			$_SESSION['rconConsole'] = "Добро пожаловать в RCON консоль.<br/><br/>Для подключения к серверу введите команду:<br/>connect [ID профиля]<br/>";
	$_SESSION['rconConsole'] .= 'Доступные профили:<br/>';
			foreach(self::getProfilesList() as $id=>$profile){
			    $_SESSION['rconConsole'] .= $id.' '.$profile.'<br/>';
			}	
		}elseif($_SESSION['rconConnected'] != 1){
		
			$_SESSION['rconConsole'] .= "<br/><br/><font color='red'>Нет соединения с сервером.</font><br/>Для подключения к серверу введите команду:<br/>connect<br/>";
		
		}else{
		    
		    try{
			$_SESSION['rconConsole'] .= \danilko09\minecraft\rcon\connector::sendCommand($_SESSION['rcon_profile'],$_POST['command'],true);
		    }catch(\xPaw\MinecraftRconException $e){
			$_SESSION['rconConsole'] .= 'ОШИБКА: '.$e->getMessage();
		    }
		}
		
	}
	
	return "<div style='background-color: black; overflow:auto; height: 400px; width: 100%; border: ridge 10px darkblue; color: wheat' id='console'>".$_SESSION['rconConsole']."</div><br/><form method='post'><input type='text' style='width: 70%;' name='command'><input type='submit' value='отправить'></form><script>document.getElementById('console').scrollTop = document.getElementById('console').scrollHeight</script>";

    }
    
} 