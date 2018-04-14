<?php


    class last_comments {
        
        private static $last_comments = array();
        
        public static function onLoad(){
            self::$last_comments = PICCOLO_ENGINE::loadData('last_comments');
            if(!is_array(self::$last_comments)){
                self::$last_comments = array();
            }
            PICCOLO_ENGINE::RegisterTagHandler('last_comments', 'last_comments');
        }
        
        public static function onEvent($event,$data){
            switch($event){
                case 'piccolo_comments.onMessage':
                    array_unshift(self::$last_comments, $data);
                    PICCOLO_ENGINE::updateData('last_comments', self::$last_comments);
                    break;
            }
        }
        
        public static function handleTag($tag){
            if(count(self::$last_comments) < 1){return 'Нет данных для отображения';}
            $messages = '';
            foreach(array_slice(self::$last_comments, 0, 3) as $message){
               $messages .= PICCOLO_ENGINE::getRTmpl('last_comments/comment',array('comment'=>  htmlspecialchars($message['comment'])) + $message);
            }
            return PICCOLO_ENGINE::getRTmpl('last_comments/comments_frame', array('data'=>$messages));
        }
        
    }
