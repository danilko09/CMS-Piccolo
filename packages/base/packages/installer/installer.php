<?php

namespace danilko09\packages;

use danilko09\packages\Package;
use Exception;

final class Installer {

    /**
     * Возвращает информацию о пакете по пути до package.json
     * @param string $path Путь до package.json
     * @return Package Информация о пакете
     */
    public static function getPackageInfoFromJSONPath(string $path): Package {
	if(!file_exists($path)){
		throw new Exception("JSON not found (".$path.")");
	}
        $json = file_get_contents($path);
        return new Package(json_decode($json, true));
    }

    private static function getPackagePath(string $repoPath, string $package): string {
        return $repoPath . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $package);
    }

    /**
     * Возвращает информацию о пакете на основе package.json в репозитории
     * @param string $repoPath Расположение репозитория
     * @param string $package Пакет, информацию о котором необходимо получить
     * @return Package Информация о пакете
     */
    public static function getPackageInfoFromRepo(string $repoPath, string $package): Package {
        return self::getPackageInfoFromJSONPath(
                        self::getPackagePath($repoPath, $package) . DIRECTORY_SEPARATOR . 'package.json'
        );
    }

    /**
     * Рекурсивно устанавливает пакеты вместе с зависимостями
     * @param string $repoPath Путь до репозитория пакетов
     * @param string $package Пакет, который требуется установить
     * @param type $toInstall Для рекурсии. Пакеты, которые будут установлены после текущего.
     */
    public static function installPackage(string $repoPath, string $package, &$toInstall = []) {
        $packagePath = self::getPackagePath($repoPath, $package);
        $packageInfo = self::getPackageInfoFromRepo($repoPath, $package);

        self::installDependencies($repoPath, $packageInfo, $toInstall);

        if(class_exists("danilko09\\packages\\VersionControl") && !is_null($packageInfo->getVersion())){
		if(!\danilko09\packages\VersionControl::isCurrentLower($package, $packageInfo->getVersion()) &&
                    	!\danilko09\packages\VersionControl::isVersionUndefined($package)){
                return;
            }
        }
        
        if(!is_null($packageInfo->getBeforeInstall()))	
        	self::runScript($packagePath, $packageInfo->getBeforeInstall());

        self::deployRootFiles($packagePath, $packageInfo);
        self::updateConfigs($packagePath, $packageInfo);
        self::installLocales($packagePath, $packageInfo);
        self::deployTemplates($packagePath, $packageInfo);
        self::deployClasses($packagePath, $packageInfo);
        self::deployScripts($packagePath, $packageInfo);

	if(!is_null($packageInfo->getAfterInstall()))
	        self::runScript($packagePath, $packageInfo->getAfterInstall());
        
        if(class_exists("danilko09\\packages\\VersionControl") && $packageInfo->getVersion() != ""){            
            danilko09\packages\VersionControl::setCurrentVersion($package, $packageInfo->getVersion());
        }
    }

    private static function installDependencies(string $repoPath, Package $packageInfo, &$toInstall = []) {
        foreach ($packageInfo->getDependenciesList() as $dependency) {
            if (!in_array($dependency, $toInstall)) {
                $toInstall[] = $dependency;
                self::installPackage($repoPath, $dependency, $toInstall);
            }
        }
    }

    private static function runScript(string $packagePath, string $script) {
        if ($script != null) {
            $scriptPath = self::relPathToAbs($packagePath, $script);
            self::checkFileExists($scriptPath);
            include $scriptPath;
        }
    }

    private static function deployRootFiles(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getRootFiles() as $fileInfo) {
            $fileData = self::getFileContents($packagePath, $fileInfo['file_in']);
            $to = self::relPathToAbs(PICCOLO_ROOT_DIR, $fileInfo['file_out']);
            self::deployFile($fileInfo['mode'] ?? 'new', $to, $fileData);
        }
    }

    private static function updateConfigs(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getConfigs() as $fileInfo) {
            $fileData = self::getFileContents($packagePath, $fileInfo['file_in']);
            $to = self::relPathToAbs(PICCOLO_CONFIGS_DIR, $fileInfo['file_out']);

            $mode = $fileInfo['mode'] ?? 'new';
            if ($mode == 'merge') {
                $current = file_exists($to) ? json_decode(file_get_contents($to), true) : [];
                $merged = self::merge_array(json_decode($fileData, true), $current);
                self::deployFile('replace', $to, json_encode($merged));
            } else {
                self::deployFile($mode, $to, $fileData);
            }
        }
    }

    private static function installLocales(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getLocales() as $fileInfo) {
            $inPath = self::relPathToAbs($packagePath, $fileInfo['file_in']);
            self::checkFileExists($inPath);
            $fileData = parse_ini_file($inPath, true);
            $to = self::relPathToAbs(PICCOLO_TRANSLATIONS_DIR, $fileInfo['file_out']);

            $curr = file_exists($to) ? parse_ini_file($to, true) : [];
            self::deployFile('replace', $to, self::arrToINI(self::merge_array($curr, $fileData)));
        }
    }

    private static function deployTemplates(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getTemplates() as $fileInfo) {
            $fileData = self::getFileContents($packagePath, $fileInfo['file_in']);
            $to = self::relPathToAbs(PICCOLO_TEMPLATES_DIR, $fileInfo['file_out']);
            self::deployFile($fileInfo['mode'] ?? 'new', $to, $fileData);
        }
    }

    private static function deployClasses(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getClasses() as $fileInfo) {
            $fileData = self::getFileContents($packagePath, $fileInfo['file_in']);
            $to = self::relPathToAbs(PICCOLO_CLASSES_DIR, $fileInfo['file_out']);
            self::deployFile('replace', $to, $fileData);
        }
    }

    private static function deployScripts(string $packagePath, Package $packageInfo) {
        foreach ($packageInfo->getScripts() as $fileInfo) {
            $fileData = self::getFileContents($packagePath, $fileInfo['file_in']);
            $to = self::relPathToAbs(PICCOLO_SCRIPTS_DIR, $fileInfo['file_out']);
            self::deployFile('replace', $to, $fileData);
        }
    }

    private static function deployFile($mode, $to, $fileData) {
        $dir = dirname($to);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        switch ($mode) {
            case 'replace':
                file_put_contents($to, $fileData);
                break;

            case 'new':
                if (!file_exists($to)) {
                    file_put_contents($to, $fileData);
                }
                break;

            default:
                throw new Exception("Unknown deploy mode '$mode'");
        }
    }

    private static function relPathToAbs(string $packagePath, $filePath) {
        return $packagePath . DIRECTORY_SEPARATOR . $filePath;
    }

    private static function getFileContents(string $packagePath, string $filePath) {
        $inPath = self::relPathToAbs($packagePath, $filePath);
        self::checkFileExists($inPath);
        return file_get_contents($inPath);
    }

    private static function checkFileExists(string $path) {
        if (!file_exists($path)) {
            throw new Exception("File \"$path\" not found!");
        }
    }

    /**
     * Рекурсивно объеденяет два массива с сохранением исходных данных в первом массиве
     */
    public static function merge_array($arr1, $arr2) {
        foreach ($arr2 as $key => $value) {
            if (!isset($arr1[$key])) {
                $arr1[$key] = $value;
            } elseif (is_array($arr1[$key]) && is_array($value)) {
                $arr1[$key] = self::merge_array($arr1[$key], $value);
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }

    /*
     * Преобразует массив в строку в формате ini
     */

    public static function arrToINI($arr) {
        $ini = "";
        foreach ($arr as $section => $ar) {
            $ini .= '[' . $section . ']' . "\r\n";
            foreach ($ar as $key => $val) {
                $ini .= $key . '=\'' . addslashes($val) . '\'' . "\r\n";
            }
        }
        return $ini;
    }

}
