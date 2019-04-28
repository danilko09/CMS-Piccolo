<?php

$defines = [
	"PICCOLO_ROOT_DIR"	=>	"__DIR__",
	"PICCOLO_CMS_DIR"	=>	"PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . 'piccolo'",
	"PICCOLO_CMS_URL"	=>	"'/piccolo'",
	"PICCOLO_CONFIGS_DIR"	=>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'config'",
	"PICCOLO_DATA_DIR"	=>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'data'",
	"PICCOLO_TEMPLATES_DIR"	=>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'templates'",
	"PICCOLO_TEMPLATES_URL"	=>	"PICCOLO_CMS_URL . '/templates'",
	"PICCOLO_SCRIPTS_DIR"	=>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'scripts'",
	"PICCOLO_CLASSES_DIR"	=>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'classpath'",
	"PICCOLO_TRANSLATIONS_DIR" =>	"PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'locales'"	
];

if(!defined("PICCOLO_ROOT_DIR")){
	define("PICCOLO_ROOT_DIR", getCWD());
}elseif(PICCOLO_ROOT_DIR != getCWD()){
	$defines['PICCOLO_ROOT_DIR'] = "'" . PICCOLO_ROOT_DIR . "'";
}

//todo добавить подстановку DIRECTORY_SEPARATOR для нестандартных расположений (в substr) во всем файле
if(!defined("PICCOLO_CMS_DIR")){
	define("PICCOLO_CMS_DIR", PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . 'piccolo');
}elseif(PICCOLO_CMS_DIR != PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . 'piccolo'){
	if(strpos(PICCOLO_CMS_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_CMS_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_CMS_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_CMS_DIR"] = "'" . PICCOLO_CMS_DIR . "'";
	}
}

if(!defined("PICCOLO_CMS_URL")){
	define("PICCOLO_CMS_URL", '/piccolo');
}else{
	$defines["PICCOLO_CMS_URL"] = "'" . PICCOLO_CMS_URL . "'";
}

if(!defined("PICCOLO_CONFIGS_DIR")){
	define("PICCOLO_CONFIGS_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'config');
}elseif(PICCOLO_CONFIGS_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'config'){
	if(strpos(PICCOLO_CONFIGS_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_CONFIGS_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_CONFIGS_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_CONFIGS_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_CONFIGS_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_CONFIGS_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_CONFIGS_DIR"] = "'" . PICCOLO_CONFIGS_DIR . "'";
	}
}

if(!defined("PICCOLO_DATA_DIR")){
	define("PICCOLO_DATA_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'data');
}elseif(PICCOLO_DATA_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'data'){
	if(strpos(PICCOLO_DATA_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_DATA_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_DATA_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_DATA_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_DATA_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_DATA_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_DATA_DIR"] = "'" . PICCOLO_DATA_DIR . "'";
	}
}

if(!defined("PICCOLO_TEMPLATES_DIR")){
	define("PICCOLO_TEMPLATES_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'templates');
}elseif(PICCOLO_TEMPLATES_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'templates'){
	if(strpos(PICCOLO_TEMPLATES_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_TEMPLATES_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_TEMPLATES_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_TEMPLATES_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_TEMPLATES_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_TEMPLATES_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_TEMPLATES_DIR"] = "'" . PICCOLO_TEMPLATES_DIR . "'";
	}
}

if(!defined("PICCOLO_TEMPLATES_URL")){
	define("PICCOLO_TEMPLATES_URL", PICCOLO_CMS_URL . '/templates');
}elseif(PICCOLO_TEMPLATES_URL != PICCOLO_CMS_URL . '/templates'){
	if(strpos(PICCOLO_TEMPLATES_URL, PICCOLO_CMS_URL) === 0){
		$defines["PICCOLO_TEMPLATES_URL"] = "PICCOLO_CMS_URL . '" . substr(PICCOLO_TEMPLATES_URL, strlen(PICCOLO_CMS_URL)) . "'";
	}else{
		$defines["PICCOLO_TEMPLATES_URL"] = "'" . PICCOLO_TEMPLATES_URL . "'";
	}
}

if(!defined("PICCOLO_SCRIPTS_DIR")){
	define("PICCOLO_SCRIPTS_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'scripts');
}elseif(PICCOLO_SCRIPTS_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'scripts'){
	if(strpos(PICCOLO_SCRIPTS_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_SCRIPTS_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_SCRIPTS_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_SCRIPTS_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_SCRIPTS_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_SCRIPTS_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_SCRIPTS_DIR"] = "'" . PICCOLO_SCRIPTS_DIR . "'";
	}
}

if(!defined("PICCOLO_CLASSES_DIR")){
	define("PICCOLO_CLASSES_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'classpath');
}elseif(PICCOLO_CLASSES_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'classpath'){
	if(strpos(PICCOLO_CLASSES_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_CLASSES_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_CLASSES_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_CLASSES_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_CLASSES_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_CLASSES_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_CLASSES_DIR"] = "'" . PICCOLO_CLASSES_DIR . "'";
	}
}

if(!defined("PICCOLO_TRANSLATIONS_DIR")){
	define("PICCOLO_TRANSLATIONS_DIR", PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'locales');
}elseif(PICCOLO_TRANSLATIONS_DIR != PICCOLO_CMS_DIR . DIRECTORY_SEPARATOR . 'locales'){
	if(strpos(PICCOLO_TRANSLATIONS_DIR, PICCOLO_CMS_DIR) === 0){
		$defines["PICCOLO_TRANSLATIONS_DIR"] = "PICCOLO_CMS_DIR . '" . substr(PICCOLO_TRANSLATIONS_DIR, strlen(PICCOLO_CMS_DIR)) . "'";
	}elseif(strpos(PICCOLO_TRANSLATIONS_DIR, PICCOLO_ROOT_DIR) === 0){
		$defines["PICCOLO_TRANSLATIONS_DIR"] = "PICCOLO_ROOT_DIR . '" . substr(PICCOLO_TRANSLATIONS_DIR, strlen(PICCOLO_ROOT_DIR)) . "'";
	}else{
		$defines["PICCOLO_TRANSLATIONS_DIR"] = "'" . PICCOLO_TRANSLATIONS_DIR . "'";
	}
}

$str = "<?php\n";
foreach($defines as $const => $val){
	$str .= "define('" . $const . "', " . $val . ");\n";
}
file_put_contents(PICCOLO_ROOT_DIR . DIRECTORY_SEPARATOR . "locations.php", $str);
