<?php

    class piccolo_blog_admin {
        
        public static function bindAdminPage(){
            $msg = '';
            if(filter_input(INPUT_POST,'piccolo_blog_add_secret') !== null){
                
                if(filter_input(INPUT_POST,'title') == null){$msg .= PICCOLO_ENGINE::translate('NO_TITLE','piccolo_blog_admin').'<br/>';}
                if(filter_input(INPUT_POST,'author') == null){$msg .= PICCOLO_ENGINE::translate('NO_AUTHOR','piccolo_blog_admin').'<br/>';}
                if(filter_input(INPUT_POST,'content') == null){$msg .= PICCOLO_ENGINE::translate('NO_CONTENT','piccolo_blog_admin').'<br/>';}
                
                if($msg === ''){
                    $blog_db = PICCOLO_ENGINE::loadConfig('piccolo_blog');
                    $blog_db['posts'][] = filter_input_array(INPUT_POST) + ['date'=>time()];
                    PICCOLO_ENGINE::updateConfig('piccolo_blog',$blog_db);
                    $msg = PICCOLO_ENGINE::translate('SUCCESS','piccolo_blog_admin');
                }
            }
            return PICCOLO_ENGINE::getRTmpl('piccolo_blog_admin/add_form',array('msg'=>$msg));
        }
        
    }
    