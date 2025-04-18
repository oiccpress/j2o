<?php

class J2oArticle extends J2oObject {

    public string $doi, $title, $articleType, $volume, $issue, $pdf, $journalId, $order;
    public bool $openAccess = false;

    public ?string $fpage = null, $lpage = null;

    /**
     * @param J2oAuthor[]
     */
    public array $authors = [];

    /**
     * @param J2oReference[]
     */
    public array $references = [];

    public array $notes = [];
    
    public array $keywords = [], $subjects = [];
    public ?J2oText $abstract = null, $acknowledgement = null, $body = null;
    public ?DateTime $published = null, $acceptedDate = null, $receivedDate = null;

    public function __construct( public $filepath, $node ){
        $this->loadFrom($node);
    }

    function relativePath($from, $to, $separator = DIRECTORY_SEPARATOR)
    {
        return dirname($from) . DIRECTORY_SEPARATOR . $to;
    }

    public function getIssueKey() {
        return 'journal-' . $this->journalId . '-vol-' . $this->volume . '-issue-' . $this->issue;
    }

    public function getHTML() {
        $out = [];
        if($this->abstract) $out[] = $this->abstract->html;
        if($this->body) $out[] = $this->body->html;
        if($this->acknowledgement) $out[] = $this->acknowledgement->html;
        foreach($this->notes as $note) {
            $out[] = $note->html;
        }
        $html = implode('<hr/>', $out);
        if(!empty($html)) {
            // Add title in
            $outhtml = ['<h1>' . $this->title . '</h1>' ];

            if(!empty($this->authors)) {
                $outhtml[] = J2oAuthor::toHTML($this->authors);
            }

            $outhtml[] = $html;

            if(!empty($this->references)) {
                $outhtml[] = '<hr/><h2>References</h2>';
                $outhtml[] = J2oReference::toHTML($this->references);
            }

            return implode(PHP_EOL, $outhtml);
        }
    }

    public function outputArticle($output_file, $id) {
        fputs($output_file, '<article xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" locale="en" ');
        if($this->acceptedDate) {
            fputs($output_file, 'data_accepted="' . $this->acceptedDate->format('Y-m-d') . '" ');
        }
        fputs($output_file, 'current_publication_id="' . $id . '"  status="3" submission_progress="" stage="production">' . PHP_EOL);

        fputs($output_file, '<id type="doi" advice="update">' . $this->doi . '</id>' . PHP_EOL);

        // Files to add
        // Add PDF
        if($this->pdf && $this->openAccess) {
            $file_id = $id+1;
            $stage = 'submission';
            $dataset = 'Article Text';
            fputs($output_file, '<submission_file xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="' . $file_id . '" file_id="' . $file_id . '" stage="' . $stage . '" viewable="false" genre="' . $dataset . '" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);

            fputs($output_file, '<name filepath="' . $id . '.pdf" locale="en">article.pdf</name>' . PHP_EOL);
            $ext = 'pdf';
            fputs($output_file, '<file id="' . $file_id . '" filesize="' . filesize($this->pdf) . '" extension="' . $ext . '">');
            fputs($output_file, '<embed encoding="base64">' . base64_encode(file_get_contents($this->pdf)) . '</embed>' . PHP_EOL);

            fputs($output_file, '</file></submission_file>' . PHP_EOL);
        }
        $html = $this->getHTML();
        if($this->openAccess && !empty($html)) {
            $html_id = $id * 1000;
            $stage = 'submission';
            $dataset = 'Article Text';
            fputs($output_file, '<submission_file xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="' . $html_id . '" file_id="' . $html_id . '" stage="' . $stage . '" viewable="false" genre="' . $dataset . '" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);

            fputs($output_file, '<name filepath="' . $id . '.html" locale="en">article.html</name>' . PHP_EOL);
            $ext = 'html';
            fputs($output_file, '<file id="' . $file_id . '" filesize="' . strlen($html) . '" extension="' . $ext . '">');
            fputs($output_file, '<embed encoding="base64">' . base64_encode($html) . '</embed>' . PHP_EOL);

            fputs($output_file, '</file></submission_file>' . PHP_EOL);

            if($this->body) {
                $i = 1;
                foreach($this->body->files as $file => $filename) {
                    $stage = 'dependent';
                    $dataset = 'Image';
                    $file_id = ($id * 1000) + $i;
                    $i += 1;
                    $file = $this->relativePath($this->filepath, $file);
                    fputs($output_file, '<submission_file xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="' . $file_id . '" file_id="' . $file_id . '" stage="' . $stage . '" viewable="false" genre="' . $dataset . '" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);

                    fputs($output_file, '<name filepath="' . $id . '.pdf" locale="en">' . $filename . '</name>' . PHP_EOL);
                    $ext = explode(".", $file);
                    $ext = array_pop($ext);
                    fputs($output_file, '<file id="' . $file_id . '" filesize="' . filesize($file) . '" extension="' . $ext . '">');
                    fputs($output_file, '<embed encoding="base64">' . base64_encode(file_get_contents($file)) . '</embed>' . PHP_EOL);

                    fputs($output_file, '</file></submission_file>' . PHP_EOL);
                }
            }

        }

        $primary_contact = '';
        foreach($this->authors as $k => $author) {
            if($author->corresponding) {
                $primary_contact = 'primary_contact_id="' . (($id*1000) + $k) . '" ';
            }
        }

        fputs($output_file, '<publication xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="1" status="3" url_path="" seq="' . $this->order . '" access_status="0" ' . $primary_contact . ' date_published="' . $this->published->format('Y-m-d') . '" section_ref="'. J2oIssue::slugify($this->articleType) . '" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);

        fputs($output_file, '<title locale="en">' . htmlspecialchars($this->title, ENT_XML1) . '</title>' . PHP_EOL);
        if($this->abstract) {
            fputs($output_file, '<abstract locale="en">' . $this->abstract->html . '</abstract>' . PHP_EOL);
        }

        if(!empty($this->keywords)) {
            fputs($output_file, '<keywords locale="en">' . PHP_EOL);
            foreach($this->keywords as $keyword) {
                fputs($output_file, '<keyword>' . htmlspecialchars($keyword, ENT_XML1) . '</keyword>' . PHP_EOL);
            }
            fputs($output_file, '</keywords>' . PHP_EOL);
        }
        if(!empty($this->authors)) {
            fputs($output_file, '<authors xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);
            foreach($this->authors as $k => $author) {
                fputs($output_file, $author->ojs( $k, (($id*1000) + $k) ) . PHP_EOL);
            }
            fputs($output_file, '</authors>' . PHP_EOL);
        }
        if(!empty($this->subjects)) {
            fputs($output_file, '<subjects locale="en">' . PHP_EOL);
            foreach($this->subjects as $keyword) {
                fputs($output_file, '<subject>' . htmlspecialchars($keyword, ENT_XML1) . '</subject>' . PHP_EOL);
            }
            fputs($output_file, '</subjects>' . PHP_EOL);
        }

        // Galleys
        if($this->pdf && $this->openAccess) {
            fputs($output_file, '<article_galley xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" locale="en" url_path="" approved="true" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">
        <id type="internal" advice="ignore">' . $id . '</id>
        <name locale="en">' . 'PDF' . '</name>
        <seq>0</seq>
        <submission_file_ref id="' . ($id+1) . '"/>
        </article_galley>' . PHP_EOL);
        }
        if($this->body && !empty($html) && $this->openAccess) {
            fputs($output_file, '<article_galley xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" locale="en" url_path="" approved="true"');
            if($this->fpage && $this->lpage) {
                fputs($output_file, ' pages="' . $this->fpage . '-' . $this->lpage . '"');
            }
            fputs($output_file, '>
        <id type="internal" advice="ignore">' . $id . '</id>
        <name locale="en">' . 'HTML' . '</name>
        <seq>1</seq>
        <submission_file_ref id="' . ($id*1000) . '"/>
        </article_galley>');
            foreach($this->body->files as $file => $filename) {
                $ext = explode(".", $file);
                $ext = array_pop($ext);
                $file = $this->relativePath($this->filepath, $file);
                fputs($output_file, '<covers><cover locale="en">');
                fputs($output_file, '<cover_image>' . $id . '.' . $ext . '</cover_image>');
                fputs($output_file, '<cover_image_alt_text>Cover Image</cover_image_alt_text>');
                fputs($output_file, '<embed encoding="base64">' . base64_encode(file_get_contents($file)) . '</embed>');
                fputs($output_file, '</cover></covers>' . PHP_EOL);
                break; // Only use the first one!
            }
        }

        if(!empty($this->references)) {
            fputs($output_file, '<citations>' . PHP_EOL);
            foreach($this->references as $reference) {
                fputs($output_file, '<citation>' . htmlentities($reference->toAPA(false), ENT_XML1) . '</citation>' . PHP_EOL);
            }
            fputs($output_file, '</citations>' . PHP_EOL);
        }

        // TODO: Page Numbers

        fputs($output_file, '</publication>' . PHP_EOL);

        fputs($output_file, '</article>' . PHP_EOL);
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
                    $this->body = $this->subobject( new J2oText($node) );
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
                    printdebug('>> skipping ' . $node->nodeName);
                    break;
                case 'notes':
                    $this->notes[] = $this->subobject(new J2oText($node));
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
                    $this->loadJournalMeta($node);
                    break;
                case 'article-meta':
                    $this->loadArticleMeta($node);
                    break;
                default:
                    $this->logWarning('>> Unknown front element: ' . $node->nodeName);
            }
        }
    }

    public function loadJournalMeta($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case 'journal-title-group':
                case 'issn':
                case 'publisher':
                case '#text':
                    break;
                case 'journal-id':
                    $type = $node->getAttribute('journal-id-type');
                    switch($type) {
                        case 'publisher-id':
                            $this->journalId = $node->nodeValue;
                            break;
                        default:
                            printdebug('>> ignoring journal-id type ' . $type);
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown journal-meta element: ' . $node->nodeName);
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
                        case 'manuscript':
                            $this->order = $node->nodeValue;
                            break;
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
                case 'fpage':
                    $this->fpage = $node->nodeValue;
                    break;
                case 'lpage':
                    $this->lpage = $node->nodeValue;
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
                            'issue-pricelist-year', 'issue-online-date-year', 'issue-online-date-month',
                            'issue-online-date-day', 'issue-print-date-year', 'issue-print-date-month',
                            'issue-print-date-day',
                        ])) {
                        continue 2; // Ignore
                    }
                    switch($name) {
                        case 'open-access':
                            if($value == 'true') {
                                $this->openAccess = true;
                            }
                            break;
                        case 'pdf-file-reference':
                            $this->pdf = $this->relativePath($this->filepath, $value);
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