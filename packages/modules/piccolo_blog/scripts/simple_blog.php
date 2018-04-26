<?php

class piccolo_blog{
    
    //База данных и конфиг
    private static $base = null;
    
    //Первоначальная загрузка скрипта
    public static function onLoad(){
        if(self::$base === null){self::$base = PICCOLO_ENGINE::loadConfig('piccolo_blog');}//Грузим конфиг
    }
    
    //хук из page_data
    public static function bindPage(){
        $uri = PICCOLO_ENGINE::getURI();
        $post_id = array_shift($uri);
        if(PICCOLO_ENGINE::checkScript('breadcrumbs')){
            breadcrumbs::setTitle('/' . rtrim(implode('/', $uri), '/'),PICCOLO_ENGINE::translate('BLOG_TITLE','piccolo_blog'));
        }
        if(is_numeric($post_id) && isset(self::$base['posts'][$post_id])){
            return array('title'=>PICCOLO_ENGINE::translate('BLOG_TITLE','piccolo_blog'),'content'=>self::showPost($post_id));
        }else{
            return array('title'=>PICCOLO_ENGINE::translate('BLOG_TITLE','piccolo_blog'),'content'=>self::showPage());
        }
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
     
        $c = 0;
        
        foreach(self::$base['posts'] as $id => $post){
            if(isset(self::$base['onpage']) && self::$base['onpage'] < $c){break;}
            $c++;
			$date = '';
			if(isset($post['date'])){
				$date = date('d.m.Y H:i');
			}else{
				$date = PICCOLO_ENGINE::translate('NO_DATE','piccolo_blog');
			}
            $posts = PICCOLO_ENGINE::getRTmpl('piccolo_blog/blog_post', array('date'=>$date)+$post + array('id'=>$id)).$posts;
        }
        
        return $posts;
        
    }
    
    private static function showPost($id){
        if(!isset(self::$base['posts'][$id])){return PICCOLO_ENGINE::translate('BAD_POST_ID','piccolo_blog');}
        $post = self::$base['posts'][$id];
        $post['id'] = $id;
        if(!isset($post['content_full']) || $post['content_full'] == '' || $post['content_full'] == null){$post['content_full'] = $post['content'];}
        $uri = PICCOLO_ENGINE::getURI();
        if(PICCOLO_ENGINE::checkScript('breadcrumbs')){
            breadcrumbs::setTitle('/' . rtrim(implode('/', $uri), '/'),$post['title']);
        }
		
		$date = '';
		if(isset($post['date'])){
			$date = date('d.m.Y H:i');
		}else{
			$date = PICCOLO_ENGINE::translate('NO_DATE','piccolo_blog');
		}
		
        return PICCOLO_ENGINE::getRTmpl('piccolo_blog/post_page', array('date'=>$date)+$post + array('id'=>$id));
    }
    
}