<?php

    //Самая простая система прав
    final class users_permissions {
        
        //Пока что самый примитивный вариант
        public static function isPermitted($user_login, $permission, $default = null){
            if(!PICCOLO_ENGINE::checkScript('users_base')){return null;}
            return self::isPermittedById(users_base::getIdByField('login',$user_login), $permission,$default);
        }
        
        public static function isPermittedById($user_id, $permission, $default = null){
            if(!PICCOLO_ENGINE::checkScript('users_base')){return null;}
            $perms = users_base::getFieldById($user_id,'permissions');
            return is_array($perms) && isset($perms[$permission]) ? $perms[$permission] : $default;
        }
        
        //Тоже пока простой вариант
        public static function setPermission($user_login, $permission, $vaule){
            if(!PICCOLO_ENGINE::checkScript('users_base')){return false;}
            $uid = users_base::getIdByField('login',$user_login);
            return self::setPermissionById($uid, $permission, $vaule);
        }
        
        public static function setPermissionById($uid, $permission, $vaule){
            if(!PICCOLO_ENGINE::checkScript('users_base')){return false;}
            $perms = users_base::getFieldById($uid,'permissions');
            if(!is_array($perms)){$perms = array();}
            $perms[$permission] = $vaule;
            users_base::setFieldById($uid,'permissions',$perms);
            return true;
        }
        
    }