<?php

//Скрипт минимальной авторизации, ничего более
//Поддержка шаблонов, в качестве БД использует скрипт users_base
//Вставлять лучше как модуль, либо делать смешанный ajax-запрос (в GET передать информацию о запросе, а в POST содержимое формы)
    class min_auth {

        private static $config = null;

        public static function onLoad(){
            if(!WSE_ENGINE::checkScript('users_base')){
                return false;
            }
            if(self::$config === null){
                self::$config = PICCOLO_ENGINE::loadConfig('min_auth');
            }
        }

        public static function showForm(){
            $msg = "";
            if(!WSE_ENGINE::checkScript('users_base')){
                return WSE_ENGINE::translate('NO_USERS_BASE', 'min_auth');
            }
            if(filter_input(INPUT_POST, 'min_auth_secret') != null){
                $msg = self::tryLogIn();
            }
            $captcha = self::getCaptcha();
            return (users_base::getCurrentUserId() === null) ? $msg . WSE_ENGINE::getRTmpl("min_auth/form", array('captcha' => $captcha))
                        : WSE_ENGINE::translate('ALREADY_LOGGED_IN', 'min_auth');
        }

        public static function logout(){
            if(!WSE_ENGINE::checkScript('users_base')){
                return;
            }
            if(filter_input(INPUT_GET, 'min_auth_logout') !== null){
                users_base::logout();
                header("Location: " . WSE_ENGINE::getIndex());
                exit;
            }
            if(users_base::getCurrentUserId() !== null){
                return '<li><a href="' . WSE_ENGINE::getURL_BASE() . '?min_auth_logout=1">выйти</a></li>';
            }
        }

        public static function showLogin(){
            if(!WSE_ENGINE::checkScript('users_base')){
                return;
            }
            return users_base::getFieldById(users_base::getCurrentUserId(), 'login');
        }

        private static function tryLogIn(){
            $user_id = users_base::getIdByField('login', filter_input(INPUT_POST, 'login'));
            $user = users_base::getDataById($user_id);
            if(!self::checkCaptcha()){
                return PICCOLO_ENGINE::translate('BAD_CAPTCHA', 'min_auth');
            }
            if($user !== null && isset($user['password']) && $user['password'] == filter_input(INPUT_POST, 'password')){
                users_base::authAsId($user_id);
                return;
            }
            return WSE_ENGINE::translate('BAD_LOGIN', 'min_auth');
        }

        private static function getCaptcha(){
            //Если в конфиге у нас не определено использование гугло-каптчи или там что-то отличное от "true"
            if(!isset(self::$config['use_google_recaptcha']) || self::$config['use_google_recaptcha'] !== true){
                return '';
            }
            //Если у нас в конфиге 100% true
            if(!PICCOLO_ENGINE::checkScript('google_recaptcha')){
                //Если каптча не загружается или вообще не установлена, то выдаем сообщение об ошибке
                return PICCOLO_ENGINE::translate('NO_RECAPTCHA_FOUND', 'min_auth');
            }
            //Если у нас все-таки получилось загрузить скрипт катчи и в этом есть необходимость, то запрашиваем таки элемент формы, который будет проводить проверку
            return google_recaptcha::showCaptcha();
        }

        private static function checkCaptcha(){
            //Если в конфиге у нас не определено использование гугло-каптчи или там что-то отличное от "true", то просто отвечаем, что она пройдена успешно
            if(!isset(self::$config['use_google_recaptcha']) || self::$config['use_google_recaptcha'] !== true){
                return true;
            }
            //Если у нас в конфиге 100% true
            if(!PICCOLO_ENGINE::checkScript('google_recaptcha')){
                //Если каптча не загружается или вообще не установлена, то сообщаем о том, что каптча пройдена
                //Ну мало ли админ случайно удалил, пусть лучше каптча не будет работать, чем админ в админку зайти не сможет
                return true;
            }
            //Если все супер, то спрашиваем у скрипта как там юзер: прошел или нет
            return google_recaptcha::checkCaptcha(INPUT_POST);
        }

        public static function bindAdminPage($b,$p){
            $msg = '';
            if(filter_input(INPUT_POST,'min_auth_admin_form') !== null){
                self::$config['use_google_recaptcha'] = filter_input(INPUT_POST,'use_google_recaptcha') !== null;
                PICCOLO_ENGINE::updateConfig('min_auth',self::$config);
                $msg = PICCOLO_ENGINE::translate('SETTINGS_SAVED','min_auth');
            }
            $checked = (!isset(self::$config['use_google_recaptcha']) || self::$config['use_google_recaptcha'] !== true)
                     ? '' : 'checked';
            return PICCOLO_ENGINE::getRTmpl('min_auth/admin', array('msg'=>$msg,'use_google_recaptcha'=>$checked));
        }
        
    }
    