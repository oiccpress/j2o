<?php

class J2oIssue {

    public string $volume, $issue;
    public DateTime $published;

    /**
     * @param J2oIssue[] Articles
     */
    public array $articles = [];

    public function __construct(J2oArticle $firstArticle) {
        $this->volume = $firstArticle->volume;
        $this->issue = $firstArticle->issue;
        $this->published = $firstArticle->published;
    }

    public function addArticle(J2oArticle $article) {
        $this->articles[] = $article;
    }

    public function getIssueKey() {
        return 'vol-' . $this->volume . '-issue-' . $this->issue;
    }

    public function outputIssue($out_dir) {
        $output_file = fopen( $out_dir . '/' . $this->getIssueKey() . '-nativexml.xml', 'w' );
        $this->outputIssueHeader($output_file);

        $this->outputIssueFooter($output_file);
    }

    public static function slugify($text, string $divider = '-') {
        // Thanks https://stackoverflow.com/a/2955878/230419
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }


    public function getSections() {
        $sections = [];
        foreach($this->articles as $article) {
            $sectionTitle = $article->articleType;
            $sections[ static::slugify($sectionTitle) ] = $sectionTitle;
        }
        return $sections;
    }

    public function outputIssueHeader($output_file) {
        fputs($output_file, '<?xml version="1.0"?>' . PHP_EOL);
        fputs($output_file, '<issues xmlns="http://pkp.sfu.ca" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . PHP_EOL);

        fputs($output_file, '<issue xmlns="http://pkp.sfu.ca" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" published="1" current="0" access_status="1" url_path="" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">' . PHP_EOL);

        fputs($output_file, '<id type="internal" advice="ignore">' . $this->getIssueKey() . '</id>' . PHP_EOL);
        fputs($output_file, '<issue_identification>' . PHP_EOL);

        fputs($output_file, '<volume>' . $this->volume . '</volume>' . PHP_EOL);
        fputs($output_file, '<number>' . $this->issue . '</number>' . PHP_EOL);
        fputs($output_file, '<year>' . $this->published->format('Y') . '</year>' . PHP_EOL);

        fputs($output_file, '</issue_identification>' . PHP_EOL);

        fputs($output_file, '<sections>' . PHP_EOL);
        foreach($this->getSections() as $key => $section) {
            fputs($output_file, '<section ref="' . $key . '" meta_indexed="1" meta_reviewed="1" abstracts_not_required="0" hide_title="0" hide_author="0" abstract_word_count="2000">' . PHP_EOL);
            fputs($output_file, '<abbrev locale="en">' . $key . '</abbrev>' . PHP_EOL);
            fputs($output_file, '<title locale="en">' . $section . '</title>' . PHP_EOL);
            fputs($output_file, '</section>' . PHP_EOL);
        }
        fputs($output_file, '</sections>' . PHP_EOL);

        return $output_file;
    }

    public static function outputIssueFooter($output_file) {
        fputs($output_file, '</issue></issues>');
    }

}