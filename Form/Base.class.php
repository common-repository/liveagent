<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

abstract class liveagent_Form_Base extends liveagent_Base {

    const TYPE_TEMPLATE = 'template';

    /**
     * @var liveagent_Settings
     */
    protected $settings;
    protected $variables = array('infoMessages' => '', 'errorMessages' => '');
    protected $connectionSucc = false;

    public function __construct(liveagent_Settings $settings) {
        $this->settings = $settings;
        $this->initForm();
    }

    public function setErrorMessages(array $messages) {
        if (count($messages) == 0) {
            return;
        }
        $html = '<div class="error">';
        foreach ($messages as $message) {
            $html .= '<strong>' . __('ERROR', LIVEAGENT_PLUGIN_NAME) . '</strong>: ' . $message . '<br />';
        }
        $html .='</div>';
        $this->addVariable('errorMessages', $html);
    }

    public function setInfoMessages(array $messages) {
        if (count($messages) == 0) {
            return;
        }
        $html = '<div class="updated">';
        foreach ($messages as $message) {
            $html .= '<p>' . $message . '</p>';
        }
        $html .='</div>';
        $this->addVariable('infoMessages', $html);
    }

    protected function initForm() {
    }

    protected abstract function getTemplateFile();

    protected function getOption($name) {
        return get_option($name);
    }

    protected function addTextArea($name, $value, $rows=1, $cols=10, $class = '') {
        $this->addVariable($name, '<textarea id="'.$name.'" name="'.$name.'" cols="'.$cols.'" rows="'.$rows.'"  class="'.$class.'">'.$value.'</textarea>');
    }

    public function render($toVar = false) {
        $html = file_get_contents($this->getTemplateFile());
		foreach ($this->variables as $name => $value) {
			$html = str_replace('{'.$name.'}', $value, $html);
		}
		echo $html;
    }

	protected function addVariable($name, $value) {
	    if (isset($this->variables[$name])) {
	        $this->variables[$name] .= $value;
	    } else {
	        $this->variables[$name] = $value;
	    }
	}
}