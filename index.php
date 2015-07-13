<?php
   
    define('INSTALLER_WORKS',  microtime(true));
    
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    //Скрипт для разработчиков пакетов под CMS Piccolo
    //В него встроен простейший установщик пакетов и функции для сборки
    //Использование: есть два режима - постоянная пересборка и пересборка по запросу
    //в первом режиме cms постоянно пересобирается, во втором пересборка производится при get-параметре rebuild=1
    //В файле install.json указывайте пакеты в том порядке, в каком они должны быть установлены, так же не забывайте про зависимости
    //Требования: наличие в папке файла ядра piccolo.php
    
    define('AUTOREBUILD',true);//режим пересборки: true - пересобирать при каждом запросе, false - пересобирать только по спец. запросу (GET: rebuild=1)
    
    if(is_file('piccolo.php')){//Подключаем ядро
        include 'piccolo.php';
    }else{
        die('Не найден файл ядра');
    }
    
    $c = null;//Счетчик установленных пакетов
    if(AUTOREBUILD || filter_input(INPUT_GET, 'rebuild') == '1'){//Пересборка, если надо
        $c = 0;
        if(!is_file('packages'.DIRECTORY_SEPARATOR.'install.json')){print('Не найден файл install.json');}
        $i = json_decode(file_get_contents('packages'.DIRECTORY_SEPARATOR.'install.json'),true);
        $log = '';
        foreach($i as $pack){
            $log .= '\r\nУстановка пакета "'.$pack['path'].'"\r\n';
            $installer = new piccolo_package_installer(
                    PICCOLO_CONFIGS_DIR,
                    PICCOLO_TEMPLATES_DIR,
                    PICCOLO_SCRIPTS_DIR,
                    PICCOLO_TRANSLATIONS_DIR,
                    'packages'.DIRECTORY_SEPARATOR.$pack['path'],
                    $pack['info']
                    );
            $installer->fullInstall();
            $log .= $installer->getLog();
            $c++;
        }
    }
    
    if(filter_input(INPUT_GET,'log') !== null){
        echo 'log:<br/>'.str_replace('\r\n','<br/>',str_replace('[info]','<font color="green">[info]</font>',str_replace('[error]','<font color="red">[error]</font>',$log)));
    }
    $msg = '<!-- Piccolo developer mainframe works: '.(round((microtime(true) - INSTALLER_WORKS) * 1000)) . 'ms; installed packages count: '.($c == null ? 'no packages' : $c).' -->';
    PICCOLO_ENGINE::start();//Запускаем ядро
    echo $msg;
    
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
        public function __construct($configs_dir,$templates_dir,$scripts_dir,$locales_dir,$package_dir,$info_name,$backup = false){
            $this->cfg  = rtrim($configs_dir,DIRECTORY_SEPARATOR);
            $this->tmpl = rtrim($templates_dir,DIRECTORY_SEPARATOR);
            $this->scr  = rtrim($scripts_dir,DIRECTORY_SEPARATOR);
            $this->lc   = rtrim($locales_dir,DIRECTORY_SEPARATOR);
            $this->pckg = rtrim($package_dir,DIRECTORY_SEPARATOR);
            $this->backup = $backup;
            $this->info = json_decode(file_get_contents($this->pckg.DIRECTORY_SEPARATOR.$info_name),true);
        }
        
        //Параметры конструктора
        private $cfg,$tmpl,$scr,$lc,$pckg,$info,$backup;
        private $log = '';//Переменная для хранения лога установки
        
        /*
         * Проводит полную установку пакета
         */
        public function fullInstall(){
            $this->log('[info]Full install called');
            if(isset($this->info['before_install']) && is_file($this->pckg.DIRECTORY_SEPARATOR.$this->info['before_install'])){
                $this->log('[info]"before_install" found, calling...');
                include($this->pckg.DIRECTORY_SEPARATOR.$this->info['before_install']);
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
            if(isset($this->info['after_install']) && is_file($this->pckg.DIRECTORY_SEPARATOR.$this->info['after_install'])){
                $this->log('[info]"after_install" found, calling...');
                include($this->pckg.DIRECTORY_SEPARATOR.$this->info['after_install']);
                $this->log('[info]"after_install" done!');
            }
            $this->log('[info]Full installation done!');
        }
        
        /*
         * Рекурсивно объеденяет два массива с сохранением исходных данных в первом массиве
         */
        public function merge_array($arr1,$arr2){
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
            if(!isset($this->info['configs']) || !is_array($this->info['configs'])){$this->log('[info]No configs found for installation.');return;}
            foreach($this->info['configs'] as $file){
                $this->log('Updating config: "'.$file['file_in'].'" => "'.$file['file_out'].'"');
                if(!is_file($this->pckg.DIRECTORY_SEPARATOR.$file['file_in'])){$this->log('[error]No file "'.DIRECTORY_SEPARATOR.$file['file_in'].'"');continue;}

                $in  = json_decode(file_get_contents($this->pckg.DIRECTORY_SEPARATOR.$file['file_in']),true);
                $out = is_file($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'])
                     ? json_decode(file_get_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out']),true)
                     : array();
                
                $dir = dirname($this->cfg.DIRECTORY_SEPARATOR.$file['file_out']);
                if(!is_dir($dir)){mkdir($dir,0777,true);}
                if(!isset($file['mode'])){file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'], json_encode($this->merge_array($out, $in)));}
                elseif($file['mode'] == 'new' && !is_file($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'])){file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'], json_encode($in));}
                elseif($file['mode'] == 'merge'){file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'], json_encode($this->merge_array($out, $in)));}
                elseif($file['mode'] == 'replace' && is_file($this->cfg.DIRECTORY_SEPARATOR.$file['file_out']) && $this->backup){
                    $this->log('[info]Backuping config "'.$this->cfg.DIRECTORY_SEPARATOR.$file['file_out'].'"');
                    $out = file_get_contents(file_get_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out']));
                    file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'].'_'.date('d-m-Y_G_i_s').'_bak', $out);
                    file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'], json_encode($in));
                }elseif($file['mode'] == 'replace'){
                    file_put_contents($this->cfg.DIRECTORY_SEPARATOR.$file['file_out'], json_encode($in));
                }
            }
        }
        
        /*
         * Обновляет локализации
         */
        public function installLocales(){
            $this->log('[info]"installLocales" called');
            if(!isset($this->info['locales']) || !is_array($this->info['locales'])){$this->log('[info]No locales found for installation.');return;}
            foreach($this->info['locales'] as $file){
                $this->log('[info]Installing locale file: "'.$file['file_in'].'" => "'.$file['file_out'].'"');
                if(!is_file($this->pckg.DIRECTORY_SEPARATOR.$file['file_in'])){$this->log('[error]No file "'.DIRECTORY_SEPARATOR.$file['file_in'].'"');continue;}

                $in  = parse_ini_file($this->pckg.DIRECTORY_SEPARATOR.$file['file_in'],true);
                $out = is_file($this->lc.DIRECTORY_SEPARATOR.$file['file_out'])
                     ? parse_ini_file($this->lc.DIRECTORY_SEPARATOR.$file['file_out'],true)
                     : array();
                
                $dir = dirname($this->lc.DIRECTORY_SEPARATOR.$file['file_out']);
                if(!is_dir($dir)){mkdir($dir,0777,true);}
                file_put_contents($this->lc.DIRECTORY_SEPARATOR.$file['file_out'], $this->arrToINI($this->merge_array($out, $in)));
            }
        }
        
        /*
         * Обновляет файлы скриптов
         */
        public function installScripts(){
            $this->log('[info]"installScripts" called');
            if(!isset($this->info['scripts']) || !is_array($this->info['scripts'])){$this->log('[info]No scripts found for installation.');return;}
            foreach($this->info['scripts'] as $file){
                $this->log('[info]Installing script file: "'.$file['file_in'].'" => "'.$file['file_out'].'"');
                if(!is_file($this->pckg.DIRECTORY_SEPARATOR.$file['file_in'])){$this->log('[error]No file "'.DIRECTORY_SEPARATOR.$file['file_in'].'"');continue;}
                $in = file_get_contents($this->pckg.DIRECTORY_SEPARATOR.$file['file_in']);
                
                $dir = dirname($this->scr.DIRECTORY_SEPARATOR.$file['file_out']);
                if(!is_dir(dirname($this->scr.DIRECTORY_SEPARATOR.$file['file_out']))){mkdir($dir,0777,true);}
                
                file_put_contents($this->scr.DIRECTORY_SEPARATOR.$file['file_out'], $in);
            }
        }
        
        /*
         * Обновляет файлы шаблонов, с бекапами
         */
        public function installTemplates(){
            $this->log('[info]"installTemplates" called');
            if(!isset($this->info['templates']) || !is_array($this->info['templates'])){$this->log('[info]No templates found for installation.');return;}
            foreach($this->info['templates'] as $file){
                $this->log('[info]Installing template file: "'.$file['file_in'].'" => "'.$file['file_out'].'"');
                if(!is_file($this->pckg.DIRECTORY_SEPARATOR.$file['file_in'])){$this->log('[error]No file "'.DIRECTORY_SEPARATOR.$file['file_in'].'"');continue;}
                
                $in = file_get_contents($this->pckg.DIRECTORY_SEPARATOR.$file['file_in']);
                
                $dir = dirname($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out']);
                if(!is_dir($dir)){mkdir($dir,0777,true);}
                
                if(!isset($file['mode']) && !is_file($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'])){file_put_contents($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'], $in);}
                elseif(isset($file['mode']) && $file['mode'] == 'new' && !is_file($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'])){file_put_contents($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'], $in);}
                elseif(isset($file['mode']) && $file['mode'] == 'replace' && is_file($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out']) && $this->backup){
                    $this->log('[info]Backuping template "'.$this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'].'"');
                    $out = file_get_contents(file_get_contents($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out']));
                    file_put_contents($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'].'_'.date('d-m-Y_G_i_s').'_bak', $out);
                }elseif(isset($file['mode']) && $file['mode'] == 'replace'){
                    file_put_contents($this->tmpl.DIRECTORY_SEPARATOR.$file['file_out'], $in);
                }
            }
        }
        
        /*
         * Преобразует массив в строку в формате ini
         */
        public function arrToINI($arr){
            $ini = "";
            foreach($arr as $section => $ar){
                $ini .= '['.$section.']'."\r\n";
                foreach($ar as $key=>$val){
                    $ini .= $key.'=\''.addslashes($val).'\''."\r\n";
                }
            }
            return $ini;
        }
        
        private function log($message){
            $this->log .= $message.'\r\n';
        } 
        
        public function getLog(){
            return $this->log;
        }
        
    }
    