<?php

function testLog($msg){
    echo htmlspecialchars($msg).'<br/>';
}

function loadPackageClasses($package){
    
    $repoPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'packages';
    $packagePath = $repoPath.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, $package);
    $packageInfo = json_decode(file_get_contents($packagePath.DIRECTORY_SEPARATOR.'package.json'), true);
    
    if(isset($packageInfo['requires'])){
        if(!is_array($packageInfo['requires'])){
            loadPackageClasses($packageInfo['requires']);
        }
        foreach($packageInfo['requires'] as $dependency){
            loadPackageClasses($dependency);
        }
    }
    
    testLog("Loading classes for '$package'");    
    foreach($packageInfo['classes'] ?? [] as $class){
        testLog($class['file_in']);
        include $packagePath.DIRECTORY_SEPARATOR.$class['file_in'];
    }
    testLog("");
}