<?php

/**
 * Файлик для проверки зависимостей всех пакетов
 */

/**
 * Где искать пакеты
 */
$path = dirname(__DIR__).DIRECTORY_SEPARATOR.'packages';

search($path);

/**
 * Перебирает папки в $path, ищет все package.json и отправляет их на проверку
 * по мере обнаружения.
 * @param type $path
 */
function search($path){
    foreach(scandir($path) as $entry){
        if(in_array($entry,['.','..'])){continue;}
        $ent = $path.DIRECTORY_SEPARATOR.$entry;        
        if(is_dir($ent)){
            search($ent);
        }else if($entry == 'package.json'){
            doCheck($ent);
        }
    }
}

/**
 * Проводит проверку зависимостей, указанных в jsonPath на предмет существования 
 * этих самых зависимостей.
 * @param type $jsonPath
 */
function doCheck($jsonPath){
    global $path;
    echo cutPath($jsonPath).'<br/>';
    $json = json_decode(file_get_contents($jsonPath), true);
    if(isset($json['requires'])){
        foreach($json['requires'] as $pack){
            echo $pack.' ';
            echo file_exists($path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pack).DIRECTORY_SEPARATOR.'package.json') ?
                    '<font color="green">OK</font>' : '<font color="red">FAIL</font>';
            echo '<br/>';
        }
    }else{
        echo '<font color="orange">No "requires" found</font>';
    }
    echo '<hr/>';
}

function cutPath($fullPath){
    global $path;
    return substr($fullPath,strlen($path));
}