<?php

class J2oText extends J2oObject {

    public string $html;
    public array $files = [];

    public const SWAPPED_NODE_TYPES = [
        'sec' => 'section',
        'italic' => 'em',
        'bold' => 'strong',
        'caption' => 'figcaption',
        'label' => 'h3',
        'list' => 'ul',
        'underline' => 'u',
        'list-item' => 'li',
        'table-wrap-foot' => 'footer',
    ];

    public function __construct($node)
    {
        $this->html = $this->loadFrom($node);
    }

    public function loadMathNode($parent, $secNode) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'alternatives':
                    // This is MathML looking stuff we can just embed these days,
                    // or just include a script within OJS rendering of galley
                    $xml = '';
                    foreach($node->childNodes as $childNode){
                      $xml .= $node->ownerDocument->saveXml($childNode);
                    }
                    return '<' . $secNode . ' id="' . $parent->getAttribute('id') . '">' . $xml . '</' . $secNode . '>';
                    break;
            }
        }
        return '';
    }

    public function loadFrom($parent) {
        $html = [];
        foreach($parent->childNodes as $node) {
            if(array_key_exists($node->nodeName, static::SWAPPED_NODE_TYPES)) {
                $n = static::SWAPPED_NODE_TYPES[ $node->nodeName ];
                $html[] = '<' . $n . '>' . $this->loadFrom($node) . '</' .$n . '>';
            } else {
                switch($node->nodeName) {
                    case 'table': // Just let it pass through
                        $html[] = $node->ownerDocument->saveXml($node);
                        break;
                    case 'inline-formula':
                        $html[] = $this->loadMathNode($node, 'span');
                        break;
                    case 'disp-formula':
                        // Math
                        $html[] = $this->loadMathNode($node, 'section');
                        break;
                    case 'graphic':
                        $file = $node->getAttribute('xlink:href');
                        $fileparts = explode("/", $file);
                        $filename = array_pop( $fileparts );
                        $this->files[$file] = $filename; // Ask to include
                        $html[] = '<img src="' . $filename . '" />';
                        break;
                    case 'chem-struct-wrap':
                    case 'table-wrap':
                        $html[] = '<figure id="' . $node->getAttribute('id') . '">' . $this->loadFrom($node) . '</figure>';
                        break;
                    case 'fig':
                        $html[] = '<figure id="' . $node->getAttribute('id') . '">' . $this->loadFrom($node) . '</figure>';
                        break;
                    case 'xref':
                        $html[] = '<a href="#' . $node->getAttribute('rid') . '"><sup>' . $this->loadFrom($node) . '</sup></a>';
                        break;
                    case 'ext-link':
                        $html[] = '<a href="' . htmlspecialchars($node->getAttribute('xlink:href'), ENT_XML1) . '">' . $this->loadFrom($node) . '</a>';
                        break;
                    case '#text':
                        $html[] = htmlspecialchars($node->nodeValue, ENT_XML1);
                        break;
                    case 'title':
                        $html[] = '<h2>' . $node->nodeValue . '</h2>';
                        break;
                    case 'sc':
                        $html[] = '<span style="font-variant: small-caps;">' . $this->loadFrom($node) . '</span>';
                        break;
                    case 'p':
                    case 'sub':
                    case 'sup':
                        $html[] = '<' . $node->nodeName . '>' . $this->loadFrom($node) . '</' . $node->nodeName . '>';
                        break;
                    default:
                        $html[] = '<' . $node->nodeName . '>' . $this->loadFrom($node) . '</' . $node->nodeName . '>';
                        $this->logWarning('>> Unknown text element: ' . $node->nodeName);
                }
            }
        }
        return implode("\n", $html);
    }

}