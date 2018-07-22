<?php

namespace danilko09\html;

trait AttributesImpl {
    
    private $_attributes = [];
    
    public function setAttribute($attr, $value) {
        $this->_attributes[$attr] = $value;
    }

    public function getAttribute($attr) {
        return isset($this->_attributes[$attr]) ? $this->_attributes[$attr] : null;
    }
    
    protected function getAttributesString(){
        $str = '';
        
        foreach($this->_attributes as $attr => $value){
            $str .= ' '.$attr.'="'.str_replace('"', '\"', $value);
        }
        
        return trim($str);
    }
    
}