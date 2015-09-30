<?php

//Отладка
    define('DEBUG', true);

    //Информация о папках
    define('PICCOLO_ROOT_DIR', __DIR__); //Корневая директория, где лежит этот файл
    define('PICCOLO_CMS_DIR', PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . 'piccolo'); //Папка с файлами CMS
    define('PICCOLO_CONFIGS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'config'); //Папка с конфигурационными файлами
    define('PICCOLO_TEMPLATES_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'templates'); //Папка с файлами шаблонов оформления страниц
    define('PICCOLO_SCRIPTS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'scripts'); //Папка с расширениями (моудялми) системы
    define('PICCOLO_TRANSLATIONS_DIR', PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'locales'); //Папка с локализациями системы


    if(DEBUG){
        ini_set('error_reporting', E_ALL);
        ini_set('display_startup_errors', 1);
        ini_set("display_errors", 1);
        error_reporting(E_ALL);
    }

//Сессия
    session_start();
    if(!isset($_SESSION['stage'])){
        $_SESSION['stage'] = 0;
    }
    if(!isset($_SESSION['repo'])){
        $_SESSION['repo'] = 0;
    }
    if(!isset($_SESSION['packs'])){
        $_SESSION['packs'] = array();
    }
    if(!isset($_SESSION['instd'])){
        $_SESSION['instd'] = array();
    }
//Определение списка репозиториев
    $repos = array('http://piccolo.tk/repo.php', 'http://test.piccolo.tk/repo.php');

//Обработка запроса
    if(filter_input(INPUT_GET, 'ajax') != null){
        switch(filter_input(INPUT_GET, 'ajax')){
            case 'progress':
                echo genProgress();
                break;
            case 'process':
                echo process();
                break;

            case 'main_data':
                echo getStage();
                break;

            case 'setRepo':
                if(filter_input(INPUT_POST, 'repo') != null){
                    $_SESSION['repo'] = filter_input(INPUT_POST, 'repo');
                    $_SESSION['packs'] = array();
                }
                break;

            case 'setPack':
                $_SESSION['packs'][filter_input(INPUT_POST, 'id')] = filter_input(INPUT_POST, 'checked');
                break;

            case 'startProc':
                proc();
                break;

            case 'nextStep':
                $_SESSION['stage'] = $_SESSION['stage'] + 1;
                break;

            case 'prevStep':
                $_SESSION['stage'] = $_SESSION['stage'] - 1;
                break;

            case 'reset':
                header("Location: " . filter_input(INPUT_SERVER, 'SCRIPT_NAME'));
                session_destroy();
                session_write_close();
                break;
        }
        exit;
    }else{
        echo getTmpl('main', array(
            'please_wait' => getTranslation('please_wait'),
            'location' => filter_input(INPUT_SERVER, 'SCRIPT_NAME')
        ));
    }

    function genProgress(){//Генерирует шапку по шаблонам
        $stages = array('Приветствие', 'Выбор репозитория', 'Выбор пакетов', 'Установка', 'Завершение');

        $data = "";
        foreach($stages as $num => $name){
            $data .= $num == $_SESSION['stage'] ? getTmpl('step-active', array('num' => $num + 1, 'name' => $name))
                        : getTmpl('step', array('num' => $num + 1, 'name' => $name));
        }

        return getTmpl('stepwizard-frame', array('data' => $data));
    }

    function getStage(){

        global $repos;
        $return = "";

        switch($_SESSION['stage']){
            case 0:
                $return = getTmpl('stage1');
                break;
            case 1:
                $r = "";
                foreach($repos as $id => $address){
                    $r .= $id == $_SESSION['repo'] ? getTmpl('repo_selected', array('id' => $id, 'address' => $address))
                                : getTmpl('repo_select', array('id' => $id, 'address' => $address));
                }
                $return = getTmpl('stage2', array('repos' => $r, 'location' => filter_input(INPUT_SERVER, 'SCRIPT_NAME')));
                break;
            case 2:
                if(!isset($_SESSION['repo'])){
                    $data = getTmpl('msg-danger', array('data' => getTranslation('no_repo_selected')));
                }else{
                    $repo = $repos[$_SESSION['repo']];
                    $client = new piccolo_package_downloader($repo, __DIR__ . DIRECTORY_SEPARATOR . 'package');
                    $index = $client->getIndex();
                    if(is_array($index)){
                        $data = "";
                        foreach($index as $alias => $info){
                            $data .= isset($_SESSION['packs'][$alias]) && $_SESSION['packs'][$alias] == "true"
                                        ? getTmpl('pack_selected', array('id' => $alias, 'title' => '['.$alias.']'.$info['title']))
                                        : getTmpl('pack_select', array('alias' => $alias, 'title' => '['.$alias.']'.$info['title']));
                        }
                    }else{
                        $data = getTmpl('msg-danger', array('data' => getTranslation('bad_repo')));
                    }
                }
                $return = getTmpl('stage3', array('data' => $data, 'location' => filter_input(INPUT_SERVER, 'SCRIPT_NAME')));
                break;
            case 3:
                $return = getTmpl('stage4', array('location' => filter_input(INPUT_SERVER, 'SCRIPT_NAME')));
                break;
            case 4:
                $return = getTmpl('stage5', array('location' => '"' . filter_input(INPUT_SERVER, 'PHP_SELF') . '"', 'log' => $_SESSION['log']));
                break;
        }
        return $return;
    }

//Отображение хода установки
    function process(){

        $return = "";

        if(!isset($_SESSION['log'])){
            $_SESSION['log'] = "";
        }

        $c_all = isset($_SESSION['packs']) ? count($_SESSION['packs']) : 0;
        $c_now = isset($_SESSION['instd']) ? count($_SESSION['instd']) : 0;
        $prc = ($c_now != 0) ? round(($c_now / $c_all) * 100) : 0;
        $return .= getTmpl('progress-bar', array('prc' => $prc,'log'=>$_SESSION['log']));

        if(isset($_SESSION['wait']) && $_SESSION['wait'] == false){
;
        }elseif(!isset($_SESSION['start'])){
            $_SESSION['start'] = 1; //Отмечаем, что установка начата, лог выведен
            session_write_close();
            instlog("Начало установки<br>");
            $return .= '<script>$.get("' . filter_input(INPUT_SERVER, 'SCRIPT_NAME') . '?ajax=startProc");</script>';
        }elseif(!isset($_SESSION['wait'])){
            $return .= '<script>$.get("' . filter_input(INPUT_SERVER, 'SCRIPT_NAME') . '?ajax=startProc");</script>';
        }
        return $return;
    }

    function proc(){

        global $repos;

        if(!isset($_SESSION['wait']) || !$_SESSION['wait']){
            $_SESSION['wait'] = true; //Выставляем флаг, что один поток уже запущен
            $repo = $repos[$_SESSION['repo']];
            instlog("Начало процесса проверки зависимостей...<br><br>");

            set_time_limit(0); //Убираем ограничение на время работы


            $client = new piccolo_package_downloader($repo, __DIR__ . DIRECTORY_SEPARATOR . 'package');

            foreach($_SESSION['packs'] as $pack => $v){
                if($v != "true"){
                    continue;
                }
                instlog("Проверка зависимостей пакета " . $pack . " ...<br>");
                $req = checkReq($pack, $client);
                instlog("Добавлено пакет(ов): " . $req . "<br>");
            }
            session_start();
            $_SESSION['start'] = 2; //Отмечаем, что проверка зависимостей пройдена
            instlog("Процесс проверки зависимостей окончен, будут установлены следующие пакеты: " . implode(", ", array_keys($_SESSION['packs'])));

            instlog("<br>Начало процесса установки пакетов...<br>");

            $installer = new piccolo_package_installer(
                    PICCOLO_CONFIGS_DIR, PICCOLO_TEMPLATES_DIR, PICCOLO_SCRIPTS_DIR, PICCOLO_TRANSLATIONS_DIR, __DIR__ . DIRECTORY_SEPARATOR . 'package', 'package.json'
            );



            foreach($_SESSION['packs'] as $pack => $v){
                @session_start();
                if(array_key_exists($pack, $_SESSION['instd'])){
                    continue;
                }
                instlog("Загрузка пакета " . $pack . " ...");
                $client->downloadPack($pack);
                instlog("Процесс загрузки пакета " . $pack . " завершён.<br>");
                instlog("Установка пакета " . $pack . " ...");
                $installer->clearLog();
                $installer->fullInstall();
                instlog(str_replace('\r\n', '<br/>', $installer->getLog()));
                session_start();
                $_SESSION['instd'][$pack] = "true";
                instlog("Процесс установки пакета " . $pack . " завершён.<br>");
            }

            instlog("<br>Все пакеты загружены, переход на следующий этап (завершение) через 10 секунд...<br/>Ход установки тоже будет отображен на следующем этапе.");
            sleep(10);
            instlog("Установка завершена. Если у вас на странице всё ещё отображается этап установки - обновите страницу для продолжения.");
            session_start();
            $_SESSION['stage'] ++;
            $_SESSION['wait'] = false; //Снимаем флаг потока
            exit;
        }
    }

    function checkReq($pack,$client){
        @session_start();
        if(!isset($_SESSION['packs']) || !is_array($_SESSION['packs'])){$_SESSION['packs'] = array();}
        $c = checkReqRec($pack, $client);//Чекаем зависимости;возможен снос сессии
        @session_start();//Запускаем сессию, мало ли закрыли функцией выше
        if(!array_key_exists($pack, $_SESSION['packs'])){//Если наш пакет ещё не добавлен в список для установки
            $_SESSION['packs'] = $_SESSION['packs'] + array($pack => "true");//Добавляем пакет в конец массива, чтоб наверняка в конец складываем
        }else{//Если наша зависимость уже в списке
            unset($_SESSION['packs'][$pack]);//Удаляем
            $_SESSION['packs'] = $_SESSION['packs'] + array($pack => "true");//Добавляем в начало списка, сложением чтоб наверняка попасть в начало
        }
        return $c;
    }
    
//Рекурсивная проверка зависимостей
    function checkReqRec($pack, $client){
        @session_start();//Запускаем сессию, так как она нам будет нужна
        if(!isset($_SESSION['packs']) || !is_array($_SESSION['packs'])){$_SESSION['packs'] = array();}
        $req = $client->getRequires($pack);//Получаем зависимости нашего пакета
        $i = 0;//Обнуляем счетчик
        foreach($req as $p){//Перебираем зависимости
            $i = $i + checkReqRec($p, $client);//Проверяем зависимости нашей зависимости;функция может сбросить сессию
            @session_start();//Запускаем сессию, мало ли закрыли функцией выше
            if(!array_key_exists($p, $_SESSION['packs'])){//Если наша зависимость ещё не добавлена в список для установки
                $i++;//Увеличиваем счетчик на 1
                $_SESSION['packs'] = $_SESSION['packs'] + array($p => "true");//Добавляем зависимость в конец массива, чтоб наверняка в конец складываем
                instlog("Добавлен пакет " . $p);//Добавляем в лог инфу о том, что добавили новый пакет; функция сбрасывает сессию
            }else{//Если наша зависимость уже в списке
                unset($_SESSION['packs'][$p]);//Удаляем
                $_SESSION['packs'] = array($p => "true") + $_SESSION['packs'];//Добавляем в начало списка, сложением чтоб наверняка попасть в начало
            }
        }
        return (int) $i;//Возвращаем количество добавленных пакетов
    }

//Добавляет сообщение в лог установки
    function instlog($msg){
        @session_start();
        $_SESSION['log'] .= $msg . "<br>";
        session_write_close();
    }

//Портативная система локализаций
    function getTranslation($name){
        //Массив с переведенными строками
        $trans = array(
            'please_wait' => 'Пожалуйста, подождите...',
            'no_repo_selected' => 'Не выбран источник пакетов. Пожалуйста, вернитесь на этап выбора репозитория.',
            'bad_repo' => 'Не удалось получить информацию из репозитория'
        );
        return isset($trans[$name]) ? $trans[$name] : $name;
    }

//Портативный шаблонизатор
    function getTmpl($name, $array = null){
        //Массив с шаблонами
        $templates = array(
            'msg-danger' => '<script>msg("%data%","danger");</script>',
            'main' => <<<MAIN
<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Install...</title>
        <style>
        * {
                margin: 0;
                padding: 0;
                border: 0;
                outline: 0;
                font-size: 100%;
                line-height: 1.5em;
                text-decoration: none;
                vertical-align: baseline;
        }
        body {
                font-family: "Helvetica";
                padding-top: 10px;
                /*background: #f5f5f5;*/
        }
        .text-center {
                text-align: center;
        }
        .center {
                display: block;
                margin: 0 auto;
        }
        .block {
                cursor: context-menu;
                user-select: none;
                -ms-user-select: none;
                -moz-user-select: none;
                -webkit-user-select: none;
        }
        .wrapper {
                padding: 5px;
                margin: 0 auto;
                width: 800px;
                background: #ffffff;
        }
        @media screen and (min-width:100px) and (max-width:850px) {
                .wrapper {
                        width: 100%;
                }
        }
        .install {
                /*border-left: 2px solid #c1c1c1;
                border-right: 2px solid #c1c1c1;
                border-bottom: 2px solid #c1c1c1;*/
                padding: 5px;
        }
        .install ul {
                margin-left: 20px;
        }
        .msg {
                background: #fefefe;
                color: #666666;
                font-weight: bold;
                font-size: small;
                padding: 12px;
                padding-left: 16px;
                border-top: solid 3px #CCCCCC;
                margin-bottom: 10px;
                -webkit-box-shadow: 0 10px 10px -5px rgba(0,0,0,.08);
                -moz-box-shadow: 0 10px 10px -5px rgba(0,0,0,.08);
                box-shadow: 0 10px 10px -5px rgba(0,0,0,.08);
        }
        .msg button {
                float: right;
                background: rgba(238, 238, 238, 0);
                cursor: pointer;
        }
        .msg-clear {
            border-color: #fefefe;
                -webkit-box-shadow: 0 7px 10px -5px rgba(0,0,0,.15);
                -moz-box-shadow: 0 7px 10px -5px rgba(0,0,0,.15);
                box-shadow: 0 7px 10px -5px rgba(0,0,0,.15);
        }
        .msg-info {
                border-color: #b8dbf2;
        }
        .msg-success {
                border-color: #cef2b8;
        }
        .msg-warning {
                border-color: rgba(255,165,0,.5);
        }
        .msg-danger {
                border-color: #ec8282;
        }
        .msg-primary {
                border-color: #9ca6f1;
        }
        .msg-magick {
                border-color: #e0b8f2;
        }
        .stepwizard-step p {
                margin-top: 10px;    
        }
        .stepwizard-row {
                display: table-row;
        }
        .stepwizard {
                margin-bottom: 10px;
                display: table;     
                width: 100%;
                position: relative;
        }
        .stepwizard-step button[disabled] {
                opacity: 1 !important;
                filter: alpha(opacity=100) !important;
        }
        .stepwizard-row:before {
                top: 14px;
                bottom: 0;
                position: absolute;
                content: " ";
                width: 100%;
                height: 1px;
                background-color: #ccc;
                z-order: 0;
        }
        .stepwizard-step {    
                display: table-cell;
                text-align: center;
                position: relative;
        }
        .btn-circle {
                width: 30px;
                height: 30px;
                text-align: center;
                padding: 6px 0;
                font-size: 12px;
                line-height: 1.428571429;
                border-radius: 15px;
        }
        .btn-active {
                color: #fff;
                background: #3b64d1;
        }
        .btn-next {
                cursor: pointer;
                padding: 5px;
                color: #fff;
                background: #2773cb;
                transition: 1s;
        }
        .dis {
                pointer-events: none;
                cursor: not-allowed;
                box-shadow: none;
                opacity: .65;
        }
        .btn-next:hover {
                transition: 1s;
                background: #144a89;
        }
        progress {
                width: 100%;
                margin-bottom: 10px;
                display: block;
                -webkit-appearance: none;
                border: none;
        }
        progress::-webkit-progress-bar {
                background: #eee;
        }
        progress::-webkit-progress-value {
                background: #2a4e9d;
        }
        progress::-moz-progress-bar {
                background: #eee;
        }
        progress::-moz-progress-value {
                background: #2a4e9d;
        }
        progress::-ms-progress-bar 
                background: #eee;
        }
        progress::-ms-progress-value {
                background: #2a4e9d;
}
                </style>
	</head>
	<body>
		<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAAB4CAYAAAAE9le0AAAACXBIWXMAAAsTAAALEwEAmpwYAAABNmlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjarY6xSsNQFEDPi6LiUCsEcXB4kygotupgxqQtRRCs1SHJ1qShSmkSXl7VfoSjWwcXd7/AyVFwUPwC/0Bx6uAQIYODCJ7p3MPlcsGo2HWnYZRhEGvVbjrS9Xw5+8QMUwDQCbPUbrUOAOIkjvjB5ysC4HnTrjsN/sZ8mCoNTIDtbpSFICpA/0KnGsQYMIN+qkHcAaY6addAPAClXu4vQCnI/Q0oKdfzQXwAZs/1fDDmADPIfQUwdXSpAWpJOlJnvVMtq5ZlSbubBJE8HmU6GmRyPw4TlSaqo6MukP8HwGK+2G46cq1qWXvr/DOu58vc3o8QgFh6LFpBOFTn3yqMnd/n4sZ4GQ5vYXpStN0ruNmAheuirVahvAX34y/Axk/96FpPYgAAO3dpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+Cjx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDE0IDc5LjE1Njc5NywgMjAxNC8wOC8yMC0wOTo1MzowMiAgICAgICAgIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAgICAgICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICAgICAgICAgIHhtbG5zOnRpZmY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vdGlmZi8xLjAvIgogICAgICAgICAgICB4bWxuczpleGlmPSJodHRwOi8vbnMuYWRvYmUuY29tL2V4aWYvMS4wLyI+CiAgICAgICAgIDx4bXA6Q3JlYXRvclRvb2w+QWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKFdpbmRvd3MpPC94bXA6Q3JlYXRvclRvb2w+CiAgICAgICAgIDx4bXA6Q3JlYXRlRGF0ZT4yMDE1LTA0LTA0VDIyOjUxOjUwKzExOjAwPC94bXA6Q3JlYXRlRGF0ZT4KICAgICAgICAgPHhtcDpNZXRhZGF0YURhdGU+MjAxNS0wNC0wNFQyMjo1MTo1MCsxMTowMDwveG1wOk1ldGFkYXRhRGF0ZT4KICAgICAgICAgPHhtcDpNb2RpZnlEYXRlPjIwMTUtMDQtMDRUMjI6NTE6NTArMTE6MDA8L3htcDpNb2RpZnlEYXRlPgogICAgICAgICA8eG1wTU06SW5zdGFuY2VJRD54bXAuaWlkOjUzN2Q0MmUwLWRiNGItZjM0MS1hOGI1LTFlNGJmZWM0YzEyMDwveG1wTU06SW5zdGFuY2VJRD4KICAgICAgICAgPHhtcE1NOkRvY3VtZW50SUQ+YWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOmZiMTJmOTFkLWRhYzAtMTFlNC1hYTU5LWZjNGJlN2Y1YzI1OTwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjg1YjRkNmFjLTA5MTMtMmE0OC1hOTA1LWQ5ZjY5OGM5NzUwYzwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDo4NWI0ZDZhYy0wOTEzLTJhNDgtYTkwNS1kOWY2OThjOTc1MGM8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDQtMDRUMjI6NTE6NTArMTE6MDA8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChXaW5kb3dzKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPnNhdmVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDppbnN0YW5jZUlEPnhtcC5paWQ6NTM3ZDQyZTAtZGI0Yi1mMzQxLWE4YjUtMWU0YmZlYzRjMTIwPC9zdEV2dDppbnN0YW5jZUlEPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6d2hlbj4yMDE1LTA0LTA0VDIyOjUxOjUwKzExOjAwPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoV2luZG93cyk8L3N0RXZ0OnNvZnR3YXJlQWdlbnQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpjaGFuZ2VkPi88L3N0RXZ0OmNoYW5nZWQ+CiAgICAgICAgICAgICAgIDwvcmRmOmxpPgogICAgICAgICAgICA8L3JkZjpTZXE+CiAgICAgICAgIDwveG1wTU06SGlzdG9yeT4KICAgICAgICAgPHBob3Rvc2hvcDpUZXh0TGF5ZXJzPgogICAgICAgICAgICA8cmRmOkJhZz4KICAgICAgICAgICAgICAgPHJkZjpsaSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSI+CiAgICAgICAgICAgICAgICAgIDxwaG90b3Nob3A6TGF5ZXJOYW1lPlBpY2NvbG88L3Bob3Rvc2hvcDpMYXllck5hbWU+CiAgICAgICAgICAgICAgICAgIDxwaG90b3Nob3A6TGF5ZXJUZXh0PlBpY2NvbG88L3Bob3Rvc2hvcDpMYXllclRleHQ+CiAgICAgICAgICAgICAgIDwvcmRmOmxpPgogICAgICAgICAgICA8L3JkZjpCYWc+CiAgICAgICAgIDwvcGhvdG9zaG9wOlRleHRMYXllcnM+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDxwaG90b3Nob3A6SUNDUHJvZmlsZT5BZG9iZSBSR0IgKDE5OTgpPC9waG90b3Nob3A6SUNDUHJvZmlsZT4KICAgICAgICAgPGRjOmZvcm1hdD5pbWFnZS9wbmc8L2RjOmZvcm1hdD4KICAgICAgICAgPHRpZmY6T3JpZW50YXRpb24+MTwvdGlmZjpPcmllbnRhdGlvbj4KICAgICAgICAgPHRpZmY6WFJlc29sdXRpb24+NzIwMDAwLzEwMDAwPC90aWZmOlhSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpZUmVzb2x1dGlvbj43MjAwMDAvMTAwMDA8L3RpZmY6WVJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOlJlc29sdXRpb25Vbml0PjI8L3RpZmY6UmVzb2x1dGlvblVuaXQ+CiAgICAgICAgIDxleGlmOkNvbG9yU3BhY2U+NjU1MzU8L2V4aWY6Q29sb3JTcGFjZT4KICAgICAgICAgPGV4aWY6UGl4ZWxYRGltZW5zaW9uPjEwMDwvZXhpZjpQaXhlbFhEaW1lbnNpb24+CiAgICAgICAgIDxleGlmOlBpeGVsWURpbWVuc2lvbj4xMjA8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAKPD94cGFja2V0IGVuZD0idyI/Ptz4RDcAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOgAAFIIAAEVWAAAOpcAABdv11ofkAAAC5ZJREFUeNrsnX+MHGUZxz/PzO4tFBCuYiQhCp4mmthEyrVqmgqFtkoJ0KP2SqKgoNAIxPiD6FUCCAHj9Q8MCSK0YJAIAi3F8rPCtRRDLUiuiJB4XYGaokEt5rZnsb3t3s7rH/PO8TLM7M7uzu7t7b1PMrnbd9535t33O+/zfb/P+76zopQiic2ZM4c0LJfL4TgOSe87DewI4Gbg48BjwG1RmYaHhxNdLIO1RuxI4Elgkf78JeArwJXAy/Vc0LFtWrcdDWw1wAhsATAEnG4BaZ1lgc268aPseOBZ4AwLSPPtGOAZYHGCvI8Cp1hAmkvgjwALa3Br9+tyFpAmEPiWOtzQp4BvW0BaQ+BJ7QILSOsIPIm9ZAFpPYHH2XbgKgtI6wk8ynYCy4EDFpDmEPhzwHnAqdqF/QyIiwE9o8sfqOXGNnQSTeBPRXDGHcAVIQCeBx7X+bNG+gtAH3DYCsPmEPjrwOUxvWE7cG2oFy0OeoZSqqZAqgUkGYE/XKXsWmAMeAU4CzgYnPA8j3K5bF1WygT+VoJrfBfYBhwMeoSI1FwR20OSKfBPhxMCV6SPS4Fvhgj8uBCvWEBSVOB9wAkx584TkfXAQqXUrZ7ngT8v8pIe9p5kAUlfgX8IP0j4wVD6ShF5BBDtos4XkRtFZJOIfExE5mWz2aVdXV0WkCYo8EXAn4FvAR9RSq0WkY2hPEeJyDVKqaNc10VE9haLxceK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" class="center" alt="Логотип">
		<div id="top_progress" class="stepwizard">
			
		</div>
		<div class="wrapper">
			<div id="alerts"></div>
			<div id="install" class="install">
                                <center><h1>%please_wait%</h1></center>
                        </div>
		</div>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="jquery.js"></script>
                <script>
                    setInterval("reload();",30000);
                    reload();
                    function reload(){
                        update_main_data();
                        update_top_progress();
                    }
                    function update_top_progress(){
                        $("#top_progress").load("%location%?ajax=progress");
                    }
                    function update_main_data(){
                        $("#install").html('<center><h1>%please_wait%</h1></center>');
                        $("#install").load("%location%?ajax=main_data");
                    }
                    function nextStep(){
                        $.ajax({
                            url: '%location%?ajax=nextStep'
                          });
                          reload();
                    }
                    function prevStep(){
                        $.ajax({
                            url: '%location%?ajax=prevStep'
                          });
                          reload();
                    }
        
                    $(document).ready(function(){
                            $('#alerts').on('click','button',function(){
                                    $(this).parent().remove();
                            });
                    });
                    function msg (data,color) {
                                    $('#alerts').append('<div class="msg msg-'+color+'"><button>&times;</button>'+data+'</div>');
                    };
                </script>
	</body>
</html>
MAIN
            ,
            'stage1' => <<<STAGE1
                        <form>
                        <h1 class="text-center">Вас приветствует мастер установки cms Piccolo</h1>
                        <p>Возможности cms</p>
                        <ul>
                                <li>Поддержка локализаций ядром</li>
                                <li>Файловая база данных</li>
                                <li>Редактор CodeMirror 4.12</li>
                                <li>Интеграция с лаунчером MCWL</li>
                                <li>Модульность</li>
                                <li>AJAX
                                        <ul>
                                                <li>Регистрация</li>
                                                <li>Авторизация</li>
                                        </ul>
                                </li>
                        </ul>
                        <br/>
                        <b>Если во время установки вам покажется, что страница зависла - обновите её.</b><br/>
                        <br/>
                        <button type="button" id="next" class="btn-next block" name="next" onclick="nextStep();return false;">Продолжить</button>
                        </form>
STAGE1
            , 'stage2' => <<<STAGE2
    <form id="install">
        <h1 class="text-center">Выбор репозитория</h1>
        %repos%
        <button type="button" id="prev" class="btn-next block" name="prev" onclick="prevStep();return false;">Назад</button>
        <button type="submit" id="next" class="btn-next block dis" onclick="nextStep(); return false;" name="next">Продолжить</button>
    </form>
    <script>
    	$('#install').on('click','input:radio',function(){
            $.ajax({
                url: '%location%?ajax=setRepo',
                type: 'POST',
                data: 'repo='+$("#install input[name='repo']:radio:checked").val()
            });
        $("#next").removeClass('dis');
	});
    </script>
    
STAGE2
            , 'repo_select' => '<label><input type="radio" value="%id%" name="repo">%address%</label><br>'
            , 'repo_selected' => '<label><input checked="true" type="radio" value="%id%" name="repo">%address%</label><br><script>$("#next").removeClass("dis")</script>'
            , 'stage3' => <<<STAGE3
   <form> 
        <h1 class="text-center">Выбор пакетов</h1>
        %data%<br>
        
        <button type="button" id="prev" class="btn-next block" name="prev" onclick="prevStep();return false;">Назад</button>
        <button type="submit" id="next" class="btn-next block" name="next" onclick="nextStep();return false;">Продолжить</button>
<script>
    $('input:checkbox').change(function(){
        $.post("%location%?ajax=setPack", { id: this.id, checked: this.checked });
                            });
</script>
</form>	
STAGE3
            , 'pack_select' => '<label><input type="checkbox"  id="%alias%"> %title%</label><br>'
            , 'pack_selected' => '<label><input checked="true" type="checkbox"  id="%alias%"> %title%</label><br>'
            , 'stage4' => <<<STAGE4
    <form id="install">
        <div id='proc'>
        <script>setInterval("$('#proc').load('%location%?ajax=process');",1000);</script>
        </div>
    </form>
STAGE4
            , 'stage5' => <<<STAGE5
    <h1 class="text-center">Вы успешно установили Piccolo cms</h1>
    <p>Благодорим вас за установку Piccolo cms. <br> <b>Не забудьте удалить файл %location%</b></p>
    <a href="" class="btn-next">Перейти на сайт</a><br/><br/>
    <h3 class="text-center">Ход установки</h3>
        %log%
STAGE5
            ,
            'stepwizard-frame' => '<div class="stepwizard-row">%data%</div>',
            'step' => '<div class="stepwizard-step"> <button type="button" class="btn-circle">%num%</button> <p>%name%</p> </div>',
            'step-active' => '<div class="stepwizard-step"> <button type="button" class="btn-circle btn-active">%num%</button> <p>%name%</p> </div>',
            'progress-bar' => '<h1 class="text-center">Установка...</h1>	
   <progress id="value_text" max="100" value="%prc%">Установлено на <span class="value_text">%prc%</span>%</span></progress>
					<p class="text-center"><span class="value_text">%prc%</span>%</p>
                                        <div id="log_scroll" style="height: 300px; overflow: auto;">%log%</div>
                                        <script>document.getElementById("log_scroll").scrollTop = 9999;</script>
'
        );

        if(!isset($templates[$name])){
            return "";
        }

        $return = $templates[$name];
        if(is_array($array)){
            foreach($array as $key => $val){
                $return = str_replace('%' . $key . '%', $val, $return);
            }
        }
        return $return;
    }

//Клиент для скачивания файлов с репозитория
    class piccolo_package_downloader {

        private $repo, $distance;

        public function __construct($repo, $distance){
            $this->repo = $repo;
            $this->distance = $distance;
        }

        /*
         * Возвращает массив со списком пакетов в текущем репозитории
         */

        public function getIndex(){
            return $this->getJsonData("?action=index");
        }

        public function downloadPack($pack){
            foreach($this->getFiles($pack) as $id => $path){
                $file = $this->getFile($pack, $id);
                $dir = dirname($this->distance . DIRECTORY_SEPARATOR . $path);
                if(!is_dir($dir)){
                    mkdir($dir, 0777, true);
                }
                file_put_contents($this->distance . DIRECTORY_SEPARATOR . $path, $file);
            }
        }

        /*
         * Возвращает список зависимостей пакета $package версии $version в текущем репозитории
         */

        public function getRequires($package, $version = null){
            return $version == null ? $this->getJsonData("?action=requires&package=" . $package) : $this->getJsonData("?action=requires&package=" . $package . "&version=" . $version);
        }

        private function getFiles($pack){
            return $this->getJsonData("?action=files&package=" . $pack);
        }

        private function getFile($pack, $file_name){
            $md5 = file_get_contents($this->repo . "?action=md5&package=" . $pack . '&file=' . $file_name);
            $file = file_get_contents($this->repo . "?action=file&package=" . $pack . '&file=' . $file_name);
            $c = 10;
            while($md5 !== md5($file) && $c > 0){
                $c--;
                $md5 = file_get_contents($this->repo . "?action=md5&package=" . $pack . '&file=' . $file_name);
                $file = file_get_contents($this->repo . "?action=file&package=" . $pack . '&file=' . $file_name);
            }

            return $file;
        }

        /*
         * Возвращает массив по url json файла.
         */

        private function getJsonData($url){
            return json_decode(@file_get_contents(rtrim($this->repo, '/') . $url), true);
        }

    }

    /*
     * Класс проводит "тупую" установку пакета.
     * Он не проверяет зависимости, совместимость и наличие уже установленной версии.
     * Идет простое копирование файлов в нужные папки и их модификация при необходимости
     */

    class piccolo_package_installer {
        /*
         * Конструктор класса.
         * Принимает параметры: папка с конфигами, папка с шаблонами, папка со скриптами, папка с локализациями, папка с пакетом, название файла с описанием репозитория
         */

        public function __construct($configs_dir, $templates_dir, $scripts_dir, $locales_dir, $package_dir, $info_name, $backup = false){
            $this->cfg = rtrim($configs_dir, DIRECTORY_SEPARATOR);
            $this->tmpl = rtrim($templates_dir, DIRECTORY_SEPARATOR);
            $this->scr = rtrim($scripts_dir, DIRECTORY_SEPARATOR);
            $this->lc = rtrim($locales_dir, DIRECTORY_SEPARATOR);
            $this->pckg = rtrim($package_dir, DIRECTORY_SEPARATOR);
            $this->backup = $backup;
            $this->inf = $info_name;
            $this->info = json_decode(file_get_contents($this->pckg . DIRECTORY_SEPARATOR . $this->inf), true);
        }

        //Параметры конструктора
        private $cfg, $tmpl, $scr, $lc, $pckg, $info, $backup, $inf;
        private $log = ''; //Переменная для хранения лога установки

        /*
         * Проводит полную установку пакета
         */

        public function fullInstall(){

            $this->log('[info]Full install called');
            if(!is_file($this->pckg . DIRECTORY_SEPARATOR . $this->inf)){
                $this->log('[error]Info file not found');
                return;
            }
            $this->info = json_decode(file_get_contents($this->pckg . DIRECTORY_SEPARATOR . $this->inf), true);
            if(isset($this->info['before_install']) && is_file($this->pckg . DIRECTORY_SEPARATOR . $this->info['before_install'])){
                $this->log('[info]"before_install" found, calling...');
                include($this->pckg . DIRECTORY_SEPARATOR . $this->info['before_install']);
                $this->log('[info]"before_install" done!');
            }
            $this->log('[info]Calling configs installation...');
            $this->installConfigs();
            $this->log('[info]Configs installation call done!');
            $this->log('[info]Calling locales installation...');
            $this->installLocales();
            $this->log('[info]Locales installation call done!');
            $this->log('[info]Calling templates installation...');
            $this->installTemplates();
            $this->log('[info]Templates installation call done!');
            $this->log('[info]Calling scripts installation...');
            $this->installScripts();
            $this->log('[info]Scripts installation call done!');
            if(isset($this->info['after_install']) && is_file($this->pckg . DIRECTORY_SEPARATOR . $this->info['after_install'])){
                $this->log('[info]"after_install" found, calling...');
                include($this->pckg . DIRECTORY_SEPARATOR . $this->info['after_install']);
                $this->log('[info]"after_install" done!');
            }
            $this->log('[info]Full installation done!');
        }

        /*
         * Рекурсивно объеденяет два массива с сохранением исходных данных в первом массиве
         */

        public function merge_array($arr1, $arr2){
			if(!is_array($arr1)){
				return $arr2;
			}
			if(!is_array($arr2)){
				return $arr1;
			}
			foreach($arr2 as $key => $value){
                if(!isset($arr1[$key])){
                    $arr1[$key] = $value;
                }elseif(is_array($arr1[$key]) && is_array($value)){
                    $arr1[$key] = $this->merge_array($arr1[$key], $value);
                }else{
                    $arr1[$key] = $value;
                }
            }
            return $arr1;
        }

        /*
         * Обновляет конфиги
         */

        public function installConfigs(){
            $this->log('[info]"installConfigs" called');
            if(!isset($this->info['configs']) || !is_array($this->info['configs'])){
                $this->log('[info]No configs found for installation.');
                return;
            }
            foreach($this->info['configs'] as $file){
                $this->log('Updating config: "' . $file['file_in'] . '" => "' . $file['file_out'] . '"');
                if(!is_file($this->pckg . DIRECTORY_SEPARATOR . $file['file_in'])){
                    $this->log('[error]No file "' . DIRECTORY_SEPARATOR . $file['file_in'] . '"');
                    continue;
                }

                $in = json_decode(file_get_contents($this->pckg . DIRECTORY_SEPARATOR . $file['file_in']), true);
                $out = is_file($this->cfg . DIRECTORY_SEPARATOR . $file['file_out']) ? json_decode(file_get_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out']), true)
                            : array();

                $dir = dirname($this->cfg . DIRECTORY_SEPARATOR . $file['file_out']);
                if(!is_dir($dir)){
                    mkdir($dir, 0777, true);
                }
                if(!isset($file['mode'])){
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'], json_encode($this->merge_array($out, $in)));
                }elseif($file['mode'] == 'new' && !is_file($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'])){
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'], json_encode($in));
                }elseif($file['mode'] == 'merge'){
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'], json_encode($this->merge_array($out, $in)));
                }elseif($file['mode'] == 'replace' && is_file($this->cfg . DIRECTORY_SEPARATOR . $file['file_out']) && $this->backup){
                    $this->log('[info]Backuping config "' . $this->cfg . DIRECTORY_SEPARATOR . $file['file_out'] . '"');
                    $out = file_get_contents(file_get_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out']));
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'] . '_' . date('d-m-Y_G_i_s') . '_bak', $out);
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'], json_encode($in));
                }elseif($file['mode'] == 'replace'){
                    file_put_contents($this->cfg . DIRECTORY_SEPARATOR . $file['file_out'], json_encode($in));
                }
            }
        }

        /*
         * Обновляет локализации
         */

        public function installLocales(){
            $this->log('[info]"installLocales" called');
            if(!isset($this->info['locales']) || !is_array($this->info['locales'])){
                $this->log('[info]No locales found for installation.');
                return;
            }
            foreach($this->info['locales'] as $file){
                $this->log('[info]Installing locale file: "' . $file['file_in'] . '" => "' . $file['file_out'] . '"');
                if(!is_file($this->pckg . DIRECTORY_SEPARATOR . $file['file_in'])){
                    $this->log('[error]No file "' . DIRECTORY_SEPARATOR . $file['file_in'] . '"');
                    continue;
                }

                $in = parse_ini_file($this->pckg . DIRECTORY_SEPARATOR . $file['file_in'], true);
				
                $out = is_file($this->lc . DIRECTORY_SEPARATOR . $file['file_out']) ? parse_ini_file($this->lc . DIRECTORY_SEPARATOR . $file['file_out'], true)
                            : array();
            
                mkdir(dirname($this->lc . DIRECTORY_SEPARATOR . $file['file_out']), 0777, true);
                file_put_contents($this->lc . DIRECTORY_SEPARATOR . $file['file_out'], $this->arrToINI($this->merge_array($out, $in)));
            }
        }

        /*
         * Обновляет файлы скриптов
         */

        public function installScripts(){
            $this->log('[info]"installScripts" called');
            if(!isset($this->info['scripts']) || !is_array($this->info['scripts'])){
                $this->log('[info]No scripts found for installation.');
                return;
            }
            foreach($this->info['scripts'] as $file){
                $this->log('[info]Installing script file: "' . $file['file_in'] . '" => "' . $file['file_out'] . '"');
                if(!is_file($this->pckg . DIRECTORY_SEPARATOR . $file['file_in'])){
                    $this->log('[error]No file "' . DIRECTORY_SEPARATOR . $file['file_in'] . '"');
                    continue;
                }
                $in = file_get_contents($this->pckg . DIRECTORY_SEPARATOR . $file['file_in']);

                $dir = dirname($this->scr . DIRECTORY_SEPARATOR . $file['file_out']);
                if(!is_dir(dirname($this->scr . DIRECTORY_SEPARATOR . $file['file_out']))){
                    mkdir($dir, 0777, true);
                }

                file_put_contents($this->scr . DIRECTORY_SEPARATOR . $file['file_out'], $in);
            }
        }

        /*
         * Обновляет файлы шаблонов, с бекапами
         */

        public function installTemplates(){
            $this->log('[info]"installTemplates" called');
            if(!isset($this->info['templates']) || !is_array($this->info['templates'])){
                $this->log('[info]No templates found for installation.');
                return;
            }
            foreach($this->info['templates'] as $file){
                $this->log('[info]Installing template file: "' . $file['file_in'] . '" => "' . $file['file_out'] . '"');
                if(!is_file($this->pckg . DIRECTORY_SEPARATOR . $file['file_in'])){
                    $this->log('[error]No file "' . DIRECTORY_SEPARATOR . $file['file_in'] . '"');
                    continue;
                }

                $in = file_get_contents($this->pckg . DIRECTORY_SEPARATOR . $file['file_in']);

                $dir = dirname($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out']);
                if(!is_dir($dir)){
                    mkdir($dir, 0777, true);
                }

                if(!isset($file['mode']) && !is_file($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'])){
                    file_put_contents($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'], $in);
                }elseif(isset($file['mode']) && $file['mode'] == 'new' && !is_file($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'])){
                    file_put_contents($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'], $in);
                }elseif(isset($file['mode']) && $file['mode'] == 'replace' && is_file($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out']) && $this->backup){
                    $this->log('[info]Backuping template "' . $this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'] . '"');
                    $out = file_get_contents(file_get_contents($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out']));
                    file_put_contents($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'] . '_' . date('d-m-Y_G_i_s') . '_bak', $out);
                }elseif(isset($file['mode']) && $file['mode'] == 'replace'){
                    file_put_contents($this->tmpl . DIRECTORY_SEPARATOR . $file['file_out'], $in);
                }
            }
        }

        /*
         * Преобразует массив в строку в формате ini
         */

        public function arrToINI($arr){
            $ini = "";
            foreach($arr as $section => $ar){
                $ini .= '[' . $section . ']' . "\r\n";
                foreach($ar as $key => $val){
                    $ini .= $key . '=\'' . addslashes($val) . '\'' . "\r\n";
                }
            }
            return $ini;
        }

        private function log($message){
            $this->log .= $message . '\r\n';
        }

        public function getLog(){
            return rtrim($this->log,'\r\n');
        }

        public function clearLog(){
            $this->log = '';
        }

    }
    