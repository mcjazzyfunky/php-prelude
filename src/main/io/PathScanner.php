<?php

namespace prelude\io;

require_once __DIR__ . '/Files.php';
require_once __DIR__ . '/../util/Seq.php';

use InvalidArgumentException;
use prelude\util\Seq;

class PathScanner {
    private $recursive;
    private $listPaths;
    private $fileIncludeFilter;
    private $fileExcludeFilter;
    private $dirIncludeFilter;
    private $dirExcludeFilter;
    private $linkIncludeFilter;
    private $linkExcludeFilter;

    private function __construct() {
        $defaultFilter = self::createFilter(false);
        
        $this->recursive = false;
        $this->absolutePaths = false;
        $this->listPaths = false;
        $this->fileIncludeFilter = $defaultFilter;
        $this->fileExcludeFilter = $defaultFilter;
        $this->dirIncludeFilter = $defaultFilter;
        $this->dirExcludeFilter = $defaultFilter;
        $this->linkIncludeFilter = $defaultFilter;
        $this->linkExcludeFilter = $defaultFilter;
     }
    
    function recursive($recursive = true) {
        if (!is_bool($recursive)) {
            throw new InvalidArgumentException(
                '[PathScanner#recursive] First argument $recursive must be boolean');
        }
        
        $ret = clone $this;
        $ret->recursive = $recursive;
        return $ret;
    }

    function absolutePaths($absolutePaths = true) {
        if (!is_bool($absolutePaths)) {
            throw new InvalidArgumentException(
                '[PathScanner#absolutePaths] First argument $absolutePaths must be boolean');
        }
        
        $ret = clone $this;
        $ret->absolutePaths = $absolutePaths;
        return $ret;
    }

    function listPaths($listPaths = true) {
        if (!is_bool($listPaths)) {
            throw new InvalidArgumentException(
                '[PathScanner#listPaths] First argument $listPaths must be boolean');
        }
        
        $ret = clone $this;
        $ret->listPaths = $listPaths;
        return $ret;
    }

    function includeFiles($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#includeFiles] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->fileIncludeFilter = self::createFilter($select);
        return $ret;
    }

    function excludeFiles($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#excludeFiles] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->fileExcludeFilter = self::createFilter($select);
        return $ret;
    }

    function includeDirs($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#includeDirs] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->dirIncludeFilter = self::createFilter($select);
        return $ret;
    }

    function excludeDirs($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#excludeDirs] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->dirExcludeFilter = self::createFilter($select);
        return $ret;
    }

    function includeLinks($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#includeLinks] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->linkIncludeFilter = $select;
        return $ret;
    }

    function excludeLinks($select = true) {
        if (!self::isValidSelectArgument($select)) {
            throw new InvalidArgumentException(
                '[PathScanner#excludeLinks] First argument $select must '
                . 'either be boolean or a callable or a string or an array '
                . 'of strings and/or callables');
        }

        $ret = clone $this;
        $ret->linkExcludeFilter = self::createFilter($select);
        return $ret;
    }

    function autoTrim($autoTrim) {
         if (!is_bool($autoTrim)) {
            throw new InvalidArgumentException(
                '[PathScanner#recursive] First argument $autoTrim must be boolean');
        }

        $ret = clone $this; 
        $ret->autoTrim = $autoTrim;
        return $ret;
    }

    function scan($dir) {
         if (!is_string($dir) && !($dir instanceof File)) {
            throw new InvalidArgumentException(
                '[PathScanner#scan] First argument $dir must be a string or a File object');
        }
        
        return new Seq(function () use ($dir) {
            $parentPath =
                is_string($dir)
                ? $dir
                : $dir->getPath();
            
            $items = scandir($parentPath, SCANDIR_SORT_ASCENDING);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $path = Files::combinePaths($parentPath, $item);
                
                
                if ($this->absolutePaths && !Files::isAbsolutePath($path)) {
                    $path = Files::combinePaths(getcwd(), $path);
                }
              
                $file = new File($path);
                
                if ($this->fileIsIncluded($file)) {
                    if ($this->listPaths) {
                        yield $path;
                    } else {
                        yield $file;
                    }
                }
                
                if ($this->recursive && $file->isDir()) {
                    $subitems = $this->scan($path);
                    
                    foreach ($subitems as $subitem) {
                        yield $subitem;
                    }
                }
            }
        });
    }
    
    static function create() {
        return new self();
    }
    
    private static function isValidSelectArgument($select) {
        $ret = false;
        
        if (is_bool($select)) {
            $ret = true;
        } else if (is_string($select)) {
            $ret = true;
        } else if (is_callable($select)) {
            $ret = true;
        } else if (is_array($select)) {
            $ret = true;
            
            foreach ($select as $constraint) {
                if (!is_string($constraint) && !is_callable($constraint)) {
                    $ret = false;
                    break;
                }
            }
        }
        
        return $ret;
    }
    
    private static function createFilter($select) {
        $ret = null;
        
        if (is_bool($select)) {
            $ret = function () use ($select) {
                return $select;
            };
        } else if (is_string($select)) {
            $ret = function ($file) use ($select) {
                return fnmatch($select, $file->getPath());
            };
        } else if (is_callable($select)) {
            $ret = $select;
        } else if (is_array($select)) {
            $ret = function ($file) use ($select) {
                $result = false;
                
                foreach ($select as $constraint) {
                    if (is_callable($constraint)) {
                        if ($constraint($file)) {
                            $result = true;
                            break;
                        }
                    } else if (is_string($constraint)) {
                        if (fnmatch($constraint, $file->getPath())) {
                            $result = true;
                            break;
                        }       
                    }
                }
                
                return $result;
            };
        } else {
            throw new Exception("[PathScanner#createFilter] This case should never happen");
        }
        
        return $ret;
    }
    
    private function fileIsIncluded($file) {
        $ret = false;
        $isFile = $file->isFile();
        $isDir = !$isFile && $file->isDir();
        $isLink = !$isFile && !$isDir && $file->isLink();
        
        if ($isFile) {
            $ret = $this->fileIncludeFilter->__invoke($file)
                && !$this->fileExcludeFilter->__invoke($file);
        }
        
        if ($isDir) {
            $ret = $this->dirIncludeFilter->__invoke($file)
                && !$this->dirExcludeFilter->__invoke($file);
        }
        
        if ($isLink) {
            if ($ret) {
                $ret = !$this->linkExcludeFilter->__invoke($file);
            } else {
                $ret = $this->linkIncludeFilter->__invoke($file)
                    && !$this->linkExcludeFilter->__invoke($file);
            }
        }
    
        return $ret;                        
    }
}