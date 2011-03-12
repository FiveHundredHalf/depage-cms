<?php

namespace depage\htmlform\elements;

use depage\htmlform\abstracts;

/**
 * HTML textarea element.
 **/
class textarea extends abstracts\textClass {
    /**
     * @param $name input elements' name
     * @param $parameters array of input element parameters, HTML attributes, validator specs etc.
     * @param $formName name of the parent HTML form. Used to identify the element once it's rendered.
     **/
    public function __construct($name, $parameters, $formName) {
        parent::__construct($name, $parameters, $formName);
        
        $this->rows = (isset($parameters['rows'])) ? $parameters['rows'] : null;
        $this->cols = (isset($parameters['cols'])) ? $parameters['cols'] : null;
    }

    /**
     * Renders element to HTML.
     *
     * @return string of HTML rendered element
     **/
    public function render($value, $attributes, $requiredChar, $class) {
        $rows = ($this->rows !== null) ? " rows=\"$this->rows\"" : "";
        $cols = ($this->cols !== null) ? " cols=\"$this->cols\"" : "";

        return "<p id=\"$this->formName-$this->name\" class=\"$class\">" .
            "<label>" .
                "<span class=\"label\">$this->label$requiredChar</span>" .
                "<textarea name=\"$this->name\"$attributes $rows$cols>$value</textarea>" .
            "</label>" .
        "</p>\n";
    }
}