<?php

class J2oAuthor extends J2oObject {

    public ?string $surname = 'Unknown', $givenNames = null, $email = null;
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

    /**
     * This function converts an array of authors into a HTML rendered block of information
     * about those authors.
     * 
     * This code is based on OICC's old WordPress->OJS script which was not made public
     * 
     * @param J2oAuthor[] $authors
     */
    public static function toHTML($authors) {

        ob_start();

		?>
			<ul class="item-list">
	        <?php

	        $affiliations = [];

	        foreach($authors as $author):
	            $content = $author->affiliations;
	            $affiliation_line = [];
                $author_affs = [];
	            foreach($content as $line) {
	                $line = trim($line->string());
                    $author_affs[] = $line;
	                if(($i = array_search($line, $affiliations)) === false) {
	                    $affiliations[] = $line;
	                    $affiliation_line[] = count($affiliations);
	                } else {
	                    $affiliation_line[] = $i + 1;
	                }
	            }
	            $family_name = $author->surname;
	            $first_name = $author->givenNames;
	            $author_name = $first_name . ' ' . $family_name;
	            $corresponding = $author->corresponding;
	        ?>
	        <li>
	            <?= htmlentities($author_name, ENT_XML1); ?>
	            <?php if($corresponding): ?>
	            <abbr title="This is the corresponding author for this article">*</abbr>
	            <?php endif; ?>
	            <?php if($corresponding && $email = $author->email): ?>
	                <a href="mailto:<?= $email; ?>" class="tiny-icon email-link mx-1" title="Email <?= htmlentities($author_name, ENT_XML1); ?>">
	                    Email
	                </a>
	            <?php endif; ?>
	            <?php if(!empty($affiliation_line)): ?>
	            <sup aria-label="Affiliated with <?= htmlentities(implode(" and", $author_affs), ENT_XML1); ?>">
	                <?= implode(", ", $affiliation_line); ?>
	            </sup>
	            <?php endif; ?>
	        </li>
	        <?php endforeach; ?>
	    </ul>
	    <?php if(!empty($affiliations)): $tag = count($affiliations) == 1 ? 'ul' : 'ol'; ?>
	    <<?= $tag; ?> class="affiliations" aria-hidden="true">
	        <?php foreach($affiliations as $affiliation) {
	            echo '<li>' . htmlentities($affiliation, ENT_XML1) . '</li>';
	        } ?>
	    </<?= $tag; ?>>
	    <?php endif;

		return ob_get_clean();

    }

}