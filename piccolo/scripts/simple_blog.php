<?php

class piccolo_blog{
    
    //База данных и конфиг
    private static $base = null;
    
    //Первоначальная загрузка скрипта
    public static function onLoad(){
        if(self::$base === null){self::$base = PICCOLO_ENGINE::loadConfig('piccolo_blog');}//Грузим конфиг
    }
    
    //Отображение страницы
    public static function showPage(){
        
        $return = "";
        
        if(isset(self::$base['posts']) && count(self::$base['posts']) > 0){
            $return .= self::genPostsHTML();
        }else{
            $return .= PICCOLO_ENGINE::translate('NO_POSTS', 'piccolo_blog');
        }
        
        return $return;
        
    }
    
    private static function genPostsHTML(){
        
        $posts = "";
        
        foreach(self::$base['posts'] as $post){
            $posts .= PICCOLO_ENGINE::getRTmpl('piccolo_blog/blog_post', $post);
        }
        
        return $posts;
        
    }
    
}