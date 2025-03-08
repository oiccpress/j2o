<?php

class J2oArticle extends J2oObject {

    public string $doi, $title, $articleType, $volume, $issue, $pdf;
    public bool $openAccess = false;
    public array $keywords = [], $authors = [], $subjects = [], $references = [];
    public J2oText $abstract, $acknowledgement;
    public DateTime $published, $acceptedDate, $receivedDate;

    public function __construct( public $filepath, $node ){
        $this->loadFrom($node);
    }

    public function loadFrom($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'front':
                    $this->loadFrontNode($node);
                    break;
                case 'body':
                    printdebug('>> Ignoring body');
                    break;
                case 'back':
                    $this->loadBackNode($node);
                    break;
                default:
                    $this->logWarning('>> Unknown article element: ' . $node->nodeName);
            }
        }
    }

    public function loadBackNode($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'ref-list':
                    $this->loadRefList($node);
                    break;
                case 'ack':
                    $this->acknowledgement = $this->subobject(new J2oText($node));
                    break;
                case 'glossary':
                case 'app-group':
                case 'notes':
                    printdebug('>> skipping ' . $node->nodeName);
                    break;
                default:
                    $this->logWarning('>> Unknown back element: ' . $node->nodeName);
            }
        }
    }

    public function loadRefList($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'title':
                case '#text':
                    break;
                case 'ref':
                    $this->references[] = $this->subobject(new J2oReference($node));
                    break;
                default:
                    $this->logWarning('>> Unknown ref-list element: ' . $node->nodeName);
            }
        }
    }

    public function loadFrontNode($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'notes':
                    printdebug('>> skipping notes');
                    break;
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

    public function loadDate($parent) {
        $year = $month = $day = 0;
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'year':
                    $year = $node->nodeValue;
                    break;
                case 'month':
                    $month = $node->nodeValue;
                    break;
                case 'day':
                    $day = $node->nodeValue;
                    break;
                default:
                    $this->logWarning('>> Unknown date element: ' . $node->nodeName);
            }
        }
        $val = new DateTime();
        $val->setDate($year, $month, $day);
        return $val;
    }

    public function loadArticleMeta($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'author-notes':
                case 'notes':
                case 'elocation-id':
                case 'permissions':
                    printdebug('> skipping ' . $node->nodeName);
                    break;
                case 'custom-meta-group':
                    $this->loadCustomMeta($node);
                    break;
                case 'pub-date':
                    switch($node->getAttribute("publication-format")) {
                        case 'electronic':
                            $this->published = $this->loadDate($node);
                            break;
                        default:
                            printdebug('>> Ignoring date for ' . $node->getAttribute("publication-format") . ' publication');
                    }
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
                            case '#text':
                                break;
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
                            case '#text':
                                break;
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
                case 'history':
                    $this->loadHistory($node);
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

    public function loadCustomMeta($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'custom-meta':
                    $name = $node->getElementsByTagName('meta-name')[0]->nodeValue;
                    $value = $node->getElementsByTagName('meta-value')[0]->nodeValue;
                    if(in_array($name, [
                            'publisher-imprint-name', 'volume-issue-count', 'issue-article-count',
                            'issue-toc-levels', 'issue-copyright-holder', 'issue-toc-levels',
                            'issue-copyright-holder', 'issue-copyright-year', 'article-contains-esm',
                            'article-numbering-style', 'article-registration-date-year', 'article-registration-date-month',
                            'article-registration-date-day', 'toc-levels', 'volume-type', 'journal-type', 'numbering-style',
                            'article-grants-type', 'metadata-grant', 'abstract-grant', 'journal-product',
                            'article-type', 'journal-subject-collection', 'issue-type', 'pdf-type',
                            'target-type', 'article-toc-levels', 'bodypdf-grant', 'bodyhtml-grant',
                            'bibliography-grant', 'online-first', 'esm-grant',
                        ])) {
                        continue; // Ignore
                    }
                    switch($name) {
                        case 'open-access':
                            if($value == 'true') {
                                $this->openAccess = true;
                            }
                            break;
                        case 'pdf-file-reference':
                            $this->pdf = $value;
                            break;
                        case 'journal-subject-primary':
                        case 'journal-subject-secondary':
                            $this->subjects[] = $value;
                            break;
                        default:
                            $this->logWarning('>>> unknown meta key ' . $name . ' => ' . $value);
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown custom-meta element: ' . $node->nodeName);
            }
        }
    }

    public function loadHistory($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'date':
                    $type = $node->getAttribute('date-type');
                    switch($type) {
                        case '#text':
                            break;
                        case 'online':
                        case 'registration':
                            printdebug('>> ignoring ' . $type . ' date');
                            break;
                        case 'accepted':
                            $this->acceptedDate = $this->loadDate($node);
                            break;
                        case 'received':
                            $this->receivedDate = $this->loadDate($node);
                            break;
                        default:
                            $this->logWarning('>> Unknown date type: ' . $type);
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown history element: ' . $node->nodeName);
            }
        }
    }

    public function loadKwdGroup($parent) {
        $type = '';
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
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
                case '#text':
                    break;
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
                case '#text':
                    break;
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