<?php

namespace danilko09\forms;

class FormValidationException extends \Throwable {
    
    private $exceptions;
    
    public function __construct(array $elementsExceptions) {
        $this->exceptions = $elementsExceptions;
    }
    
    public function getExceptions(){
        return $this->exceptions;
    }
    
}