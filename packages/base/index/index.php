<?php

    if(is_file('install.php')){header('Location: install.php');}//Если есть установщик - переходим на него
    include __DIR__ . DIRECTORY_SEPARATOR . 'locations.php';
    include PICCOLO_CLASSES_DIR . DIRECTORY_SEPARATOR . 'PICCOLO_ENGINE.php'; //Загружаем ядро
    PICCOLO_ENGINE::start(); //Запускаем генератор страницы
