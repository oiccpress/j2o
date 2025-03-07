<?php

class J2oAuthor extends J2oObject {

    public string $surname, $givenNames, $email;
    public bool $corresponding = false;

    public array $aff_id = [], $affiliations = [];

    public function __construct($node) {
        $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'name':
                    $this->loadName($node);
                    break;
                case 'address':
                    $this->loadAddress($node);
                    break;
                case 'xref':
                    $ref_type = $node->getAttribute("ref-type");
                    switch($ref_type) {
                        case 'corresp':
                            $this->corresponding = true;
                            break;
                        case 'aff':
                            $this->aff_id[] = $node->getAttribute('rid');
                            break;
                        default:
                            printdebug('>>> unknown author ref type ' . $ref_type);
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown author element: ' . $node->nodeName);
            }
        }
    }

    public function loadAddress($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'email':
                    $this->email = $node->nodeValue;
                    break;
                default:
                    $this->logWarning('>> Unknown author address element: ' . $node->nodeName);
            }
        }
    }

    public function loadName($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'surname':
                    $this->surname = $node->nodeValue;
                    break;
                case 'given-names':
                    $this->givenNames = $node->nodeValue;
                    break;
                default:
                    $this->logWarning('>> Unknown author name element: ' . $node->nodeName);
            }
        }
    }


}