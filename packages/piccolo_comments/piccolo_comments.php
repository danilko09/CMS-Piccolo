<?php

    final class comments {

        private static $config = null;

        public static function onLoad(){
            if(self::$config === null){
                self::$config = PICCOLO_ENGINE::loadConfig('piccolo_comments');
            }
        }

        public static function autoload(){
            if(!isset(self::$config['autoreg']) || ((boolean) self::$config['autoreg'])){
                WSE_ENGINE::RegisterTagHandler('comments', 'comments');
                WSE_ENGINE::RegisterTagHandler('comments_count', 'comments');
            }
        }

        public static function handleTag($tag){
            switch($tag['type']){
                case 'comments': return self::getComments($tag);
                case 'comments_count': 
                    return self::anyComments($tag['pageID']) ? count(self::loadComments($tag['pageID'])) : '0';
            }
        }

        private static function getComments($tag){
            if(self::checkCaptcha() && filter_input(INPUT_POST, 'piccolo_comments_secret') !== null && $tag['pageID'] == filter_input(INPUT_POST, 'piccolo_comments_secret') && filter_input(INPUT_POST,'comment') != null && trim(filter_input(INPUT_POST,'comment')) != ''){
                $pageID = filter_input(INPUT_POST, 'piccolo_comments_secret');
                $post = filter_input_array(INPUT_POST) + array('time' => time());
                self::addComment($pageID, $post);
                PICCOLO_ENGINE::onEvent('piccolo_comments.onMessage',$post + $tag);
                PICCOLO_ENGINE::updateConfig('piccolo_comments', self::$config);
            }

            $style = isset($tag['style']) ? $tag['style'] : 'default';
            return self::getCommentsForm($style, $tag['pageID'], self::loadComments($tag['pageID']));

        }

        private static function getCommentsForm($style, $pageID, $comments_arr){
            if(!PICCOLO_ENGINE::isTmpl('piccolo_comments/styles/' . $style) || !PICCOLO_ENGINE::isTmpl('piccolo_comments/styles/' . $style . '_comment')){
                return PICCOLO_ENGINE::translate('BAD_COMMENTS_STYLE', 'piccolo_comments');
            }
            $comments = '';
            foreach($comments_arr as $id => $comment){
                $comments .= PICCOLO_ENGINE::getRTmpl(
                                'piccolo_comments/styles/' . $style . '_comment', array(
                            'comment' => self::prepareComment($comment['comment']),
                            'name' => $comment['name'] !== '' ? self::prepareData($comment['name']) : PICCOLO_ENGINE::translate('NO_NAME', 'piccolo_comments'),
                            'date' => date('j.m.Y G:i \G\M\TP', $comment['time']),
                            'id' => $id,
                            'pageID' => $pageID
                                )
                );
            }
            if($comments == ''){
                $comments = PICCOLO_ENGINE::translate('NO_COMMENTS', 'piccolo_comments');
            }
            return PICCOLO_ENGINE::getRTmpl('piccolo_comments/styles/' . $style, array('pageID' => $pageID, 'comments' => $comments,'captcha'=>self::getCaptcha()));
        }

        private static function addComment($pageID,$comment){
            self::migrate_if_need($pageID);
            $data[] = $comment;
            PICCOLO_ENGINE::updateData('piccolo_comments/'.$pageID, $data);
        }
        
        private static function loadComments($pageID){
            self::migrate_if_need($pageID);
            return PICCOLO_ENGINE::loadData('piccolo_comments/'.$pageID);
        }
        
        private static function anyComments($pageID){
            self::migrate_if_need($pageID);
            return PICCOLO_ENGINE::isData('piccolo_comments/'.$pageID);
        }

        private static function migrate_if_need($pageID){
            if(isset(self::$config['comments'][$pageID])){
                PICCOLO_ENGINE::updateData('piccolo_comments/'.$pageID, self::$config['comments'][$pageID]);
                unset(self::$config['comments'][$pageID]);
                PICCOLO_ENGINE::updateConfig('piccolo_comments', self::$config);
            }
        }
        
        private static function prepareData($text){
            $text = htmlspecialchars($text);
            $text = str_replace(array('[', ']'), array('&#091;', '&#093;'), $text);
            return $text;
        }

        private static function prepareComment($text){
            $text = self::prepareData($text);
            if(isset(self::$config['show_links']) && self::$config['show_links'] == '1'){
                $text = preg_replace("~(http|https|ftp|ftps)://(.*?)(\s|\n|[,.?!](\s|\n)|$)~", '<a href="$1://$2">$1://$2</a>$3', $text);
            }
            return $text;
        }
        
        private static function checkCaptcha(){
            
            //Если в конфиге у нас не определено использование гугло-каптчи или там что-то отличное от "true", то просто отвечаем, что она пройдена успешно
            if(!isset(self::$config['use_google_recaptcha']) || self::$config['use_google_recaptcha'] !== true){
                return true;
            }
            //Если у нас в конфиге 100% true
            if(!PICCOLO_ENGINE::checkScript('google_recaptcha')){
                //Если каптча не загружается или вообще не установлена, то сообщаем о том, что каптча пройдена
                //Мало ли админ случайно поломал сайт
                return true;
            }
            //Если все супер, то спрашиваем у скрипта как там юзер: прошел или нет
            return google_recaptcha::checkCaptcha(INPUT_POST);
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

        
    }
    