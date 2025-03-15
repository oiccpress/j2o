<?php

class J2oReference extends J2oObject {

    public ?string $year = null, $volume = null, $issue = null, $fpage = null, $lpage = null, $doi = null, $edition,
        $source, $title = null, $publisherName = null, $publisherLocation = null, $collab;
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
                case 'elocation-id':
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

    /**
     * Convert this line to an APA-style output as requested by OICC
     * 
     * This is following https://www.mmu.ac.uk/sites/default/files/2024-03/APA%20Referencing%20Quick%20Guide%20v2.pdf
     * (the author of this package is not an academic so there may be inacuracies with this output)
     * 
     * If you need something else, you may need another function (sorry!)
     */
    public function toAPA($html = true) {

        $out = [];
        // $authors is an array of array of strings
        // each item is key 0: giveNane; key 1: surname; key 2: suffix
        if(count($this->authors) === 1) {
            $out[] =  $this->authors[0][1] . ' (' . $this->year . ')';
        } elseif(count($this->authors) === 2) {
            $out[] = $this->authors[0][1] . ' and ' . $this->authors[1][1] . ' (' . $this->year . ')';
        } elseif(count($this->authors) > 2) {
            $out[] = $this->authors[0][1] . ' et al. (' . $this->year . ')';
        } else {
            $out[] = 'Unknown (' . $this->year . ')';
        }

        // Article Title
        if($html) {
            $out[] = '<em>' . $this->title . '</em>';
        } else {
            $out[] = $this->title;
        }

        if($this->volume && $this->issue) {
            $out[] = $this->volume . '(' . $this->issue . ')';
        }
        if($this->fpage && $this->lpage) {
            $out[] = '(pp. ' . $this->fpage . '-' . $this->lpage . ')';
        }

        if($this->publisherName) {
            $out[] = $this->publisherName;
        }

        if($this->doi) {
            if($html) {
                $out[] = '<a href="https://doi.org/' . $this->doi . '" target="_blank">' . $this->doi . '</a>';
            } else{
                $out[] = 'https://doi.org/' . $this->doi;
            }
        }

        return implode(' ', $out);

    }

    /**
     * This function converts an array of references into a HTML rendered block
     * 
     * @param J2oReference[] $references
     */
    public static function toHTML($references) {

        $out = [];
        foreach($references as $reference) {
            $out[] = $reference->toAPA();
        }
        return '<ol><li>' . implode("</li><li>", $out) . '</li></ol>';

    }


}