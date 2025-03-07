<?php

class J2oText extends J2oObject {

    public string $html;

    public function __construct($node)
    {
        $this->html = $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        $html = [];
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    $html[] = $node->nodeValue;
                    break;
                case 'title':
                    $html[] = '<h2>' . $node->nodeValue . '</h2>';
                    break;
                case 'sec':
                    $html[] = '<section>' . $this->loadFrom($node) . '</section>';
                    break;
                case 'p':
                case 'sup':
                    $html[] = '<' . $node->nodeName . '>' . $this->loadFrom($node) . '</' . $node->nodeName . '>';
                    break;
                default:
                    $this->logWarning('>> Unknown text element: ' . $node->nodeName);
            }
        }
        return implode("\n", $html);
    }

}