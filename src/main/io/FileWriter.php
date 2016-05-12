<?php

namespace prelude\io;

require_once(__DIR__ . '/FileRef.php');
require_once(__DIR__ . '/IOException.php');
require_once(__DIR__ . '/../util/Seq.php');

use \IllegalArgumentException;
use \prelude\util\Seq;

class FileWriter {
    private $filerEF;
    
    private function __construct(FileRef $fileRef) {
        $this->fileRef = $fileRef;    
    }
    
    function writeFull($text) {
        if (!is_string($text)) {
            throw new InvalidArgumentException(
                '[FileWriter#writeFull] First argument $text must be a string');
        }
        
        $filename = $this->fileRef->getFilename();
        $context = $this->fileRef->getContext();
                
        $result = $context === null
            ? @file_put_contents(
                $filename,
                $text,
                0)
            : @file_put_contents(
                $filename,
                $text,
                0,
                $context);
        
        if ($result === false) {
            $message = error_get_last()['message'];
            throw new IOException($message);
        }
    }
    
    function writeLines(Seq $lines, $lineSeparator = "\r\n") {
        if (!is_string($lineSeparator)) {
            throw new IllegalArgumentException(
                '[FileWriter#writeLines] Second argument $lineSeparator must be a string');
        }
        
        $filename = $this->fileRef->getFilename();
        $context = $this->fileRef->getContext();
                
        $fhandle = $context === null
            ? @fopen(
                $filename,
                'wb',
                false)
            : @fopen(
                $filename,
                'wb',
                false,
                $context);
        
        if ($fhandle === false) {
            $message = error_get_last()['message'];
            throw new IOException($message);
        }
        
        foreach ($lines as $line) {
            foreach ([$line, $lineSeparator] as $s) {
                $result = fwrite($fhandle, $s);
            
                if ($result === false) {
                    $message = error_get_last()['message'];
                    @fclose($fhandle);
                    throw new IOException($message);
                }
            }
        }
        
        @fclose($fhandle);
    }
    
    static function fromFile($filename, array $context = null) {
         if (!is_string($filename)) {
            throw new InvalidArgumentException(
                '[FileWriter.fromFile] First argument $filename must be a string');
        }

        return new self(new FileRef($filename, $context));
    }
    
    static function fromFileRef(FileRef $fileRef) {
        return new self($fileRef);
    }    
}
