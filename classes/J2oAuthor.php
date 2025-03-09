<?php

class J2oAuthor extends J2oObject {

    public ?string $surname, $givenNames = null, $email = null;
    public bool $corresponding = false;

    public array $aff_id = [], $affiliations = [];

    public function __construct($node) {
        $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'name':
                    $this->loadName($node);
                    break;
                case 'address':
                    $this->loadAddress($node);
                    break;
                case 'xref':
                    $ref_type = $node->getAttribute("ref-type");
                    switch($ref_type) {
                        case 'fn':
                            break;
                        case 'corresp':
                            $this->corresponding = true;
                            break;
                        case 'aff':
                            $this->aff_id[] = $node->getAttribute('rid');
                            break;
                        default:
                            $this->logWarning('>>> unknown author ref type ' . $ref_type);
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
                case '#text':
                    break;
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
                case '#text':
                    break;
                case 'surname':
                    $this->surname = $node->nodeValue;
                    break;
                case 'given-names':
                    $this->givenNames = $node->nodeValue;
                    break;
                default:
                    $this->logWarning('>>> Unknown author name element: ' . $node->nodeName);
            }
        }
    }

    public function ojs($k, $id) {

        $output = [ '<author corresponding="' . ($this->corresponding ? 'true' : 'false') .
             '" include_in_browse="true" user_group_ref="Author" seq="' . $k . '" id="' . $id . '">', ];

        $output[] = '<givenname locale="en">' . $this->givenNames . '</givenname>';
        $output[] = '<familyname locale="en">' . $this->surname . '</familyname>';

        $aff_text = [];
        foreach($this->affiliations as $aff) {
            $aff_text[] = $aff->string();
        }
        if(!empty($aff_text)) {
            $output[] = '<affiliation locale="en">' . implode("\n", $aff_text) . '</affiliation>';
        }

        if($this->email) {
            $output[] = '<email>' . $this->email . '</email>';
        }

        $output[] = '</author>';

        return "\t" . implode("\n\t", $output);

    }

}