<?php

class J2oReference extends J2oObject {

    public string $year, $volume, $issue, $fpage, $lpage, $doi, $edition,
        $source, $title, $publisherName, $publisherLocation, $collab;
    public array $authors = [];
    public J2oText $content;

    public function __construct($node)
    {
        $this->loadNode($node);
    }

    public function loadNode($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'label':
                case '#text':
                    break;
                case 'mixed-citation':
                    $this->loadMixedC($node);
                    break;
                default:
                    $this->logWarning('>> Unknown reference element: ' . $node->nodeName);
            }
        }
    }

    public function loadPersonGroup($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'etal':
                case '#text':
                    break;
                case 'collab':
                    $this->collab = $node->nodeValue;
                    break;
                case 'name':
                    $name = [ null, null, null ];
                    foreach($node->childNodes as $child) {
                        switch($child->nodeName) {
                            case '#text':
                                break;
                            case 'surname':
                                $name[1] = $child->nodeValue;
                                break;
                            case 'given-names':
                                $name[0] = $child->nodeValue;
                                break;
                            case 'suffix':
                                $name[2] = $child->nodeValue;
                                break;
                            default:
                                $this->logWarning('>> unknown person-group name element ' . $child->nodeName);
                        }
                    }
                    $this->authors[] = $name;
                    break;
                default:
                    $this->logWarning('>> Unknown person-group element: ' . $node->nodeName);
            }
        }
    }

    public function loadMixedC($parent) {
        $type = $parent->getAttribute('publication-type');
        if($type == 'other') {
            $this->content = $this->subobject(new J2oText($parent));
            return;
        }

        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'comment':
                case '#text':
                    break;
                case 'pub-id':
                    $type = $node->getAttribute('pub-id-type');
                    switch($type) {
                        case 'doi':
                            $this->doi = $node->nodeValue;
                            break;
                        default:
                            $this->logWarning('>>> Unknown pub-id ' . $type);
                    }
                    break;
                case 'person-group':
                    $this->loadPersonGroup($node);
                    break;
                case 'article-title':
                    $this->title = $node->nodeValue;
                    break;
                case 'publisher-name':
                    $this->publisherName = $node->nodeValue;
                    break;
                case 'publisher-loc':
                    $this->publisherLocation = $node->nodeValue;
                    break;
                case 'issue':
                case 'fpage':
                case 'lpage':
                case 'volume':
                case 'collab':
                case 'source':
                case 'edition':
                case 'year':
                    $name = $node->nodeName;
                    $this->$name = $node->nodeValue;
                    break;
                default:
                    $this->logWarning('>> Unknown reference mixed-citation element: ' . $node->nodeName);
            }
        }
    }


}