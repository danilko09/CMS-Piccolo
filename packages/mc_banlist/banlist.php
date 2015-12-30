<?php


class mc_banlist {
    
    private static $cfg = null;
    
    public static function onLoad(){
        self::$cfg = PICCOLO_ENGINE::loadConfig('mc_banlist');
    }
    
    public static function bindAdminPage(){
        $msg = '';
        if(filter_input(INPUT_POST,'banlist_settings_post') !== null){
            self::$cfg['on_page'] = self::filter_input(INPUT_POST, 'rows_count', 10);
            self::$cfg['dbtable'] = self::filter_input(INPUT_POST, 'tablename','banlist');
            PICCOLO_ENGINE::updateConfig('mc_banlist', self::$cfg);
            $msg = PICCOLO_ENGINE::translate('SETTINGS_SAVED','mc_banlist');
        }
        return PICCOLO_ENGINE::getRTmpl('mc_banlist/settings', self::$cfg + array('msg'=>$msg,'on_page'=>'','dbtable'=>''));
    }
    
    
    
    public static function bindPage(){
        return array('content'=>self::showList());
    }
    
    public static function showList($params = array()){
    
        $res = self::getPage($params);
        
        $table = PICCOLO_ENGINE::getMTmpl('mc_banlist/table_line', $res, array('name'=>''));
        
        return $table == '' ? '' : PICCOLO_ENGINE::getRTmpl('mc_banlist/table', array(
            'lines'=>$table,
            'scriptname'=>__CLASS__
            ));
    }

    public static function showLines($params = array()){
    
        $res = self::getPage($params);
        
        $table = PICCOLO_ENGINE::getMTmpl('mc_banlist/table_line', $res, array('name'=>''));
        
        return $table;
        
    }

    public static function findUser(){
        return PICCOLO_ENGINE::getTmpl('mc_banlist/finduser');
    }
    
    public static function findUserAjax($params){
        $dbtable = isset(self::$cfg['dbtable']) ? self::$cfg['dbtable'] : 'banlist';
        if(!isset($params['username'])){return '0';}
        if(!PICCOLO_ENGINE::checkScript('piccolo_pdo')){return 'NO_PDO';}
        $PDO = piccolo_pdo::getPDO();
        $stmt = $PDO->prepare('SELECT * FROM '.$dbtable.' WHERE name=:username ORDER BY time DESC LIMIT 1');
        $stmt->execute(array(':username'=>$params['username']));
        $result = $stmt->fetchAll();
        return count($result) >= 1 ? 'Игрок '.$params['username'].' находится в списке заблокированных' : 'Игрок '.$params['username'].' не найден в списке заблокированных';
    }
    
    private static function getPage($params){
        $count = isset($params['on_page']) ? $params['on_page'] : (isset(self::$cfg['on_page']) ? self::$cfg['on_page'] : 10);
        $page = isset($params['page']) ? $params['page'] : 1;
        $from = $count * ($page-1);
        $dbtable = isset(self::$cfg['dbtable']) ? self::$cfg['dbtable'] : 'banlist';
        
        if(!PICCOLO_ENGINE::checkScript('piccolo_pdo')){return 'NO_PDO';}
        $PDO = piccolo_pdo::getPDO();
        $stmt = $PDO->prepare('SELECT * FROM '.$dbtable.' ORDER BY time DESC LIMIT '.((int) $from).','.((int) $count));
        $stmt->execute();
        $result = $stmt->fetchAll();
        $table = array();
        foreach($result as $user){
            $table[] = self::formatUser($user);
        }
        return $table;
    }
    
    private static function formatUser($user){
        return $user + array(
            'date'=>date('d-m-Y H:i:s',$user['time']),
            'tempdate'=>$user['temptime'] == 0 ? 'никогда' : date('d-m-Y H:i:s',$user['temptime'])
        );
    }
    
    private static function filter_input($input,$name,$default=null){
        $inp = filter_input($input,$name);
        return ($inp === null) || ($inp === '') ? $default : $inp;
    }
    
}