<?php

namespace Supports\Config;

use Noodlehaus\Exception\EmptyDirectoryException;
use Noodlehaus\Parser\ParserInterface;

class ArrayConfig extends \Noodlehaus\Config
{

    /**
     * Loads configuration from file.
     *
     * @param  string|array     $path   Filenames or directories with configuration
     * @param  ParserInterface  $parser Configuration parser
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     */
    protected function loadFromFile($path, ParserInterface $parser = null)
    {
        $paths      = $this->getValidPath($path);
        $this->data = [];

        foreach ($paths as $path) {
            if ($parser === null) {

                list($filename, $extension) = $this->getFileInfo($path);

                // Skip the `dist` extension
                if ($extension === 'dist') {
                    $extension = array_pop($parts);
                }

                // Get file parser
                $parser = $this->getParser($extension);

                // Try to load file
                $this->data[$filename] = $parser->parseFile($path);

                // Clean parser
                $parser = null;
            } else {
                // Try to load file using specified parser
                list($filename, $extension) = $this->getFileInfo($path);
                $this->data[$filename] = $parser->parseFile($path);
            }
        }
    }

    protected function getFileInfo($path)
    {
        $info      = pathinfo($path);
        $parts     = explode('.', $info['basename']);
        $extension = array_pop($parts);
        $filename = $info['filename'];

        // Skip the `dist` extension
        if ($extension === 'dist') {
            $extension = array_pop($parts);
        }

        return [$filename, $extension];
    }

}