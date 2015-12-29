<?php

final class piccolo_pdo {

    //Настройки PDO по умолчанию
    private static $dsn;
    private static $connect_info;
    private static $opt = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );
    
    private static $db = null;
    
    //Выдает ссылку на PDO
    public static function getPDO(){
        //Если функция вызывается не в первый раз, то выдаем ссылку на объект PDO
        if(self::$db != null){return self::$db;}
        //Грузим настройки БД
        $cfg = WSE_ENGINE::loadConfig("piccolo_pdo");
        self::$dsn = $cfg['dsn'];
        self::$connect_info = $cfg['connect_info'];
        //Пытаемся подключиться, в случае ошибки, по возможности, логируем ошибку
        try{
            self::$db = new PDO(self::$dsn,self::$connect_info['username'],self::$connect_info['password'],self::$opt);
        }catch(PDOException $e){
            if(WSE_ENGINE::checkScript('piccolo_log')){
                piccolo_log::add($e->getMessage(),reports_log::LEVEL_ERROR,$e->getTraceAsString());
            }
        }
        return self::$db;
    }
    
    public static function bindAdminPage(){
        
        $cfg = WSE_ENGINE::loadConfig("piccolo_pdo");
        $msg = '';
        
        if(filter_input(INPUT_POST,'pdo_settings_post') !== null){
            $cfg['dsn'] = filter_input(INPUT_POST,'dsn');
            $cfg['connect_info']['username'] = filter_input(INPUT_POST,'username');
            $cfg['connect_info']['password'] = filter_input(INPUT_POST,'password');
            PICCOLO_ENGINE::updateConfig('piccolo_pdo', $cfg);
            self::$db = null;
            $msg = PICCOLO_ENGINE::getTmpl('piccolo_pdo/settings_saved');
        }
        
        return PICCOLO_ENGINE::getRTmpl('piccolo_pdo/settings', array(
            'msg'=>$msg,
            'dsn'=>$cfg['dsn'],
            'username'=>$cfg['connect_info']['username'],
            'password'=>$cfg['connect_info']['password']
                ));
    }
    
}