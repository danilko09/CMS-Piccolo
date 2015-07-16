<?php
    
    require_once PICCOLO_SCRIPTS_DIR.DIRECTORY_SEPARATOR.'google_recaptcha'.DIRECTORY_SEPARATOR.'lib.php';//Подгружаем классы от гугла
    
    final class google_recaptcha {
        
        private static $config = null;
        
        public static $last_err = array();
        
        public static function onLoad(){
            if(self::$config === null){
                self::$config = PICCOLO_ENGINE::loadConfig('google_recaptcha');//Запрашиваем у ядра конфиг
            }
    
            return class_exists('\ReCaptcha\ReCaptcha');//Если класс есть - мы загрузились, если нет, то не загрузились
        }
        
        public static function showCaptcha(){
            if(isset(self::$config['siteKey']) && isset(self::$config['secret'])){
                return '<div class="g-recaptcha" data-sitekey="'.self::$config['siteKey'].'"></div>'
                        . '<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=ru"></script>';
            }else{
                return PICCOLO_ENGINE::translate('BAD_RECAPTCHA_CONFIG');
            }
        }
        
        public static function checkCaptcha($input_type){
            if(!isset(self::$config['siteKey']) || !isset(self::$config['secret'])){return false;}//Говорим, что капча не пройдена, если у нас нет ключей
            if(filter_input($input_type,'g-recaptcha-response') !== null){//Если нам что-то отправлено, то пробуем распарсить
                //Проверяем
                $recaptcha = new \ReCaptcha\ReCaptcha(self::$config['secret']);
                $resp = $recaptcha->verify(filter_input($input_type,'g-recaptcha-response'), $_SERVER['REMOTE_ADDR']);
                //Сохраняем ошибку, если таковая была
                self::$last_err = $resp->getErrorCodes();
                //Возвращаем результат проверки
                return $resp->isSuccess();
            }
            //Если нам ничего не было передано, то отвечаем false
            return false;
        }

        public static function bindAdminPage(){
            
            if(filter_input(INPUT_POST,'google_recaptcha_admin_secret') !== null){
                self::$config['siteKey'] = filter_input(INPUT_POST,'siteKey');
                self::$config['secret'] = filter_input(INPUT_POST,'secret');
                PICCOLO_ENGINE::updateConfig('google_recaptcha',self::$config);
            }
            
            $demo = self::showCaptcha();
            
            if(filter_input(INPUT_POST,'google_recaptcha_admin_demo_secret') !== null){
                if(self::checkCaptcha(INPUT_POST)){
                    $demo .= '<font color="green">Вы не робот.</font>';
                }else{
                    $demo .= '<font color="red">Похоже, что что-то пошло не так.</font>';
                }
            }else{
                $demo .= 'Проверка не пройдена.';
            }
            
            return PICCOLO_ENGINE::getRTmpl('google_recaptcha/admin_forms', self::$config + array('siteKey'=>'','secret'=>'','demo'=>$demo));
            
        }
        
    }