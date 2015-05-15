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

            //Определяем страницу
            self::detectPage();
        }

        //Действие при автозагрузке
        public static function autoload(){

            //Регистрация обработчика тега
            if(isset(self::$cfg['autoreg']) && ((boolean) self::$cfg['autoreg'])){
                WSE_ENGINE::RegisterTagHandler('page', 'page_data');
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
                return self::$page_data[$tag['name']];
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

            //Пытаемся найти страницу среди статических
            if(isset(self::$cfg['spages'][$page])){
                self::$page_data = self::$cfg['spages'][$page];
                if(isset(self::$page_data['parse_content']) && ((boolean) self::$page_data['parse_content'])){
                    self::$page_data['content'] = PICCOLO_ENGINE::PrepearHTML(self::$page_data['content']);
                }
                return;
            }

            //Пытаемся найти страницу среди динамических
            foreach(self::$cfg['dpages'] as $p => $pgw){
                if(strpos($page, $p) === 0 && PICCOLO_ENGINE::checkScript($pgw) && method_exists($pgw, "bindPage")){
                    self::$page_data = $pgw::bindPage();
                    return;
                }
            }

            //Если совсем ничего не нашлось то генерируем 404
            self::$page_data = array(
                'title' => PICCOLO_ENGINE::translate('404_TITLE', 'page_data'), //Заголовок из локали
                'content' => PICCOLO_ENGINE::getTmpl('404')//Содержимое из шаблона
            );
        }

    }
    