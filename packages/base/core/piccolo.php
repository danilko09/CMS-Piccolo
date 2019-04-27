<?php

//Отмечаем время начала работы системы
define('PICCOLO_START_MICROTIME', microtime(true));

//Системная информация
define('PICCOLO_CORE_BUILD', '7_RC3.2'); //Версия ядра
define('PICCOLO_WORKS', true); //Пометка, что работает именно CMS Piccolo
define('PICCOLO_SYSTEM_DEBUG', true); //Режим отладки (вкл/вкл)
define('PICCOLO_SYSTEM_PRINT_TIMINGS',true);//Печатать системные тайминги или нет
//Информация о папках
define('PICCOLO_ROOT_DIR', __DIR__); //Корневая директория, где лежит этот файл
define('PICCOLO_CMS_DIR', PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . 'piccolo'); //Папка с файлами CMS
define('PICCOLO_CMS_URL', '/piccolo'); //Внешний URI папки с файлами CMS
define('PICCOLO_CONFIGS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'config'); //Папка с конфигурационными файлами
define('PICCOLO_DATA_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'data'); //Папка с пользовательскими данными
define('PICCOLO_TEMPLATES_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'templates'); //Папка с файлами шаблонов оформления страниц
define('PICCOLO_TEMPLATES_URL', PICCOLO_CMS_URL . '/templates'); //Внешний URI папки с шаблонами
define('PICCOLO_SCRIPTS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'scripts'); //Папка с расширениями (моудялми) системы
define('PICCOLO_CLASSES_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'classpath'); //Папка с классами
define('PICCOLO_TRANSLATIONS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'locales'); //Папка с локализациями системы
//Включение вывода ошибок, если включена отладка
if (PICCOLO_SYSTEM_DEBUG) {
    error_reporting(-1);
    ini_set('display_errors', 1);
}


/**
 * Главный класс CMS Piccolo - ядро
 */
final class PICCOLO_ENGINE {

    //Инициализация
    /**
     * Входная точка в систему
     */
    public static function start($handle_request = true) {
        self::selfUpdate(); //Проверяем наличие обновлений ядра
        session_start(); //Запускаем сессию
        //Грузим конфиг
        self::$cfg_manager = new PICCOLO_ENGINE_CONFIGS_MANAGER();
        self::$config = self::loadConfig('piccolo_core');
        spl_autoload_register(__NAMESPACE__ . '\PICCOLO_ENGINE::classLoader'); //Указываем __autoload
        self::autoload(); //Проводим авто-загрузку скриптов
        if(!$handle_request){return;}//Если нас попросили не обрабатывать запрос
        if(!isset(self::$config['page_generator']) || self::$config['page_generator'] == 'default' || self::$config['page_generator'] == '' || !self::checkScript(self::$config['page_generator'])){
            self::generatePage();//Если не указан или не загружается кастмный генератор страниц
        }else{//если удалось подгрузить кастомный генератор
            $cl = self::$config['page_generator'];
            $cl::generatePage();
        }
    }

    private static function generatePage(){
        if (filter_input(INPUT_GET, 'ajax') == '1') {//Если ajax-запрос
            echo self::GetContentByTag(filter_input_array(INPUT_GET)); //Выводим данные
            exit; //Завершаем работу
        }//Если не ajax-запрос
        echo self::PrepearHTML(self::getTmpl('index')); //Обрабатываем и выводим шаблон главной страницы
        if (PICCOLO_SYSTEM_DEBUG && PICCOLO_SYSTEM_PRINT_TIMINGS) {
            self::printTimings();
        }//Выводим отладочную информацию, если включен режим отладки        
    }
    
    //classpath
    public static function classLoader($classname){
        $file = PICCOLO_CLASSES_DIR.DIRECTORY_SEPARATOR.str_replace('\\',DIRECTORY_SEPARATOR,$classname).'.php';
       if(file_exists($file)){
           include_once $file;
           return class_exists($classname);
       }
        return false;
    }
    
    //URI(L)
    private static $url = null;
    private static $uri = null;
    private static $url_base = null;
    private static $index = null;

    /**
     * Возвращает текущий URL
     */
    public static function getURL() {
        if (self::$url == null) {
            self::genURLI();
        }
        return self::$url;
    }

    /**
     * Возвращает URI
     */
    public static function getURI() {
        if (self::$uri == null) {
            self::genURLI();
        }
        return self::$uri;
    }

    /**
     * Возвращает базу URL
     */
    public static function getURL_BASE() {
        if (self::$url_base == null) {
            self::genURLI();
        }
        return self::$url_base;
    }

    /**
     * Возвращает ссылку на главную страницу
     */
    public static function getIndex() {
        if (self::$index == null) {
            self::genURLI();
        }
        return self::$index;
    }

    /**
     * Генерирует результаты работы функций, которые представлены выше
     */
    private static function genURLI() {
        //Получаем полный URL с index.php
        $f_url = 'http://' . self::getSrvInf('HTTP_HOST') . self::getSrvInf('PHP_SELF');
        $url = explode(basename(filter_input(INPUT_SERVER,'SCRIPT_NAME')), $f_url); //Бьем адрес на две части
        if(isset($url[2])){$url[1] = $url[2];}
        self::$url_base = rtrim($url[0], '/'); //Сохраняем базовый URL
        if (isset($url[1])) {//Если у нас получились две части, то создаем массив uri
            self::$uri = explode('/', rtrim($url[1], '/'));
            array_shift(self::$uri);
        } else {
            self::$uri = array('');
        }//Если же часть одна, то оставляем uri с одной пустой строкой
        $c_url = 'http://' . self::getSrvInf('HTTP_HOST') . urldecode(self::getSrvInf('REQUEST_URI')); //Получаем точный URL
        self::$url = str_replace('?' . self::getSrvInf('QUERY_STRING'),'',$c_url); //Отсекаем GET-параметры и сохраняем
        if(!isset($url[1])){$url[1] = '';}
        self::$index = $url[1] == '' ? rtrim(self::$url, '/') : rtrim(substr(self::$url, 0, strripos(self::$url, $url[1])), '/');
    }

    //Возвращает информацию из $_SERVER или, по возможности, из filter_input_array(INPUT_SERVER) 
    public static function getSrvInf($var) {
        $fin = filter_input(INPUT_SERVER, $var); //Сохраняем, чтоб не тратить время на повторный вызов
        if ($fin !== null) {
            return $fin; //Если не null - возвращаем
        }
        if (isset($_SERVER[$var])) {//Если $fin === null, но есть в $_SERVER
            return $_SERVER[$var]; //Возвращаем
        }
        return null; //Если ни там, ни там не нашли, то возвращаем null
    }

    //Скрипты
    private static $all_scripts_cache = null; //Кеш информации о всех скриптах
    private static $loaded = array(); //Список загруженных скриптов

    /**
     * Проверяет возможность использования скрипта и подгружает его, если это возможно
     * 
     * @param string $alias алиас скрипта (главный класс)
     * 
     * @return boolean Возвращает true, если скрипт успешно подгрузился; Возвращает false, если такого скрипта нет или информация о нем в кеше не позволят загрузить его
     */

    public static function checkScript($alias) {
        $info = self::getScriptInfo($alias); //Получаем информацию о скрипте
        if ($alias == '' || $alias == null || $info === false) {//Проверяем название и наличие записи в конфиге ядра
            return false;
        }
        if (isset(self::$loaded[$alias])) {//Если уже есть кеш, то выдаем его
            return (boolean) self::$loaded[$alias];
        }
        $file = isset($info['file']) ? $info['file'] : $alias; //Определяем имя файла
        if (isset($info['need_wse']) && !PICCOLO_WSE_STAFF) {
            return false;
        }//Ответ false, если требуется поддержка WSE_ENGINE, но она отключена
        if(is_file(PICCOLO_SCRIPTS_DIR . DIRECTORY_SEPARATOR . $file . '.php')){//Подключаем файл, если он указан в конфиге и существует в папке scripts
                include_once PICCOLO_SCRIPTS_DIR . DIRECTORY_SEPARATOR . $file . '.php';
        }
        $mainclass = isset($info['mainclass']) ? $info['mainclass'] : $alias;
        if (!class_exists($mainclass,true)) {//Проверяем наличие класса, если его нет, то отвечаем false; кроме этого php может вызвать PICCOLO_ENGINE::classLoader, если класс не был подгружен ранее
            return false;
        }
        	if($mainclass !== $alias && !class_exists($alias)){
			class_alias($mainclass, $alias);
		}
        if (method_exists($mainclass, 'onLoad')) {//Автозагрузка скрипта: если метод onLoad есть, то вызываем его
            self::$loaded[$alias] = $mainclass::onLoad($alias);//В качестве параметра передается алиас скрипта (мало ли потребуется узнать как обозначен модуль)
        }
        if (!isset(self::$loaded[$alias]) || self::$loaded[$alias] === null) {//Если функция ничего не вернула или нет autoload
            self::$loaded[$alias] = true; //Отмечаем скрипт как успешно загруженный
        }
        return true; //Отвечаем, что скрипт загрузился успешно
    }

    /**
     * Возвращает информацию о скрипте с алиасом $alias
     * 
     * @param string $alias Алиас скрипта (название главного класса)
     * @return mixed false - информации нет в базе данных, во всех остальных случаях - найденная информация
     */
    public static function getScriptInfo($alias) {
        $info = self::getAllScriptsInfo();
        return isset($info[$alias]) ? $info[$alias] : false;
    }

    /**
     * Возвращает массив с информацией о всех скриптах
     * Загружет кеш, если его нет или выдает кеш, если уже загрузен
     */
    public static function getAllScriptsInfo() {
        return isset(self::$config['scripts']) ? self::$config['scripts'] : array();
    }

    /**
     * Производит автозагрузку всех отмеченных для этого скриптов
     */
    private static function autoload() {
        self::$autoload = microtime(true);
        $scripts = self::getAllScriptsInfo();
        if (!is_array($scripts)) {
            return;
        }
        foreach ($scripts as $alias => $info) {
            if (!isset($info['autoload']) || $info['autoload'] != 1 || $info['autoload'] != '1') {
                continue;
            }
            if (!self::checkScript($alias)) {
                continue;
            }
            if (class_exists($alias) && method_exists($alias, 'autoload')) {
                $alias::autoload();
            }
        }
        self::$autoload = (round((microtime(true) - self::$autoload) * 1000));
    }

    //Конфигурация
    private static $config = null;
    private static $cfg_manager = null;
    
    /**
     * Загружает конфиг
     * Возвращает содержимое JSON в виде массива
     */
    public static function loadConfig($config) {
        return is_file(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $config . '.json') ? json_decode(file_get_contents(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $config . '.json'), true) : array();
    }

    /**
     * Обновляет данные в конфиге
     * Полностью перезаписывает содержимое JSON содержимым в переданном массиве (Если что-то отсутствует в массиве, то оно будет удалено из конфига)
     */
    public static function updateConfig($config, $data) {
	if (!is_dir(dirname(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $config . '.json'))) {
            mkdir(dirname(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $config . '.json'), 0777, true);
        }
        file_put_contents(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $config . '.json', json_encode($data),LOCK_EX);
    }

    public static function getConfig($config){
        return self::$cfg_manager->loadConfig($config);
    }
    
    /**
     * Проверяет существование файла с данными в БД
     * @param type $path относительный путь до файла
     */
    public static function isData($path) {
        return is_file(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json');
    }

    /**
     * Загружает данные
     * Возвращает содержимое JSON в виде массива
     */
    public static function loadData($path) {
        return is_file(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json') ? json_decode(file_get_contents(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json'), true) : array();
    }

    /**
     * Обновляет данные
     * Полностью перезаписывает содержимое JSON содержимым в переданном массиве (Если что-то отсутствует в массиве, то оно будет удалено)
     */
    public static function updateData($path, $data) {
        if (!is_dir(dirname(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json'))) {
            mkdir(dirname(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json'), 0777, true);
        }
        file_put_contents(PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . $path . '.json', json_encode($data),LOCK_EX);
    }

    //Локализации
    private static $locales_cache = null;

    /**
     * Возвращает локализированную строку $mark для скрипта $script на языке $locale
     */
    public static function translate($mark, $script = null, $locale = null) {
        if ($mark == '' || $mark == null) {
            return null;
        }
        self::loadMainLocales();
        $data = self::$locales_cache;
        if ($script !== null && file_exists(PICCOLO_TRANSLATIONS_DIR . DIRECTORY_SEPARATOR . $script . '.ini')) {
            foreach (parse_ini_file(PICCOLO_TRANSLATIONS_DIR . DIRECTORY_SEPARATOR . $script . '.ini', true) as $lang => $vars) {
                $data[$lang] = isset($data[$lang]) ? $vars + $data[$lang] : $vars;
            }
        }
        if ($locale === null) {
            $locale = isset(self::$config['main']['locale']) ? self::$config['main']['locale'] : 'ru-RU';
        }
        if (isset($data[$locale][$mark])) {
            return $data[$locale][$mark];
        }
        if (isset(self::$config['main']['def_locale']) && isset($data[self::$config['main']['def_locale']][$mark])) {
            return $data[self::$config['main']['def_locale']][$mark];
        }
        PICCOLO_ENGINE::onEvent('PICCOLO_ENGINE.translation_not_found', array('mark'=>$mark,'script'=>$script,'locale'=>$locale));
        return $mark;
    }

    private static function loadMainLocales() {
        if (self::$locales_cache == null) {
            self::$locales_cache = is_file(PICCOLO_TRANSLATIONS_DIR . DIRECTORY_SEPARATOR . 'main.ini') ? parse_ini_file(PICCOLO_TRANSLATIONS_DIR . DIRECTORY_SEPARATOR . 'main.ini', true) : array();
        }
    }

    //Шаблонизация
    private static $types;

    /**
     * Возвращает результат обработки шаблонного тега с параметрами в массиве $tag
     */
    public static function GetContentByTag($tag) {
        //Если запрашивается системная переменная
        if (isset($tag['type']) && $tag['type'] == 'script') {//Если запрашивается вызов скрипта
            //Проверка наличия скрипта
            if (!(isset($tag['name']) && isset($tag['action']) && self::checkScript($tag['name']) && method_exists($tag['name'], $tag['action'])) && PICCOLO_SYSTEM_DEBUG) {
                return 'Can\'t handle script tag "' . ($tag['name']) . '"';
            } elseif (!(isset($tag['name']) && isset($tag['action']) && self::checkScript($tag['name']) && method_exists($tag['name'], $tag['action']))) {
                return '';
            }

            //Проверка публичных методов для отображения
            if (!self::isAction($tag['name'], $tag['action']) && PICCOLO_SYSTEM_DEBUG) {
                return 'Can\'t handle script tag "' . ($tag['name']) . '". Access dined.';
            } elseif (!self::isAction($tag['name'], $tag['action'])) {
                return '';
            }

            $cl = $tag['name'];
	    $fn = $tag['action'];
            try {
                return $cl::$fn($tag);
            } catch (Exception $e) {
                return PICCOLO_SYSTEM_DEBUG ? $e->getMessage() : 'Can\'t handle script tag "' . ($tag['name']) . '". Error in code.';
            }
        }
        return self::getRegistredTypeBy($tag); //Если не удалось обработать как скриптовый тег, тогда пытаемся обработать по типу
    }

    /**
     * Пытается обработать тег по зарегистрированному обработчику
     * @param array $tag Массив со значениями параметров тега
     * @return string Возвращает либо обработанный тег, либо сообщение об ошибке 
     */
    private static function getRegistredTypeBy($tag) {
        if (!isset($tag['type']) || !isset(self::$types[$tag['type']])) {
            return PICCOLO_SYSTEM_DEBUG ? ('Can\'t handle tag' . (isset($tag['type']) ? ' type "' . $tag['type'] . '", reason: tag type not registred' : ', reason: no type')) : '';
        }
        $code = self::$types[$tag['type']];
        if (method_exists($code, 'handleTag')) {
            return $code::handleTag($tag);
        } elseif (PICCOLO_SYSTEM_DEBUG) {
            return 'Can\'t handle tag type "' . (isset($tag['type']) ? $tag['type'] : ' ') . '", reason: no method in class "' . $code . '"';
        } else {
            return '';
        }
    }

    /**
     * Функция нужна для корректной эмуляции WSE_ENGINE
     * Возвращает только сообщение о том, что функция устарела
     * @deprecated Не поддерживается с ранних версий ядра Piccolo
     */
    public static function getSystemVar() {
        return self::translate('SYSTEM_VARS_DEPRECATED');
    }

    /**
     * Проверяет разрешено ли метод вызывать как действие для тега типа script
     * 
     * @return true - разрешено; false - не разрешено
     */
    public static function isAction($script, $method) {
        if (!self::checkScript($script)) {
            return false;
        }
        $sinfo = self::getScriptInfo($script);
        if (!isset($sinfo['actions'])) {
            return false;
        }
        if (is_string($sinfo['actions']) && $sinfo['actions'] === $method) {
            return true;
        }
        if (is_array($sinfo['actions']) && in_array($method, $sinfo['actions'])) {
            return true;
        }
        return false;
    }

    /**
     * Регистрирует обработчик для определенного типа тегов
     * 
     * @param string $tag_type Значение параметра type тега шаблона
     * @param string $handler "класс::название_функции" - без скобок.
     */
    public static function RegisterTagHandler($tag_type, $handler) {
        self::$types[$tag_type] = $handler;
    }

    /**
     * Сканирует $text на наличие шаблонных тегов и вызывает обработку найденных тегов
     */
    public static function PrepearHTML($text) {
        $text = str_replace('%index%', self::getIndex(), str_replace('%base%', self::getURL_BASE(), str_replace('%url%', self::getURL(), str_replace('%tmpl_root%', '%base%' . PICCOLO_TEMPLATES_URL, $text))));
        $text_reply = '';
        preg_match_all('#<content (.*)/>#U', $text, $text_reply, PREG_SPLIT_NO_EMPTY);
        foreach ($text_reply[1] as $num => $args) {
            preg_match_all('|(.*)=["\'](.*)["\']|U', $args, $text_rep, PREG_SPLIT_NO_EMPTY);
            $tag = array();
            foreach ($text_rep[0] as $value) {
                $tag_tmp = explode('=', trim($value));
                if (isset($tag_tmp[1])) {
                    $tag[$tag_tmp[0]] = str_replace('\'', '', str_replace('"', '', $tag_tmp[1]));
                } else {
                    $tag[$tag_tmp[0]] = true;
                }
            }
            $text = str_replace($text_reply[0][$num], self::GetContentByTag($tag), $text);
        }
        $ret = str_replace('%index%', self::getIndex(), str_replace('%base%', self::getURL_BASE(), str_replace('%url%', self::getURL(), str_replace('%tmpl_root%', '%base%' . PICCOLO_TEMPLATES_URL, $text))));
        if (count($text_reply[0]) > 0) {
            $ret = self::PrepearHTML($ret);
        }
        return $ret;
    }

    /**
     * Проверяет наличие файла шаблона
     * 
     * @return boolean false - нет файла; true - есть файл
     */
    public static function isTmpl($name) {
        return is_file(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $name . '.html') || is_file(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $name . '.html');
    }

    /**
     * Возвращает содержимое файла шаблона
     * 
     * @param string $name Название файла шаблона
     * @return string Содержимое файла шаблона, либо локализированное сообщение об ошибке
     */
    public static function getTmpl($name) {
        if (is_file(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $name . '.html')) {
            return file_get_contents(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $name . '.html');
        } elseif (is_file(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $name . '.html')) {
            return file_get_contents(PICCOLO_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $name . '.html');
        } elseif (PICCOLO_SYSTEM_DEBUG) {
            PICCOLO_ENGINE::onEvent('PICCOLO_ENGINE.template_not_found', array('name'=>$name));
            return self::translate('TMPL_NOT_FOUND');
        }
    }

    /**
     * Вызывает обработку каждого элемента массива функцией getRTmpl 
     * Возвращает содержимое файла шаблона с замененными переменными в массиве $arr
     * 
     * @param string $template название шаблона
     * @param array $data массив с данными для обработки
     * @param string $index_name  название индекса при передаче на обработку; если параметр соответствует null, то индекс не передается
     * @param string $str_data_name в какую ячейку поместить данные, если это не массив
     * @param boolean $str_ret результат работы; true - строка, false - массив
     * @param $from с какого элемента массива начать обработку
     * @param $count сколько элементов массива обрабатывать
     * @return mixed В зависимости от параметра $str_ret может возвращать как строку, так и массив. (По умолчанию $str_ret = true)
     */
    public static function getMTmpl($template, $data, $data_preset, $index_name = 'index', $str_data_name = 'data', $str_ret = true, $from = null, $count = null) {

        if ($from !== null) {
            array_slice($data, $from, $count, true);
        }

        if (!self::isTmpl($template)) {
            PICCOLO_ENGINE::onEvent('PICCOLO_ENGINE.multi_template_not_found', array('name'=>$template,'index_name'=>$index_name,'str_data_name'=>$str_data_name,'preset'=>$data_preset));
            return self::translate('MULTI_TEMPLATE_NOT_FOUND');
        }
        $return = $str_ret ? '' : array();
        foreach ($data as $index => $arr) {
            if (is_string($arr)) {
                $arr = array($str_data_name => $arr);
            }
            $arr = ($index_name === null ? array() : array($index_name => $index)) + $arr + $data_preset;
            if ($str_ret) {
                $return .= self::getRTmpl($template, $arr);
            } else {
                $return[] = self::getRTmpl($template, $arr);
            }
        }
        return $return;
    }

    /**
     * Возвращает содержимое файла шаблона с замененными переменными в массиве $arr
     * Массив $arr = ['key'=>'value']
     * Содержимое файла шаблона: '[key]'
     * Результат работы функции 'value'
     */
    public static function getRTmpl($name, $arr, $preset = array()) {
        if (!self::isTmpl($name)) {
            PICCOLO_ENGINE::onEvent('PICCOLO_ENGINE.replacable_template_not_found', array('name'=>$name,'preset'=>$preset));
            if (PICCOLO_SYSTEM_DEBUG) {
                return self::translate('REPLACABLE_TEMPLATE_NOT_FOUND_DEBUG_START')
                        . $name
                        . self::translate('REPLACABLE_TEMPLATE_NOT_FOUND_DEBUG_END');
            }
            return self::translate('REPLACABLE_TEMPLATE_NOT_FOUND');
        } else {
            $return = self::getTmpl($name);
            if (!is_array($arr)) {
                return $return;
            }
            $arr = $arr + $preset;
            foreach ($arr as $code => $val) {
                if (is_array($code) || is_array($val)) {
                    continue;
                }
                $return = str_replace('[' . $code . ']', $val, $return);
            }
            return $return;
        }
    }

    //Работа с событиями

    /**
     * Функция вызывает обработку события по его "идентификатору"
     * 
     * @param string $event_str идентификатор события
     * @param mixed $data дополнительные данные для обработчиков
     */
    public static function onEvent($event_str, $data = null) {
        $scripts = self::getAllScriptsInfo();
        foreach ($scripts as $class => $info) {
            if (!isset($info['events'])) {
                continue;
            }//Если эвенты не указаны
            if (is_string($info['events']) && $info['events'] !== $event_str) {
                continue;
            }//Если в эвентах строка и та не подходит
            elseif (is_array($info['events']) && !in_array($event_str, $info['events'])) {
                continue;
            }//Если в массиве с эвентами нет нашего
            if (!method_exists($class, 'onEvent')) {
                continue;
            }//Если нет метода для обработки эвента
            $class::onEvent($event_str, $data); //Передаем на обработку
        }
    }

    //Вывод таймингов
    private static $autoload = 0;

    /*
     * Выводит отладочную информацию
     */

    public static function printTimings() {
        if (PICCOLO_SYSTEM_DEBUG) {
            echo "<!-- page generation time: " . (round((microtime(true) - PICCOLO_START_MICROTIME) * 1000)) . "ms\r\n"
            . " scripts autoload time: " . self::$autoload . "ms\r\n"
            . "memory used: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB"
            . "core build: ".PICCOLO_CORE_BUILD
            . "-->";
        }
    }

    private static function selfUpdate() {
        $date = is_file('core_update.txt') ? file_get_contents('core_update.txt') : 0;
        if (((time() - $date) < 86400) || isset($_SESSION['no_updates'])) {
            return;
        }//Если после последней проверки\обновления прошло менее 24 часов

        $_SESSION['no_updates'] = true;
        file_put_contents('core_update.txt', time());
        
        $updates_url = 'http://piccolo.tk/core/updates_beta.php?domain='.urlencode(filter_input(INPUT_SERVER,'HTTP_HOST')).'&version='.PICCOLO_CORE_BUILD.'&get='; //Адрес страницы обновлений
	if(@file_get_contents($updates_url . 'check') !== 'yes'){return;}
        $ver = @file_get_contents($updates_url . 'version'); //Получаем последнюю версию
        if ($ver === PICCOLO_CORE_BUILD || $ver === false) {//Если всё совпадает - ничего не делаем
            return;
        }//Если версия не совпадает
        $log = file_exists('core_updates.log.json') ? json_decode(file_get_contents('core_updates.log.json')) : array();
        $log[] = 'An update found! (' . PICCOLO_CORE_BUILD . '|' . $ver . ")\r\n";
        $file = file_get_contents($updates_url . 'file'); //Грузим обновление
        $log[] = "Updating file...\r\n";
        file_put_contents(__FILE__, $file); //Обновляемся
        $log[] = 'Redirect to ' . self::getURL() . "\r\n";
        file_put_contents('core_updates.log.json', json_encode($log));
        header('Location: ' . self::getURL()); //Просим повторить запрос
    }

}

class PICCOLO_ENGINE_CONFIGS_MANAGER {
    
    private $objects = array();

    public function loadConfig($cfg){
        if(!isset($this->objects[$cfg])){
            $this->objects[$cfg] = new PICCOLO_ENGINE_CONFIG_FILE(PICCOLO_CONFIGS_DIR . DIRECTORY_SEPARATOR . $cfg . '.json');
        }
        return $this->objects[$cfg];
    }
    
}

class PICCOLO_ENGINE_CONFIG_FILE {

    private $filename = null;
    private $config = null;
    private $config_time = 0;

    public function get($data_tag) {
        $this->reloadFile();
        return $this->getFromArray($this->config, $this->parseTag($data_tag));
    }

    public function set($data_tag, $data) {
        $this->reloadFile();
        $this->config = $this->setInArray($this->config, $this->parseTag($data_tag), $data);
        $this->rewriteFile();
    }

    public function insert($data_tag, $data) {
        $this->reloadFile();
        $this->config = $this->insertInArray($this->config, $this->parseTag($data_tag), $data);
        $this->rewriteFile();
    }

    public function __construct($filename) {
        $this->filename = $filename;
        $this->checkFileDir();
    }

    public function __destruct() {
        $this->rewriteFile();
    }

    private function parseTag($data_tag){
        return $data_tag === '' ? array() : explode('.', $data_tag);
    }
    
    private function checkFileDir() {
        $dir = dirname($this->filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function reloadFile() {
        clearstatcache(true, $this->filename);
        $f = is_file($this->filename);
        $time = $f ? filemtime($this->filename) : 0;
        if ($time > $this->config_time) {
            $this->config = $f ? json_decode(file_get_contents($this->filename), true) : array();
            $this->config_time = $time;
        }
    }

    private function rewriteFile() {
        file_put_contents($this->filename, json_encode($this->config), LOCK_EX);
        clearstatcache(true, $this->filename);
        $this->config_time = filemtime($this->filename);
    }

    private function getFromArray($data_array, $element_path) {
        if (count($element_path) < 1) {
            return $data_array;
        }
        $element = array_shift($element_path);
        return isset($data_array[$element]) ? $this->getFromArray($data_array[$element], $element_path) : null;
    }

    private function setInArray($data_array, $element_path, $element_data) {
        if (count($element_path) < 1) {
            return $element_data;
        }
        $element = array_shift($element_path);
        $data_array[$element] = isset($data_array[$element]) ? $this->setInArray($data_array[$element], $element_path, $element_data) : $this->setInArray(array(), $element_path, $element_data);
        return $data_array;
    }

    private function insertInArray($data_array, $element_path, $element_data) {
        if (count($element_path) < 1) {
            $data_array[] = $element_data; 
            return $data_array;
        }
        $element = array_shift($element_path);
        $data_array[$element] = isset($data_array[$element]) 
                              ? $this->insertInArray($data_array[$element], $element_path, $element_data) 
                              : $this->insertInArray(array(), $element_path, $element_data);
        return $data_array;
    }

}
