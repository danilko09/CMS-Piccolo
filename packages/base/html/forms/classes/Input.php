<?php

namespace danilko09\forms\elements;
use \danilko09\forms\FormElement;

class Input implements FormElement {
    
    use \danilko09\html\AttributesImpl;
    
    const TYPE_TEXT = 'text';
    const TYPE_PASSWORD = 'password';    
    const TYPE_HIDDEN = 'hidden';
    
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    
    const TYPE_SUBMIT = 'submit';
    const TYPE_RESET = 'reset';
    
    public function __construct($name, $type = TYPE_TEXT, $value = null) {
        $this->setAttribute('type', $type);
        $this->setAttribute('name', $name);
        $this->setValue($value);
    }
    
    public function getValue() {
        return $this->getAttribute('value');
    }

    public function setValue($value) {
        $this->setAttribute('value', $value);
    }
    
    public function __toString() {
        return '<input '.$this->getAttributesString()."/>\r\n";        
    }
    
}