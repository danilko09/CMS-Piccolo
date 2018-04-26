<?php

final class piccolo_auth {

    private static $config = null;

    public static function onLoad() {
        self::$config = PICCOLO_ENGINE::loadConfig('piccolo_auth');
        if (!isset(self::$config['key'])) {
            self::regSite();
        }
        if (PICCOLO_ENGINE::checkScript('users_base')) {
            self::users_base_login(); //мало-ли сессия слетела
        }
    }

    private static function getReferer() {
        if (filter_input(INPUT_SERVER, 'HTTP_REFERER') !== null) {
            return filter_input(INPUT_SERVER, 'HTTP_REFERER');
        }
        return PICCOLO_ENGINE::getIndex();
    }

    public static function login($curback = true) {
        $ref = self::getReferer();
        if($curback){
            $ref = PICCOLO_ENGINE::getURL();
        }
        self::auth($ref);
    }

    public static function logout() {
        setcookie('piccolo_auth_accesstoken', filter_input(INPUT_GET, 'access_token'), time() + 60 * 60 * 24 * 90); //кука с токеном
        setcookie('piccolo_auth_uuid', $answer['uuid'], time() + 60 * 60 * 24); //кука с UUID на 90 дней
        users_base::logout();
        header('Location: '.self::getReferer());
    }

    public static function registration(){
        header('Location: http://auth.piccolo.tk/beta/registration' . (isset(self::$config['key']) ? '?key=' . self::$config['key'] : die('Auth not configured')));
    }
    
    public static function auth($referer) {
        if (isset($_COOKIE['piccolo_auth_accesstoken'])) {//Если кука есть - посылаем обратно, ибо нефиг авторизацию повторно запрашивать
            header('Location: ' . $referer);
            die();
        } else {
            //Сохраняем ссылку возврата и отправляем на авторизацию
            $_SESSION['piccolo_auth']['ref'] = $referer;
            header('Location: http://auth.piccolo.tk/beta/login' . (isset(self::$config['key']) ? '?key=' . self::$config['key'] : die('Auth not configured')));
        }
        echo 'Loading...';
    }

    public static function back() {
//Если есть токен, то ставим куку
        if (filter_input(INPUT_GET, 'access_token') !== null) {
            setcookie('piccolo_auth_accesstoken', filter_input(INPUT_GET, 'access_token'), time() + 60 * 60 * 24 * 90); //кука с токеном
        }
        //авторизируемся в users_base
        self::users_base_login();
//Если ссылка возврата есть - посылаем на нее, если нет - на главную
        if (isset($_SESSION['piccolo_auth']['ref'])) {
            header("Location: " . $_SESSION['piccolo_auth']['ref']);
        }
        header('Location: ' . PICCOLO_ENGINE::getIndex());
    }

    public static function getUUID() {
        if (isset($_COOKIE['piccolo_auth_uuid'])) {
            return $_COOKIE['piccolo_auth_uuid'];
        }
        if (isset($_COOKIE['piccolo_auth_accesstoken'])) {
            $answer = json_decode(file_get_contents('http://auth.piccolo.tk/beta/tokenToUUID/token/' . $_COOKIE['piccolo_auth_accesstoken']), true);
            if (isset($answer['uuid'])) {
                setcookie('piccolo_auth_uuid', $answer['uuid'], time() + 60 * 60 * 24); //кука с UUID на 90 дней
                return $answer['uuid'];
            }
        }
        return null;
    }

    public static function isAuthorised() {
        return isset($_COOKIE['piccolo_auth_accesstoken']);
    }

    private static function regSite() {
        $answer_http = file_get_contents('http://auth.piccolo.tk/beta/reg_site?return_url=' . urlencode(PICCOLO_ENGINE::getIndex() . '?ajax=1&type=script&name=piccolo_auth&action=back&access_token=[access_token]'));
        $answer = json_decode($answer_http, true);
        if ($answer['status'] === 'ok') {
            self::$config['key'] = $answer['description'];
            PICCOLO_ENGINE::updateConfig('piccolo_auth', self::$config);
            return;
        }
        var_dump($answer_http);
        die('Auth auto configuration error!');
    }

    private static function users_base_login() {
        if (PICCOLO_ENGINE::checkScript('users_base')) {
            $uid = users_base::getIdByField('piccolo_auth_uuid', self::getUUID());
            if($uid == null){
                users_base::register(array('piccolo_auth_uuid'=>self::getUUID()));
                $uid = users_base::getIdByField('piccolo_auth_uuid', self::getUUID());
            }
            users_base::authAsId($uid);
        }
    }

}
