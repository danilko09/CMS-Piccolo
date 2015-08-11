<?php

    final class breadcrumbs {

        private static $cfg = null;

        public static function onLoad(){
            if(self::$cfg == null){
                self::$cfg = PICCOLO_ENGINE::loadConfig('piccolo_breadcrumbs');
            }
            WSE_ENGINE::RegisterTagHandler('breadcrumbs', 'breadcrumbs');
        }

        public static function handleTag($tag){
            $current = array();
            $uri = PICCOLO_ENGINE::getURI();
            $page = count($uri) > 0 && $uri[0] != '' ? '/' . rtrim(implode('/', $uri), '/').'/' : '/';
			foreach(self::$cfg['cache'] as $uri => $title){
                if(strpos($page, rtrim($uri,'/').'/') === 0){
                    $current[$uri] = $title;
                }
            }
            uksort($current, array("breadcrumbs", "sort"));
            return self::genHTML(isset($tag['style']) ? $tag['style'] : 'default',$current);
        }

        public static function setTitle($uri, $title){
            if(!isset(self::$cfg['cache'])){self::$cfg['cache'] = array();}
            self::$cfg['cache'][$uri] = $title;
            PICCOLO_ENGINE::updateConfig('piccolo_breadcrumbs', self::$cfg);
        }

        private static function genHTML($style,$current){
            if(!PICCOLO_ENGINE::isTmpl('piccolo_breadcrumbs/styles/' . $style) || !PICCOLO_ENGINE::isTmpl('piccolo_breadcrumbs/styles/' . $style.'_element') || !PICCOLO_ENGINE::isTmpl('piccolo_breadcrumbs/styles/' . $style.'_element_active')){
                return PICCOLO_ENGINE::translate('BAD_BREADCRUMBS_STYLE','piccolo_breadcrumbs');
            }
            $last = array_shift($current);
            $list = '';$list_arr = array_reverse($current);
            foreach($list_arr as $uri => $title){
                $list .= PICCOLO_ENGINE::getRTmpl('piccolo_breadcrumbs/styles/' . $style.'_element',array('title'=>$title,'link'=>PICCOLO_ENGINE::getIndex().$uri));
            }
            $list .= PICCOLO_ENGINE::getRTmpl('piccolo_breadcrumbs/styles/' . $style.'_element_active',array('title'=>$last));
            return PICCOLO_ENGINE::getRTmpl('piccolo_breadcrumbs/styles/' . $style,array('list'=>$list));
        }
        
        public static function sort($f, $s){
            if(strlen($f) == strlen($s)){
                return 0;
            }
            if(strlen($f) < strlen($s)){
                return 1;
            }
            return -1;
        }

    }
    