<?php

    if(is_file('install.php')){header('Location: install.php');}//Если есть установщик - переходим на него
    include __DIR__ . DIRECTORY_SEPARATOR . 'piccolo.php'; //Загружаем ядро
    PICCOLO_ENGINE::start(); //Запускаем генератор страницы
