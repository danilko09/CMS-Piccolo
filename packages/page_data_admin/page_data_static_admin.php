<?php

    final class page_data_static_admin {

        public static function bindAdminPage($link, $params){
            self::setBreadCrumbs($link);
            if(count($params) == 0){
                return self::genListPage($link);
            }
            $act = array_shift($params);
            switch($act){
                case 'add': return self::addPage($link);
                case 'edit': return self::editPage($link, $params);
                case 'remove': return self::removePage($link, $params);
                default: return self::genListPage($link);
            }
        }

        private static function genListPage($base){
            $cdata = PICCOLO_ENGINE::loadConfig('page_data');
            $pages = isset($cdata['spages']) ? $cdata['spages'] : array();
            $list = '';
            $i = 0;
            foreach($pages as $alias => $data){
                if($alias[0] !== '/'){
                    unset($cdata['spages'][$alias]);
                    PICCOLO_ENGINE::updateConfig('page_data', $cdata);
                    continue;
                }
                $i++;
                $list .= PICCOLO_ENGINE::getRTmpl('page_data/admin/slink', array('number' => $i, 'alias' => ltrim($alias, '/')) + $data);
            }
            if($list == ''){
                $list = PICCOLO_ENGINE::translate('NO_PAGES', 'page_data_static_admin');
            }

            return PICCOLO_ENGINE::getRTmpl('page_data/admin/static_list', array('list' => $list, 'base' => $base, 'ADD_TITLE' => PICCOLO_ENGINE::translate('ADD_PAGE', 'page_data_static_admin')));
        }

        private static function addPage($base){
            $msg = '';
            if(filter_input(INPUT_POST, 'page_data_static_admin_secret') !== null){
                $msg = self::writePage();
                if($msg === ''){
                    $msg = PICCOLO_ENGINE::translate('SAVED', 'page_data_static_admin');
                }
            }
            return PICCOLO_ENGINE::getRTmpl('page_data/admin/add_form', array(
                        'msg' => $msg,
                        'base' => $base,
                        'ialias' => filter_input(INPUT_POST, 'alias'),
                        'ititle' => filter_input(INPUT_POST, 'title'),
                        'icontent' => filter_input(INPUT_POST, 'content'),
                        'ichecked' => filter_input(INPUT_POST, 'dynamic') !== null ? 'checked' : ''
            ));
        }

        
        private static function editPage($base,$params){
            $msg = ''; $alias = '/' . implode('/', $params);
            $cfg = PICCOLO_ENGINE::loadConfig('page_data');
            $page = $cfg['spages'][$alias]; unset($cfg);
            $checkbox = isset($page['parse_content']) ? $page['parse_content'] : false;
            if(filter_input(INPUT_POST, 'page_data_static_admin_secret') !== null){
                $checkbox = filter_input(INPUT_POST, 'dynamic') !== null;
                $msg = self::writePage();
                if($msg === '' && filter_input(INPUT_POST, 'alias') !== ltrim($alias,'/')){
                    $cfg = PICCOLO_ENGINE::loadConfig('page_data');
                    unset($cfg['spages'][$alias]);
                    PICCOLO_ENGINE::updateConfig('page_data',$cfg);            
                }
                if($msg === ''){ $msg = PICCOLO_ENGINE::translate('SAVED', 'page_data_static_admin'); }
            }
            $content = filter_input(INPUT_POST, 'content') !== null ? filter_input(INPUT_POST, 'content') : $page['content'];
            $icontent = preg_replace('|<content (.*)/>|U', '[content $1/]', $content);
            return PICCOLO_ENGINE::getRTmpl('page_data/admin/edit_form', array(
                        'msg' => $msg, 'base' => $base, 'alias' => $alias,
                        'ialias' => filter_input(INPUT_POST, 'alias') !== null ? filter_input(INPUT_POST, 'alias') : ltrim($alias,'/'),
                        'ititle' => filter_input(INPUT_POST, 'title') !== null ? filter_input(INPUT_POST, 'title') : $page['title'],
                        'icontent' => $icontent,
                        'ichecked' => $checkbox ? 'checked' : ''
            ));
        }
        
        private static function removePage($link, $params){
            $msg = '';
            if(filter_input(INPUT_POST, 'page_data_static_admin_secret') !== null){
                if(self::checkPass(filter_input(INPUT_POST, 'password'))){
                    $msg .= PICCOLO_ENGINE::translate('BAD_PASSWORD', 'page_data_static_admin');
                }else{
                    $cfg = PICCOLO_ENGINE::loadConfig('page_data');
                    unset($cfg['spages']['/' . implode('/', $params)]);
                    PICCOLO_ENGINE::updateConfig('page_data', $cfg);
                    $msg .= PICCOLO_ENGINE::translate('REMOVED', 'page_data_static_admin');
                }
            }
            return PICCOLO_ENGINE::getRTmpl('page_data/admin/remove_form', array('msg' => $msg, 'base' => $link, 'alias' => '/' . implode('/', $params)));
        }

        private static function setBreadCrumbs($link){
            if(!PICCOLO_ENGINE::checkScript('breadcrumbs')){
                return;
            }
            breadcrumbs::setTitle($link . '/add', PICCOLO_ENGINE::translate('ADD_PAGE', 'page_data_static_admin'));
            breadcrumbs::setTitle($link . '/remove', PICCOLO_ENGINE::translate('REMOVE_PAGE', 'page_data_static_admin'));
        }

        private static function writePage(){
            $msg = self::checkInput('pass', 'NO_PASSWORD') . self::checkInput('alias', 'NO_ALIAS')
                    . self::checkInput('title', 'NO_TITLE') . self::checkInput('content', 'NO_CONTENT');
            if($msg == '' && self::checkPass(filter_input(INPUT_POST, 'pass'))){
                $msg .= PICCOLO_ENGINE::translate('BAD_PASSWORD', 'page_data_static_admin');
            }elseif($msg == ''){
                $page = filter_input_array(INPUT_POST);
                unset($page['pass']);
                if(isset($page['dynamic'])){
                    unset($page['dynamic']);
                    $page['parse_content'] = true;
                    $page['content'] = preg_replace('|[content (.*)/]|U', '<content $1/>', $page['content']);
                }
                $cfg = PICCOLO_ENGINE::loadConfig('page_data');
                $cfg['spages']['/' . filter_input(INPUT_POST, 'alias')] = $page;
                PICCOLO_ENGINE::updateConfig('page_data', $cfg);
            }
            return $msg;
        }

        private static function checkInput($input_post, $err_msg){
            if(filter_input(INPUT_POST, $input_post) == null){
                return PICCOLO_ENGINE::translate($err_msg, 'page_data_static_admin');
            }
        }

        private static function checkPass($password){
            return users_base::getFieldById(users_base::getCurrentUserId(), 'password') !== $password;
        }

    }
    