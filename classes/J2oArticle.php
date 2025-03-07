<?php

class J2oArticle extends J2oObject {

    public string $doi, $title, $articleType, $volume, $issue;
    public array $keywords = [], $authors = [];
    public $abstract;

    public function __construct($node ){
        $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'front':
                    $this->loadFrontNode($node);
                    break;
                default:
                    $this->logWarning('>> Unknown article element: ' . $node->nodeName);
            }
        }
    }

    public function loadFrontNode($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'journal-meta':
                    printdebug('>> Skipping journal-meta: Journals should already be defined');
                    break;
                case 'article-meta':
                    $this->loadArticleMeta($node);
                    break;
                default:
                    $this->logWarning('>> Unknown front element: ' . $node->nodeName);
            }
        }
    }

    public function loadArticleMeta($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'elocation-id':
                    printdebug('> skipping ' . $node->nodeName);
                    break;
                case 'abstract':
                    $this->abstract = $this->subobject(new J2oText($node));
                    break;
                case 'volume':
                    $this->volume = $node->nodeValue;
                    break;
                case 'issue':
                    $this->issue = $node->nodeValue;
                    break;
                case 'article-categories':
                    foreach($node->childNodes as $child) {
                        switch($child->nodeName) {
                            case 'subj-group':
                                $this->loadSubjectGroup($child);
                                break;
                            default:
                                $this->logWarning('>> Unknown article-categories element: ' . $child->nodeName);
                        }
                    }
                    break;
                case 'title-group':
                    foreach($node->childNodes as $child) {
                        switch($child->nodeName) {
                            case 'article-title':
                                $this->title = $child->nodeValue;
                                break;
                            default:
                                $this->logWarning('>> Unknown title-group element: ' . $child->nodeName);
                        }
                    }
                    break;
                case 'article-id':
                    $id_type = $node->getAttribute('pub-id-type');
                    switch($id_type) {
                        case 'doi':
                            $this->doi = $node->nodeValue;
                            break;
                        default:
                            printdebug('>>> Skipping unknown pub id type: ' . $id_type);
                            break;
                    }
                    break;
                case 'contrib-group':
                    $this->loadContribGroup($node);
                    break;
                case 'kwd-group':
                    $this->loadKwdGroup($node);
                    break;
                default:
                    $this->logWarning('>> Unknown article-meta element: ' . $node->nodeName);
            }
        }
    }

    public function loadKwdGroup($parent) {
        $type = '';
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'title':
                    if($node->nodeValue == 'Keywords') {
                        $type = 'kw';
                    } else {
                        $this->logWarning('>> Unknown kwd-group title: ' . $node->nodeValue);
                    }
                    break;
                case 'kwd':
                    if($type == 'kw') {
                        $this->keywords[] = $node->nodeValue;
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown kwd-group element: ' . $node->nodeName);
            }
        }
    }

    public function loadContribGroup($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'aff':
                    $found = false;
                    $aff_id = $node->getAttribute("id");
                    foreach($this->authors as $author) {
                        if(in_array($aff_id, $author->aff_id)) {
                            $author->affiliations[] = $this->subobject( new J2oAffiliation($node) );
                            $found = true;
                        }
                    }
                    if(!$found) {
                        $this->logWarning('>> Aff not associated with author with ID of ' . $aff_id);
                    }
                    break;
                case 'contrib':
                    if($node->getAttribute("contrib-type") == "author") {
                        $this->authors[] = $this->subobject(new J2oAuthor($node));
                    } else {
                        $this->logWarning('>> Unknown contrib-group type: ' . $node->getAttribute("contrib-type"));
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown contrib-group element: ' . $node->nodeName);
            }
        }
    }

    public function loadSubjectGroup($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'subject':
                    if($parent->getAttribute("subj-group-type") == "heading") {
                        $this->articleType = $node->nodeValue;
                    } else {
                        $this->subjects[] = $node->nodeValue;
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown subject-group element: ' . $node->nodeName);
            }
        }
    }

}