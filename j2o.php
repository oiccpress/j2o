<?php

if(!function_exists('println')) {
    function println($msg) {
        echo $msg . PHP_EOL;
    }
}
if(!function_exists('printdebug')) {
    function printdebug($msg) {
        // TODO: Verbosity option
        echo "\e[1;33m" . $msg . "\e[0m" . PHP_EOL;
    }
}

println('j2o from OICC Press');

require_once(dirname(__FILE__) . '/classes/J2oObject.php');
require_once(dirname(__FILE__) . '/classes/J2oArticle.php');
require_once(dirname(__FILE__) . '/classes/J2oAffiliation.php');
require_once(dirname(__FILE__) . '/classes/J2oAuthor.php');
require_once(dirname(__FILE__) . '/classes/J2oText.php');

$arguments = $argv;
if(count($arguments) === 3) {
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
                            $articleEntries[] = $article = new J2oArticle($node);
                            // var_dump($article);
                            if($article->hasWarnings()) {
                                println('> Warnings total: ' . count($article->warnings));
                                $articleWarnings[] = count($article->warnings);
                            }
                            break;
                        default:
                            println('>> Unknown root element: ' . $node->nodeName);
                    }
                }

                println('----');

            }
        }
    }

    $html = ['<h1>J2o report</h1>'];
    $html[] = 'Max warnings: ' . max($articleWarnings) . '<br/>';
    $html[] = 'Avg warnings: ' . ( array_sum($articleWarnings) / count($articleWarnings) );
    $html[] = '<table>';
    foreach($articleEntries as $article) {
        $html[] = '<tr><td>';
        $html[] = '<details><summary>' . $article->title . '</summary>';
        $html[] = '<ul><li>' . implode('</li><li>', $article->warnings) . '</li></ul></details>';
        $html[] = '</td><td>' . count($article->warnings) . ' warnings</td></tr>';
    }
    $html[] = '</table>';
    file_put_contents('report.html', implode("", $html));

    println('-----');
    println('Max warnings: ' . max($articleWarnings));
    println('Avg warnings: ' . ( array_sum($articleWarnings) / count($articleWarnings) ));
    println('-----');

} else {
    println('Usage: php j2o.php in_dir out_dir');
}
