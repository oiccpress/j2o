<?php

class J2oText extends J2oObject {

    public string $html;

    public const SWAPPED_NODE_TYPES = [
        'sec' => 'section',
        'italic' => 'em',
        'bold' => 'strong',
    ];

    public function __construct($node)
    {
        $this->html = $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        $html = [];
        foreach($parent->childNodes as $node) {
            if(array_key_exists($node->nodeName, static::SWAPPED_NODE_TYPES)) {
                $n = static::SWAPPED_NODE_TYPES[ $node->nodeName ];
                $html[] = '<' . $n . '>' . $this->loadFrom($node) . '</' .$n . '>';
            } else {
                switch($node->nodeName) {
                    case 'ext-link':
                        $html[] = '<a href="' . htmlspecialchars($node->getAttribute('xlink:href'), ENT_XML1) . '">' . $this->loadFrom($node) . '</a>';
                        break;
                    case '#text':
                        $html[] = htmlspecialchars($node->nodeValue, ENT_XML1);
                        break;
                    case 'title':
                        $html[] = '<h2>' . $node->nodeValue . '</h2>';
                        break;
                    case 'p':
                    case 'sub':
                    case 'sup':
                        $html[] = '<' . $node->nodeName . '>' . $this->loadFrom($node) . '</' . $node->nodeName . '>';
                        break;
                    default:
                        $this->logWarning('>> Unknown text element: ' . $node->nodeName);
                }
            }
        }
        return implode("\n", $html);
    }

}