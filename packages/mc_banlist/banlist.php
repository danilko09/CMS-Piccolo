<?php


class mc_banlist {
    
    private static $cfg = null;
    
    public static function onLoad(){
        self::$cfg = PICCOLO_ENGINE::loadConfig('mc_banlist');
    }
    
    public static function bindAdminPage(){
        return PICCOLO_ENGINE::getRTmpl('mc_banlist/settings', self::$cfg);
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

    private static function getPage($params){
        $count = isset(self::$cfg['on_page']) ? self::$cfg['on_page'] : isset($params['on_page']) ? $params['on_page'] : 10;
        $page = isset($params['page']) ? $params['page'] : 1;
        $from = $count * ($page-1);
        
        if(!PICCOLO_ENGINE::checkScript('piccolo_pdo')){return 'NO_PDO';}
        $PDO = piccolo_pdo::getPDO();
        $stmt = $PDO->prepare('SELECT * FROM banlist ORDER BY time DESC LIMIT '.((int) $from).','.((int) $count));
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
    
}