<?php

    final class page_data_dynamic_admin {
        
		private static $cfg = array();
		
        public static function bindAdminPage($link, $params){
			$msg = '';
			self::$cfg = PICCOLO_ENGINE::loadConfig('page_data');            
			if(filter_input(INPUT_GET,'remove') !== null){
				unset(self::$cfg['dpages'][filter_input(INPUT_GET,'remove')]);
				PICCOLO_ENGINE::updateConfig('page_data',self::$cfg);
				$msg = PICCOLO_ENGINE::getRTmpl('page_data/admin/message',array('message'=>'Страница удалена'));				
			}elseif(filter_input(INPUT_POST,'add') !== null){
				self::$cfg['dpages'][filter_input(INPUT_POST,'add_url')] = filter_input(INPUT_POST,'add_script');
				PICCOLO_ENGINE::updateConfig('page_data',self::$cfg);
				$msg = PICCOLO_ENGINE::getRTmpl('page_data/admin/message',array('message'=>'Страница добавлена'));				
			}
			
			$pages = self::genList($link,$params);
			$scripts = PICCOLO_ENGINE::getMTmpl('page_data/admin/dscript',PICCOLO_ENGINE::getAllScriptsInfo(), array(), 'alias');
			
			return PICCOLO_ENGINE::getRTmpl('page_data/admin/dframe',array('pages'=>$pages,'message'=>$msg,'scripts'=>$scripts));
			
        }
        
        private static function genList(){
            self::$cfg = PICCOLO_ENGINE::loadConfig('page_data');
            $pages = self::$cfg['dpages'];
			$n = 0;$ret = '';
            foreach($pages as $alias=>$pgw){
                if($alias[0] !== '/'){continue;}
                $n++;
                $ret .= PICCOLO_ENGINE::getRTmpl('page_data/admin/dlink',array('n'=>$n,'alias'=>ltrim($alias,'/'),'page_worker'=>$pgw));//$n.'. <a href="%index%/'.ltrim($alias,'/').'">'.$alias.'</a> <div style="float: right;">{'.$pgw.'} [изменить | удалить]</div><br/>';
            }
            return $ret;
        }
        
    }