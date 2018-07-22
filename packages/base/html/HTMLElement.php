<?php

namespace danilko09\html;

interface HTMLElement {
    
    /**
     * Задает значение аттрибута
     * @param type $attr
     * @param type $value
     */
    public function setAttribute($attr, $value);
    
    /**
     * Должен возвращать значение аттрибута
     * @param type $attr
     */
    public function getAttribute($attr);
    
    /**
     * Должен возвращать html элемента
     */
    public function __toString();
    
}