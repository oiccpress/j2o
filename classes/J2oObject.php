<?php

class J2oObject {

    public $parent;
    public array $warnings = [];

    public function logWarning($msg) {
        $this->warnings[] = $msg;
        if($this->parent) {
            $this->parent->logWarning($msg);
        }
        println($msg);
    }

    public function hasWarnings() {
        return !empty($this->warnings);
    }

    public function subobject(J2oObject $obj) {
        $this->warnings = array_merge($this->warnings, $obj->warnings);
        $obj->parent = $this;
        return $obj;
    }

}