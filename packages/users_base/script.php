<?php

//Абстрактная база данных пользователей
class users_base{
    
    //Кеш
    private static $users = array();
    
    //Инициализация скрипта
    public static function onLoad(){
        //Загружаем кеш
        self::$users = PICCOLO_ENGINE::loadConfig('users_base');
    }
    
    //Регистрация пользователя
    //В массиве могут быть переданы любые данные
    public static function register($user_data){
        self::$users[] = $user_data;
        PICCOLO_ENGINE::updateConfig('users_base', self::$users);
    }
    
    //Авторизирует пользователя по его id в БД
    //Время авторизации равно времени действия сессии
    public static function authAsId($id){
        $_SESSION['users_base']['user_id'] = $id;
    }
    
    //Возвращает id пользователя в текущей сессии
    //null, если не авторизирован
    public static function getCurrentUserId(){
        return isset($_SESSION['users_base']['user_id']) ? $_SESSION['users_base']['user_id'] : null;
    }
    
    //Снимает авторизацию в текущей сессии
    public static function logout(){
        $_SESSION['users_base']['user_id'] = null;
    }
    
    //Ищет пользователя по его учётным данным
    //Возвращает только id первого встречного или null, если ничего не найдёт
    public static function getIdByField($field,$vaule){
        foreach(self::$users as $id=>$user){
            if(isset($user[$field]) && $user[$field] == $vaule){return $id;}
        }
        return null;
    }
    
    //Возвращает все данные о пользователе по его id
    //или null, если пользователя с таким id нет
    public static function getDataById($id){
        return isset(self::$users[$id]) ? self::$users[$id] : null;
    }
    
    //Устанавливает значение поля по id пользователя
    public static function setFieldById($id,$field,$vaule){
        self::$users[$id][$field] = $vaule;
        PICCOLO_ENGINE::updateConfig('users_base', self::$users);
    }
    
    //Возвращает значение поля по id пользователя, если нет пользователя или поля, возвращает null.
    public static function getFieldById($id,$field){
        return isset(self::$users[$id]) && isset(self::$users[$id][$field]) ? self::$users[$id][$field] : null;
    }
    
    public static function getAllBase(){
        return self::$users;
    }
    
}