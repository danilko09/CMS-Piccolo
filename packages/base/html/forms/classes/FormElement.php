<?php

namespace danilko09\forms;
use danilko09\html\HTMLElement;

interface FormElement extends HTMLElement {
    
    /**
     * Задает значение поля
     */
    public function setValue(string $value);
    
    /**
     * Возвращает значение поля
     * @param \danilko09\forms\Form $parent Форма, в которой расположен элемент
     */
    public function getValue(Form $parent);
    
    /**
     * Проводит валидацию принятых данных
     * @param \danilko09\forms\Form $parent Форма, в которой расположен элемент
     */
    public function validate(Form $parent);
    
}