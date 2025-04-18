<?php

if(!function_exists('println')) {
    function println($msg) {
        echo $msg . PHP_EOL;
    }
}
if(!function_exists('printdebug')) {
    function printdebug($msg) {
        global $quiet_debug;
        if(!$quiet_debug) {
            echo "\e[1;33m" . $msg . "\e[0m" . PHP_EOL;
        }
    }
}

println('j2o from OICC Press');

require_once(dirname(__FILE__) . '/classes/J2oObject.php');
require_once(dirname(__FILE__) . '/classes/J2oArticle.php');
require_once(dirname(__FILE__) . '/classes/J2oAffiliation.php');
require_once(dirname(__FILE__) . '/classes/J2oAuthor.php');
require_once(dirname(__FILE__) . '/classes/J2oText.php');
require_once(dirname(__FILE__) . '/classes/J2oIssue.php');
require_once(dirname(__FILE__) . '/classes/J2oReference.php');

$arguments = $argv;
if(stripos($arguments[0], 'j2o.php') !== false) { // Remove self
    array_shift($arguments);
}

$quiet_debug = false;
if(in_array( '--quiet', $arguments ) ) {
    $quiet_debug = true;
    array_splice( $arguments, array_search('--quiet', $arguments), 1 );
}

$mode = 'xml';
if($arguments[0] === 'html') {
    // Html mode
    $mode = 'html';
    println('HTML mode activated');
    array_shift($arguments);
} elseif($arguments[0] === 'report') {
    // Report Only Mode
    $mode = 'report';
    println('Report-only mode activated');
    array_shift($arguments);
}


if(count($arguments) === 2) {

    $in_dir = $arguments[0];
    $out_dir = $arguments[1];

    println('IN: ' . $in_dir);
    println('OUT: ' . $out_dir);

    // Validation
    if(!is_dir($in_dir)) {
        die('Input directory is not valid');
    }
    @mkdir($out_dir);
    if(!is_dir($out_dir)) {
        die('Output directory is not valid');
    }

    $directoryIterator = new \RecursiveDirectoryIterator($in_dir);
    $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
    $articleEntries = [];
    $articleWarnings = [];
    $issueOutputs = [];

    foreach ($recursiveIterator as $file) {
        if ($file->isFile()) {
            $filepath = strval($file);
            if(preg_match('/\.(meta|xml)$/i', $filepath)) {

                println('> ' . $filepath);
                $document = new DOMDocument();
                $document->loadXML( file_get_contents($filepath) );

                foreach($document->childNodes as $node) {
                    if($node->nodeType != XML_ELEMENT_NODE) continue;
                    switch($node->nodeName) {
                        case '#text':
                            break;
                        case 'article':
                            $articleEntries[] = $article = new J2oArticle($filepath, $node);
                            // var_dump($article);
                            if($article->hasWarnings()) {
                                println('> Warnings total: ' . count($article->warnings));
                            }
                            $articleWarnings[] = count($article->warnings);
                            if(!array_key_exists($article->getIssueKey(), $issueOutputs)) {
                                $issueOutputs[ $article->getIssueKey() ] = new J2oIssue($article);
                            }
                            $issueOutputs[ $article->getIssueKey() ]->addArticle($article);
                            break;
                        default:
                            println('>> Unknown root element: ' . $node->nodeName);
                    }
                }

                println('----');

            }
        }
    }

    foreach($issueOutputs as $output) {
        if($mode === 'xml') {
            $output->outputIssue($out_dir);
        } elseif($mode === 'html') {
            $output->outputArticleHtml($out_dir);
        }
    }

    $html = ['<h1>J2o report</h1>'];
    $html[] = 'Total Articles: ' . count($articleEntries) . '<br/>';
    $html[] = 'Max warnings: ' . max($articleWarnings) . '<br/>';
    $html[] = 'Avg warnings: ' . ( array_sum($articleWarnings) / count($articleWarnings) );
    $html[] = '<table>';
    $html[] = '<thead>';
    $html[] = '<tr><th>Title / Summary</th><th>Warning Count</th><th>DOI</th><th>Galleys</th></tr>';
    $html[] = '</thead><tbody>';
    foreach($articleEntries as $article) {
        $html[] = '<tr><td>';
        $html[] = '<details><summary>' . $article->title . ' - ';
        $html[] = $article->volume . ' - ' . $article->issue . ' - ' . ($article->openAccess ? 'OpenAccess' : 'ClosedAccess');
        $html[] = '<br/>' . $article->filepath . '</summary>';
        $html[] = '<ul><li>' . implode('</li><li>', $article->warnings) . '</li></ul></details>';
        $html[] = '</td><td>' . count($article->warnings) . ' warnings</td>';
        $html[] = '<td>' . $article->doi . '</td>';
        $html[] = '<td>' . ($article->pdf ? 'PDF' : '') . '</td>';
        $html[] = '</tr>';
    }
    $html[] = '</tbody></table>';
    file_put_contents('report.html', implode("", $html));

    println('-----');
    println('Max warnings: ' . max($articleWarnings));
    println('Avg warnings: ' . ( array_sum($articleWarnings) / count($articleWarnings) ));
    println('-----');

} else {
    println('Usage: php j2o.php in_dir out_dir');
}
