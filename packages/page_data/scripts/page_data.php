<?php

    final class page_data {

        private static $cfg = null;
        private static $page_data = null;

        //Инициализация скрипта
        public static function onLoad(){

            //Грузим данные в память
            if(self::$cfg === null){
                self::$cfg = PICCOLO_ENGINE::loadConfig('page_data');
            }
            
        }

        //Действие при автозагрузке
        public static function autoload(){
            //Регистрация обработчика тега
            if(!isset(self::$cfg['autoreg']) || ((boolean) self::$cfg['autoreg'])){
                PICCOLO_ENGINE::RegisterTagHandler('page', 'page_data');
            }
            
        }

        /*
         * Обрабатывает полученный тег
         */

        public static function handleTag($tag){
            if(self::$page_data === null){
                self::detectPage();
            }
            if(isset($tag['name']) && isset(self::$page_data[$tag['name']])){
                return PICCOLO_ENGINE::isTmpl('page/'.$tag['name']) 
                     ? PICCOLO_ENGINE::getRTmpl('page/'.$tag['name'], array('data'=>self::$page_data[$tag['name']])) 
                     : self::$page_data[$tag['name']];
            }
        }

        public static function getData($name){
            self::handleTag(array('name'=>$name));
        }
        
        /*
         * Определяет массив с возвращаемыми данными
         */

        private static function detectPage(){
            //Получаем адрес страницы сайта
            $uri = PICCOLO_ENGINE::getURI();
            $page = count($uri) > 0 && $uri[0] != '' ? '/' . rtrim(implode('/', $uri), '/') : '/home';
			self::sortPages();//Упорядочиваем страницы в БД
            
            //Пытаемся найти страницу среди статических
            if(isset(self::$cfg['spages'][$page])){
                self::loadStaticPage($page);
                return;
            }
			//Пытаемся найти страницу среди динамических
            foreach(self::$cfg['dpages'] as $link => $pgw){
				if(strpos($page, $link) === 0 && PICCOLO_ENGINE::checkScript($pgw) && method_exists($pgw, "bindPage")){
					$params = ltrim(self::str_replace_once($link, '', $page),'/');
					self::$page_data = $pgw::bindPage(rtrim($link,'/'),  strlen($params) > 0 ? explode('/',$params) : array());
                    return;
                }
            }

            //Если совсем ничего не нашлось то генерируем 404
            self::$page_data = array(
                'title' => PICCOLO_ENGINE::translate('404_TITLE', 'page_data'), //Заголовок из локали
                'content' => PICCOLO_ENGINE::getTmpl('404')//Содержимое из шаблона
            );
        }

        public static function sort($f, $s){//Функция нужна для сортировки страниц по длинне URI
            if(strlen($f) <= strlen($s)){
                return 1;
            }
            if(strlen($f) == strlen($s)){
                return 0;
            }
            return -1;
        }
        
        private static function sortPages(){//Сортирует страницы в БД
			if(isset(self::$cfg['spages']) && self::$cfg['spages'] != null){
				uksort(self::$cfg['spages'], array('page_data','sort'));//статика
			}
			if(isset(self::$cfg['dpages']) && self::$cfg['dpages'] != null){
				uksort(self::$cfg['dpages'], array('page_data','sort'));//динамика
			}
            PICCOLO_ENGINE::updateConfig('page_data',self::$cfg);
        }
        
        private static function loadStaticPage($page){
            
                self::$page_data = self::$cfg['spages'][$page];
                if(isset(self::$page_data['parse_content']) && ((boolean) self::$page_data['parse_content'])){
                    self::$page_data['content'] = PICCOLO_ENGINE::PrepearHTML(self::$page_data['content']);
                }
                if(PICCOLO_ENGINE::checkScript('breadcrumbs')){
                    breadcrumbs::setTitle($page, self::$page_data['title']);
                }
        }
        
		public static function str_replace_once($search, $replace, $text){ 
		   $pos = strpos($text, $search); 
		   return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text; 
		} 
		
    }
    
