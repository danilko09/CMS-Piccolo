<?php

namespace danilko09\customHTML;

/**
 * Description of customCSS
 *
 * @author Данил
 */
class customCSS {

    private static $imports = '';
    private static $code = "";
    
    public static function handleTag($tag){
	$ret = \PICCOLO_ENGINE::isTmpl('customHTML/CSSImports_frame') ? 
		\PICCOLO_ENGINE::getRTmpl('customHTML/CSSImports_frame', array('imports'=>self::$imports)) :
		'<style>'.self::$imports.'</style>';
	$ret .= \PICCOLO_ENGINE::isTmpl('customHTML/CSSGlobal_frame') ? 
		\PICCOLO_ENGINE::getRTmpl('customHTML/CSSGlobal_frame', array('code'=>self::$code)) :
		'<style>'.self::$code.'</style>';
	return \PICCOLO_ENGINE::PrepearHTML($ret);
    }
    
    public static function addImport($file){
	self::$imports .= \PICCOLO_ENGINE::isTmpl('customHTML/CSSImport') ? \PICCOLO_ENGINE::getRTmpl('customHTML/CSSImport', array('file'=>$file)) : 
	    '$import url("'.$file.'");';
    }
    
    public static function addCode($code){
	self::$code .= \PICCOLO_ENGINE::isTmpl('customHTML/CSSGlobal') ? \PICCOLO_ENGINE::getRTmpl('customHTML/CSSGlobal', array('code'=>$code)) : 
	    $code;
    }
    
}
