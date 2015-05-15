<?php
//Отмечаем время начала работы системы
define('PICCOLO_START_MICROTIME', microtime(true));

//Системная информация
define('PICCOLO_CORE_BUILD', '4');//Версия ядра
define('PICCOLO_WORKS', true);//Пометка, что работает именно CMS Piccolo
define('PICCOLO_WSE_STAFF',true);//Минимальная совместимость с MSC: WebSiteEngine
define('PICCOLO_SYSTEM_DEBUG', true);//Режим отладки

//Информация о папках
define('PICCOLO_ROOT_DIR', __DIR__);//Корневая директория, где лежит этот файл
define('PICCOLO_CMS_DIR', PICCOLO_ROOT_DIR.DIRECTORY_SEPARATOR.'piccolo');//Папка с файлами CMS
define('PICCOLO_CMS_URL', '/piccolo');//Внешний URI папки с файлами CMS
define('PICCOLO_CONFIGS_DIR',PICCOLO_CMS_DIR.DIRECTORY_SEPARATOR.'config');//Папка с конфигурационными файлами
define('PICCOLO_TEMPLATES_DIR', PICCOLO_CMS_DIR.DIRECTORY_SEPARATOR.'templates');//Папка с файлами шаблонов оформления страниц
define('PICCOLO_TEMPLATES_URL', PICCOLO_CMS_URL.'/templates');//Внешний URI папки с шаблонами
define('PICCOLO_SCRIPTS_DIR', PICCOLO_CMS_DIR.DIRECTORY_SEPARATOR.'scripts');//Папка с расширениями (моудялми) системы
define('PICCOLO_TRANSLATIONS_DIR', PICCOLO_CMS_DIR.DIRECTORY_SEPARATOR.'locales');//Папка с локализациями системы

//Включение вывода ошибок
if(PICCOLO_SYSTEM_DEBUG){
    error_reporting(-1);
    ini_set('display_errors', 1);
}

//Имитация ядра WSE
if(PICCOLO_WSE_STAFF){
    
    define("WSE_START_MICROTIME", PICCOLO_START_MICROTIME);//Запоминаем время

    define("MSC_WSE_CORE_VERSION", '4');//Имитируемая версия ядра, возможно отличие в поведении
    define("WSE_INCLUDE", true);//Имитируем работу WSE
    define("WSE_DEBUG", PICCOLO_SYSTEM_DEBUG);//Определяем работает ли отладка

    //Остальные системные папки
    define("WSE_ROOT_DIR", PICCOLO_ROOT_DIR);
    define("WSE_CMS_DIR", PICCOLO_CMS_DIR);
    define("WSE_CONFIG_DIR",PICCOLO_CONFIGS_DIR);
    define("WSE_TMPL_DIR", PICCOLO_TEMPLATES_DIR);
    define("WSE_SCRIPTS_DIR", PICCOLO_SCRIPTS_DIR);
    define("WSE_TRANSLATE_DIR", PICCOLO_TRANSLATIONS_DIR);
    
    //Создаем алиас для класса ядра
    class_alias ('PICCOLO_ENGINE' , 'WSE_ENGINE');
}

//Главный класс (ядро)
final class PICCOLO_ENGINE{

    //URI(L)
    private static $url = null;
    private static $uri = null;
    private static $url_base = null;
    private static $index = null;

    /*
     * Возвращает текущий URL
     */
    public static function getURL(){
        if(self::$url == null){ self::genURLI(); }
        return self::$url;
    }

    /*
     * Возвращает URI
     */
    public static function getURI(){
        if(self::$uri == null){ self::genURLI(); }
        return self::$uri;
    }

    /*
     * Возвращает базу URL
     */
    public static function getURL_BASE(){
	if(self::$url_base == null){ self::genURLI(); }
        return self::$url_base;
    }

    /*
     * Возвращает ссылку на главную страницу
     */
    public static function getIndex(){
        if(self::$index == null){ self::genURLI(); }
        return self::$index;
    }
    
    /*
     * Генерирует результаты работы функций, которые представлены выше
     */
    private static function genURLI(){
        //Получаем полный URL
	self::$url = 'http://'.(filter_input(INPUT_SERVER, 'HTTP_HOST') == null ? $_SERVER['HTTP_HOST'] : filter_input(INPUT_SERVER, 'HTTP_HOST')).(filter_input(INPUT_SERVER, 'PHP_SELF') == null ? $_SERVER['PHP_SELF'] : filter_input(INPUT_SERVER, 'PHP_SELF'));
        //Бьем адрес на две части
        $url = explode('index.php', self::$url);
        //Сохраняем базовый URL
        self::$url_base = trim($url[0], '/');
        //Если у нас получились две части, то создаем массив uri
        if(isset($url[1])){
            self::$uri = explode('/', $url[1]);
            array_shift(self::$uri);
        }else{ self::$uri[0] = ''; }//Если же часть одна, то оставляем uri с одной пустой строкой
        //Генерируем ссылку на кглавную
        $u = 'http://'.(filter_input(INPUT_SERVER, 'HTTP_HOST') == null ? $_SERVER['HTTP_HOST'] : filter_input(INPUT_SERVER, 'HTTP_HOST'))
		.(filter_input(INPUT_SERVER, 'REQUEST_URI') == null ? $_SERVER['REQUEST_URI'] : filter_input(INPUT_SERVER, 'REQUEST_URI'));
        self::$index = $url[1] == '' ? $u : rtrim(substr($u,0,strpos($u,$url[1])),'/');
    }

    //Инициализация
    /*
     * Входная точка в систему
     */
    public static function start(){
	session_start();//Запускаем сессию
        //Грузим конфиг
	self::$config = self::loadConfig('piccolo_core');
        self::autoload();//Проводим авто-загрузку скриптов
        if(filter_input(INPUT_GET, 'ajax') == '1'){//Если ajax-запрос
            echo self::GetContentByTag(filter_input_array(INPUT_GET));//Выводим данные
            exit;//Завершаем работу
        }//Если не ajax-запрос
        echo self::PrepearHTML(self::getTmpl('index'));//Обрабатываем и выводим шаблон главной страницы
        if(PICCOLO_SYSTEM_DEBUG){self::printTimings();}//Выводим отладочную информацию, если включен режим отладки
    }

    //Скрипты
    private static $all_scripts_cache = null;//Кеш информации о всех скриптах
    private static $loaded = array();//Список загруженных скриптов
    
    /*
     * Проверяет возможность использования скрипта и подгружает его, если это возможно
     * Возвращает true, если скрипт успешно подгрузился
     * Возвращает false, если такого скрипта нет или информация о нем в кеше не позволят загрузить его
     */
    public static function checkScript($alias){
	$allInfo = self::getAllScriptsInfo();
        if($alias == '' || $alias == null || !isset($allInfo[$alias])){ return false; }
        $info = $allInfo[$alias];
        if(isset($info['need_wse']) && !PICCOLO_WSE_STAFF){return false;}//Если скрипту нужно наличие WSE, а эмуляция отключена, то отвечаем false
        if(!(isset($info['file']) || isset($info['a_file']))){ return false; }
	if(isset($info['file']) && !is_file(PICCOLO_SCRIPTS_DIR.DIRECTORY_SEPARATOR.$info['file'].'.php')){ return false; }
        elseif(isset($info['file'])){
            include_once PICCOLO_SCRIPTS_DIR.DIRECTORY_SEPARATOR.$info['file'].'.php';
            if(!class_exists($alias)){ return false; }
            if(isset(self::$loaded[$alias]) && self::$loaded[$alias] == true){return true;}
            if(method_exists($alias, 'onLoad')){$alias::onLoad();}
            self::$loaded[$alias] = true;
	}
        return true;
    }

    /*
     * Возвращает информацию о скрипте с алиасом $alias
     */
    public static function getScriptInfo($alias){
        if(self::checkScript($alias)){
            $info = self::getAllScriptsInfo();
            return $info[$alias];
        }else{ return false; }
    }

    /*
     * Возвращает массив с информацией о всех скриптах
     * Загружет кеш, если его нет или выдает кеш, если уже загрузен
     */
    public static function getAllScriptsInfo(){
	return isset(self::$config['scripts']) ? self::$config['scripts'] : array();
    }

    /*
     * Производит автозагрузку всех отмеченных для этого скриптов
     */
    private static function autoload(){
	self::$autoload = microtime(true);
        $scripts = self::getAllScriptsInfo();
        if(!is_array($scripts)){ return; }
        foreach($scripts as $alias => $info){
            if(!isset($info['autoload']) || $info['autoload'] != 1 || $info['autoload'] != '1'){ continue; }
            if(!self::checkScript($alias)){ continue; }
            include_once PICCOLO_SCRIPTS_DIR.DIRECTORY_SEPARATOR.$info['file'].'.php';
            if(class_exists($alias) && method_exists($alias, 'autoload')){ $alias::autoload(); }
        }
        self::$autoload = (round((microtime(true) - self::$autoload) * 1000));
    }

    //Конфигурация
    private static $config = null;
    
    /*
     * Загружает конфиг
     * Возвращает содержимое JSON в виде массива
     */
    public static function loadConfig($config){
        return is_file(PICCOLO_CONFIGS_DIR.DIRECTORY_SEPARATOR.$config.'.json') ? json_decode(file_get_contents(PICCOLO_CONFIGS_DIR.DIRECTORY_SEPARATOR.$config.'.json'), true) : array();
    }
    
    /*
     * Обновляет данные в конфиге
     * Полностью перезаписывает содержимое JSON содержимым в переданном массиве (Если что-то отсутствует в массиве, то оно будет удалено из конфига)
     */
    public static function updateConfig($config,$data){ file_put_contents(PICCOLO_CONFIG_DIR.DIRECTORY_SEPARATOR.$config.'.json', json_encode($data)); }
    
    //Локализации
    private static $locales_cache = null;

    /*
     * Возвращает локализированную строку $mark для скрипта $script на языке $locale
     */
    public static function translate($mark, $script = null, $locale = null){
	if($mark == '' || $mark == null){return null;}
	if(self::$locales_cache == null){
            self::$locales_cache = is_file(PICCOLO_TRANSLATIONS_DIR.DIRECTORY_SEPARATOR.'main.ini') 
                                 ? parse_ini_file(PICCOLO_TRANSLATIONS_DIR.DIRECTORY_SEPARATOR.'main.ini', true)
                                 : array();
        }
	$data = self::$locales_cache;
        if($script !== null && file_exists(PICCOLO_TRANSLATIONS_DIR.DIRECTORY_SEPARATOR.$script.'.ini')){
            foreach(parse_ini_file(PICCOLO_TRANSLATIONS_DIR.DIRECTORY_SEPARATOR.$script.'.ini', true) as $lang => $vars){
                $data[$lang] = isset($data[$lang]) ? $vars + $data[$lang] : $vars;
            }
        }
	if($locale === null){ $locale = isset(self::$config['main']['locale']) ? self::$config['main']['locale'] : 'LOCALE_NAME'; }
        if(isset($data[$locale][$mark])){ return $data[$locale][$mark]; }
        if(isset(self::$config['main']['def_locale']) && isset($data[self::$config['main']['def_locale']][$mark])){
            return $data[self::$config['main']['def_locale']][$mark];
        }
	return $mark;
    }

    //Шаблонизация
    private static $types;
    
    /*
     * Возвращает результат обработки шаблонного тега с параметрами в массиве $tag
     */
    public static function GetContentByTag($tag){
	if(isset($tag['type']) && $tag['type'] == 'system'){
            return self::getSystemVar($tag['name']);
        }elseif(isset($tag['type']) && $tag['type'] == 'script'){
            
            //Проверка наличия скрипта
            if(!(isset($tag['name']) && isset($tag['action']) && self::checkScript($tag['name']) && method_exists($tag['name'], $tag['action'])) && PICCOLO_SYSTEM_DEBUG){ return 'Can\'t handle script tag "'.($tag['name']).'"'; }
            if(!(isset($tag['name']) && isset($tag['action']) && self::checkScript($tag['name']) && method_exists($tag['name'], $tag['action']))){ return ''; }
            
            //Проверка публичных методов для отображения
            $sinfo = self::getScriptInfo($tag['name']);
            if((!isset($sinfo['actions']) || !in_array($tag['action'], $sinfo['actions'])) && PICCOLO_SYSTEM_DEBUG){return 'Can\'t handle script tag "'.($tag['name']).'". Access dined.';}
            if(!isset($sinfo['actions']) || !in_array($tag['action'], $sinfo['actions'])){return '';}
            
            $cl = $tag['name'];
            return $cl::$tag['action']($tag);
            
        }else{ return self::getRegistredTypeBy($tag); }
    }

    /*
     * Регистрирует обработчик для определенного типа тегов
     */
    public static function RegisterTagHandler($tag_type, $handler){ self::$types[$tag_type] = $handler; }

    /*
     * Возвращает системные переменные
     */
    private static function getSystemVar($var){
	if($var == 'title'){ return self::$config['main']['site_name']; }
	return $var;
    }

    /*
     * Пытается обработать тег по зарегистрированному обработчику
     */
    private static function getRegistredTypeBy($tag){
	$code = isset($tag['type']) && isset(self::$types[$tag['type']]) ? self::$types[$tag['type']] : ' ';
        if(self::checkScript($code) && method_exists($code, 'handleTag')){ return $code::handleTag($tag); }
        elseif(PICCOLO_SYSTEM_DEBUG){ return 'Can\'t handle tag type "'.(isset($tag['type']) ? $tag['type'] : ' ').'", reason: no method in class "'.$code.'"'; }
        else{ return ''; }
    }

    /*
     * Сканирует $text на наличие шаблонных тегов и вызывает обработку найденных тегов
     */
    public static function PrepearHTML($text){
	$text_reply = '';
        preg_match_all('|<content (.*)/>|U', $text, $text_reply, PREG_SPLIT_NO_EMPTY);
        foreach($text_reply[1] as $num => $args){
            $arr = explode(' ', $args);
            foreach($arr as $value){
                $tag_tmp = explode('=', $value);
                if(isset($tag_tmp[1])){ $tag[$tag_tmp[0]] = str_replace('\'', '', str_replace('"', '', $tag_tmp[1])); }
                else{ $tag[$tag_tmp[0]] = true; }
            }
            $text = str_replace($text_reply[0][$num], self::GetContentByTag($tag), $text);
            $tag = array();
        }
        $ret = str_replace('%index%',self::getIndex(),str_replace('%base%',self::getURL_BASE(),str_replace('%url%',self::getURL(),str_replace('%tmpl_root%','%base%'.PICCOLO_TEMPLATES_URL,$text))));
        if(count($text_reply[0]) > 0){ $ret = self::PrepearHTML($ret); }
        return $ret;
    }

    /*
     * Проверяет наличие файла шаблона
     * false - нет файла
     * true - есть файл
     */
    public static function isTmpl($name){
        return is_file(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.$name.'.html')
            || is_file(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$name.'.html');        
    }
    
    /*
     * Возвращает содержимое файла шаблона
     */
    public static function getTmpl($name){
        if(is_file(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.$name.'.html')){
            return file_get_contents(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.$name.'.html');
        }elseif(is_file(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$name.'.html')){
            return file_get_contents(PICCOLO_TEMPLATES_DIR.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$name.'.html');
        }elseif(PICCOLO_SYSTEM_DEBUG){ return self::translate('TMPL_NOT_FOUND'); }
    }

    /*
     * Возвращает содержимое файла шаблона с замененными переменными в массиве $arr
     * Массив $arr = ['key'=>'value']
     * Содержимое файла шаблона: '[key]'
     * Результат работы функции 'value'
     */
    public static function getRTmpl($name, $arr){
	if(!self::isTmpl($name)){
            $return = '<p>Во вроемя вывода шаблона возникла ошибка, но вам было передано следующее:</p>';
            if(!is_array($arr)){return $return;}
	    foreach($arr as $code => $val){ $return .= '<p>$code: <br/>$val</p>'; }
            return '<div>'.$return.'</div>';
        }else{
	    $return = self::getTmpl($name);
	    if(!is_array($arr)){return $return;}
	    foreach($arr as $code => $val){ $return = str_replace('['.$code.']', $val, $return); }
            return $return;
        }
    }
    
    //Вывод таймингов
    private static $autoload = 0;
    
    /*
     * Выводит отладочную информацию
     */
    private static function printTimings(){
        if(PICCOLO_SYSTEM_DEBUG){
            echo '<!-- page generation time: '.(round((microtime(true) - PICCOLO_START_MICROTIME) * 1000)).'ms\r\n'
                .' scripts autoload time: '.self::$autoload.'ms\r\n'
		.'memory used: '.round(memory_get_usage()/1024/1024, 2).' MB'
                .'-->';
        }
    }

}