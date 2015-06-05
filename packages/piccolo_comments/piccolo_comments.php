<?php

    final class comments {

        private static $cfg = null;

        public static function onLoad(){
            if(self::$cfg === null){
                self::$cfg = PICCOLO_ENGINE::loadConfig('piccolo_comments');
            }
        }

        public static function autoload(){
            if(isset(self::$cfg['autoreg']) && ((boolean) self::$cfg['autoreg'])){
                WSE_ENGINE::RegisterTagHandler('comments', 'comments');
                WSE_ENGINE::RegisterTagHandler('comments_count', 'comments');
            }
        }

        public static function handleTag($tag){
            switch($tag['type']){
                case 'comments': return self::getComments($tag);
                case 'comments_count': return isset(self::$cfg['comments'][$tag['pageID']]) ? count(self::$cfg['comments'][$tag['pageID']])
                                : '0';
            }
        }

        private static function getComments($tag){
            if(filter_input(INPUT_POST, 'piccolo_comments_secret') !== null && $tag['pageID'] == filter_input(INPUT_POST, 'piccolo_comments_secret') && filter_input(INPUT_POST,'comment') != null && trim(filter_input(INPUT_POST,'comment')) != ''){
                $pageID = filter_input(INPUT_POST, 'piccolo_comments_secret');
                if(!isset(self::$cfg['comments'][$pageID])){
                    self::$cfg['comments'][$pageID] = array();
                }
                self::$cfg['comments'][$pageID][] = filter_input_array(INPUT_POST) + array('time' => time());
                PICCOLO_ENGINE::updateConfig('piccolo_comments', self::$cfg);
            }
            if(isset(self::$cfg['comments'][$tag['pageID']])){
                return self::getCommentsForm(isset($tag['style']) ? $tag['style'] : 'default', $tag['pageID'], self::$cfg['comments'][$tag['pageID']]);
            }else{
                return self::getCommentsForm(isset($tag['style']) ? $tag['style'] : 'default', $tag['pageID'], array());
            }
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
            return PICCOLO_ENGINE::getRTmpl('piccolo_comments/styles/' . $style, array('pageID' => $pageID, 'comments' => $comments));
        }

        private static function prepareData($text){
            $text = htmlspecialchars($text);
            $text = str_replace(array('[', ']'), array('&#091;', '&#093;'), $text);
            return $text;
        }

        private static function prepareComment($text){
            $text = self::prepareData($text);
            if(isset(self::$cfg['show_links']) && self::$cfg['show_links'] == '1'){
                $text = preg_replace("~(http|https|ftp|ftps)://(.*?)(\s|\n|[,.?!](\s|\n)|$)~", '<a href="$1://$2">$1://$2</a>$3', $text);
            }
            return $text;
        }

    }
    