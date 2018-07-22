<?php

namespace danilko09\customHTML;
use PICCOLO_ENGINE;

/**
 * Description of customHTML
 *
 * @author Данил
 */
class customHTML {

    
    /**
     * Автозагрузка
     * Регистрирует обработчик для тега "customCSS"
     */
    public static function onLoad(){
	PICCOLO_ENGINE::RegisterTagHandler('customCSS', 'danilko09\\customHTML\\customCSS');
    }

    /**
     * Добавляет импорт файла в HTML код шаблона
     * @param string $file URL CSS-файла
     * @return null
     */
    public static function addCSSImport($file){
	return customCSS::addImport($file);
    }
    
    /**
     * Добавляет CSS-код в HTML код шаблона
     * @param string $code CSS-код
     * @return null
     */
    public static function addCSSGlobal($code){
	return customCSS::addCode($code);
    }
    
}
