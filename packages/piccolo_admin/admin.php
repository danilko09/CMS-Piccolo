<?php

    final class admin{
        
        private static $cfg = null;
        
        public static function onLoad(){
            if(self::$cfg == null){
                self::$cfg = PICCOLO_ENGINE::loadConfig('piccolo_admin');
            }
        }
        
        //Генератор страницы
        public static function bindPage($link,$params){
            //Проверка зависимостей
            if(!PICCOLO_ENGINE::checkScript('users_base')){return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>PICCOLO_ENGINE::translate('NO_USERS_BASE','admin'));}
            if(!PICCOLO_ENGINE::checkScript('users_permissions')){return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>PICCOLO_ENGINE::translate('NO_USERS_PERMISSIONS','admin'));}
            if(!PICCOLO_ENGINE::checkScript('min_auth')){return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>PICCOLO_ENGINE::translate('NO_MIN_AUTH','admin'));}
            
            //Если первый запуск, то показываем форму с регистрацией админа
            if(self::checkFirstStart() && filter_input(INPUT_POST,'piccolo_admin_secret') == null){
                return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>PICCOLO_ENGINE::getRTmpl('admin/first_start',array('login'=>users_base::getFieldById(users_base::getCurrentUserId(),'login'))));
            }elseif(self::checkFirstStart() && filter_input(INPUT_POST,'piccolo_admin_secret') != null){
                self::regAdminFirstStart();
            }
            //Авторизация
            if(users_base::getCurrentUserId() === null){
                min_auth::showForm();
                if(users_base::getCurrentUserId() === null){return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>min_auth::showForm());}//двойная проверка на случай отправки запроса, чтоб случайно не показать авторизированному пользователю форму входа
            }
            
            
            if(!users_permissions::isPermittedById(users_base::getCurrentUserId(),'admin.see',false)){
                return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>PICCOLO_ENGINE::translate('NO_PERMISSION','admin'));
            }
            
            //Генерация страницы
            if(PICCOLO_ENGINE::checkScript('breadcrumbs')){breadcrumbs::setTitle($link,PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'));}
            self::genData();//генерируем список скриптов в админке
            if(isset($params[0]) && isset($params[1]) && $params[1] != ''){return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>self::getLinkedPage($link,$params));}//Если это страница категории
            else{return array('title'=>PICCOLO_ENGINE::translate('ADMIN_TITLE','piccolo_admin'),'content'=>self::genList($link));}//Если это главная страница админки
            
        }

        public static function genList($base){
            $list = '';
            foreach(self::$data as $alias=>$category){
                $links = '';
                foreach($category as $link){$links .= PICCOLO_ENGINE::getRTmpl('admin/link',$link+array('base'=>$base));}
                if($links == ''){continue;}
                $category['links'] = $links;
                $category['alias'] = $alias;
                $category['category_name'] = PICCOLO_ENGINE::translate('category_'.$alias,'piccolo_admin');
                $list .= PICCOLO_ENGINE::getRTmpl('admin/list',$category);
            }
            if($list == ''){$list = PICCOLO_ENGINE::translate('NO_LINKS','piccolo_admin');}
            return $list;
        }
        
        private static function getLinkedPage($link,$params){
            $script = $params[1];
            if(PICCOLO_ENGINE::checkScript('breadcrumbs')){
                breadcrumbs::setTitle($link.'/'.$params[0],PICCOLO_ENGINE::translate('category_'.$params[0],'piccolo_admin'));
                $info = PICCOLO_ENGINE::getScriptInfo($params[1]);
                breadcrumbs::setTitle($link.'/'.$params[0].'/'.$params[1],$info['admin_title']);
            }
            $script = $params[1];
            if(PICCOLO_ENGINE::checkScript($script) && method_exists($script, 'bindAdminPage')){
                $link .= '/'.array_shift($params);
                $link .= '/'.array_shift($params);
                return $script::bindAdminPage($link,$params);
            }else{
                return PICCOLO_ENGINE::translate('CANT_BIND_PAGE','piccolo_admin');
            }
        }
        
        private static $data = array();//список скриптов с админкой
        
        private static function genData(){
            $scripts = PICCOLO_ENGINE::getAllScriptsInfo();//получаем информацию о всех скриптах
            $list = array();
            foreach($scripts as $alias=>$data){//перебираем каждый скрипт на наличие категории и названия в админке
                $data['alias'] = $alias;
                if(!isset($data['admin_category']) || !isset($data['admin_title'])){continue;}
                if(!isset($list[$data['admin_category']])){
                    $list[$data['admin_category']] = array($data);
                }else{
                    $list[$data['admin_category']][] = $data;
                }
            }
            self::$data = $list;
        }
        
        private static function regAdminFirstStart(){
            if(!self::checkFirstStart()){return;}//если этап регистрации админа пройден, то шлём подальше
            if(filter_input(INPUT_POST,'piccolo_admin_secret') == null){return;}//Если форма не отослана
            $id = users_base::getIdByField('login',filter_input(INPUT_POST,'login'));
            if($id === null){//если не зареган, то регаем. Если зареган, то обновляем пароль
                users_base::register(array('login'=>filter_input(INPUT_POST,'login'),'password'=>filter_input(INPUT_POST,'password')));
            }else{
                users_base::setFieldById($id,'password',filter_input(INPUT_POST,'password'));
            }
            users_permissions::setPermission(filter_input(INPUT_POST,'login'), 'admin.see', true);//Выставляем права
            self::$cfg['first_start'] = '0';//снимаем флаг первого старта
            PICCOLO_ENGINE::updateConfig('piccolo_admin',self::$cfg);//сохраняемся
        }
        
        private static function checkFirstStart(){
            return !isset(self::$cfg['first_start']) || self::$cfg['first_start'] === '' || self::$cfg['first_start'] === '1';
        }
        
    }
