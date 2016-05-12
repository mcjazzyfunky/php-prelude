<?php

namespace prelude\io;

require_once(__DIR__ . '/../../main/util/Seq.php');
require_once(__DIR__ . '/../../main/io/FileRef.php');
require_once(__DIR__ . '/../../main/io/FileReader.php');

use prelude\util\Seq;

error_reporting(E_ALL);

class FileReaderTest extends \PHPUnit_Framework_TestCase {
    function testMethodWriteFullText() {
        $filename = tempnam(sys_get_temp_dir(), 'txt');
        
        file_put_contents($filename, "a\nb\nc");
        
        $content =
            FileReader::fromFile($filename)
                ->readFull();
   
        $this->assertEquals($content, "a\nb\nc");
    }
    
    function testMethodReadLines() {
        $filename = tempnam(sys_get_temp_dir(), 'txt');
        
        file_put_contents($filename, "a\r\nb\r\nc");
        
        $lines = FileReader::fromFile($filename)
            ->readLines()
            ->toArray();

        $this->assertEquals($lines, ['a', 'b', 'c']);
    }
}
