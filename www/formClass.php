<?php 

require_once('inputClass.php');
require_once('textClass.php');
require_once('checkboxClass.php');
require_once('fileClass.php');
require_once('exceptions.php');

class formClass {
    private $name;
    private $valid;
    private $method;
    private $action;
    private $successAddress;
    private $submitLabel;
    private $inputTypes = array(
        'hidden' => 'text', 
        'text' => 'text',
        'email' => 'text',
        'select' => 'checkbox'
    );
    private $inputs = array();

    public function __construct($name, $parameters = array()) {
        $this->_checkFormName($name);

        $this->name = $name;
        $this->submitLabel = (isset($parameters['submitLabel'])) ? $parameters['submitLabel'] : 'submit';
        $this->action = (isset($parameters['action'])) ? $parameters['action'] : '';
        $this->method = ((isset($parameters['method'])) && (strToLower($parameters['method'])) === 'get') ? 'get' : 'post'; // @todo make this more verbose 
        $this->successAddress = (isset($parameters['successAddress'])) ? $parameters['successAddress'] : $_SERVER['REQUEST_URI']; // @todo url check?
        $this->valid = ((isset($_SESSION[$this->name . '-valid'])) && ($_SESSION[$this->name . '-valid'] === true)) ? true : false;
        
        if (!session_id()) {
            session_start();
        }

        if ((isset($_SESSION[$this->name . '-valid'])) &&  ($_SESSION[$this->name . '-valid'] == true)) {
            $this->valid = true;
        }
        $this->addHidden('form-name', array('value' => $this->name));
    }

    public function __call($functionName, $functionArguments) {
        if (substr($functionName, 0, 3) === 'add') {
            $type = strtolower(str_replace('add', '', $functionName)); // @todo case insensitive
            $name = (isset($functionArguments[0])) ? $functionArguments[0] : '';
            $parameters = (isset($functionArguments[1])) ? $functionArguments[1] : array();
            $this->addInput($type, $name, $parameters);
        }
    }

    public function addInput($type, $name, $parameters = array()) {
        $this->_checkInputType($type);
        $this->_checkInputName($name);

        $class = $this->inputTypes[$type] . "Class";
        $this->inputs[] = new $class($type, $name, $parameters, $this->name);
    }

    public function __toString() {
        $renderedForm = '';
        foreach($this->inputs as $input) {
            $renderedForm .= $input;
        }
        $renderedMethod = ' method="' . $this->method . '"';
        $renderedAction = (empty($this->action)) ? '' : ' action="' . $this->action . '"';
        $renderedSubmit = '<p id="' . $this->name . '-submit"><input type="submit" name="submit" value="' . $this->submitLabel . '"></p>'; // @todo clean up

        $renderedForm = '<form id="' . $this->name . '" name="' . $this->name . '"' . $renderedMethod . $renderedAction . '>' . $renderedForm . $renderedSubmit . '</form>';
        return $renderedForm;
    }

    public function validate() {
        if ((isset($_POST['form-name'])) && (($_POST['form-name']) === $this->name)) {
            $_SESSION[$this->name . '-data'] = $_POST;
            if ($_SESSION[$this->name . '-data']['firstname'] == 'valid') {
                $_SESSION[$this->name . '-valid'] = true;
                header('Location: ' . $this->successAddress);
                die( "Tried to redirect you to " . $this->successAddress);
            } else {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                die( "Tried to redirect you to " . $_SERVER['REQUEST_URI']);
            }
        }
        // @todo ...validate
    }

    public function isValid() {
        return $this->valid;
    }

    public function populate() {
        
    }

    public function getInputs() { // @todo get single input instead?
        return $this->inputs;
    }

    private function _checkFormName($name) {
        if (!is_string($name)) {
            throw new formNameNoStringException();
        }
        if (trim($name) === '') {
            throw new invalidFormNameException();
        }
    }

    private function _checkInputName($name) {
        foreach($this->inputs as $input) {  
            if ($input->getName() === $name) {
                throw new duplicateInputNameException();
            }
        }
    }

    private function _checkInputType($type) {
        if (!isset($this->inputTypes[$type])) {
            throw new unknownInputTypeException();
        }
    }
}
