<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

abstract class liveagent_Form_Validator_Base {
    protected $valid = true;
    protected $fields;
    protected $errors = array();

    public function setFields($fields) {
        $this->fields = $fields;
    }

    /**
     * @return boolean
     */
    public abstract function isValid();

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    protected function addError($message) {
        $this->errors[] = $message;
        $this->valid = false;
    }
}