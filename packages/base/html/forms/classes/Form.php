<?php

namespace danilko09\forms;
use danilko09\html\HTMLElement;
use danilko09\forms\FormElementValidationException;
use danilko09\forms\elements\Input;

class Form implements HTMLElement {
    
    use danilko09\html\AttributesImpl;
    
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    
    public function __construct(string $method = METHOD_GET, string $action = null) {
        $this->attributes['method'] = $method;
        $this->method = $method;
        if($action != null){
            $this->setAttribute('action', $action);            
        }
        
        $this->addElement(new Input('_', Input::TYPE_HIDDEN, $this->getHash()));
    }

    private $method;
    
    public function getMethod(){
        return $this->method;
    }    
    
    private $elements = [];
    
    public function addElement(HTMLElement $element){
        $this->elements[] = $element;
    }
    
    public function getElement($name){
        foreach($this->getFormElements() as $element){
            if($element->getAttribute('name') == $name){
                return $element;
            }
        }
    }
    
    public function getFormElements(){
        $elements = [];
        
        foreach($this->elements as $element){
            if($element instanceof FormElement){
                $elements[] = $element;
            }
        }
        
        return $elements;
        
    }
    
    public function getElementValue($name){
        $elem = $this->getElement($name);
        return $elem != null ? $elem->getValue($this) : null;
    }
    
    public function validate(){
        $exceptions = [];
        
        foreach($this->getFormElements() as $element){
                try{
                    $element->validate($this);
                }catch(FormElementValidationException $ex){
                    $exceptions[] = $ex;
                }
        }
        
        if(count($exceptions) > 0){
            throw new FormValidationException($exceptions);
        }
        
    }
    
    public function __toString() {
        $html = '<form'.$this->getAttributesString().">\r\n";
        
        foreach($this->elements as $element){
            $html .= $element."\r\n";
        }
        
        $html .= '</form>';
        return $html;
    }
    
    public function isFormSent(){
        $type = $this->getMethod() == Form::METHOD_GET ? INPUT_GET : INPUT_POST;
        return $this->getHash() == filter_input($type, '_');
    }
    
    public function getHash(){
        return md5(serialize($this));
    }
    
}