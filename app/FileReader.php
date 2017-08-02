<?php

namespace Acme;

class FileReader implements InputReaderContract
{
    /**
     * @var string File path
     */
    protected $file;

    /**
     * @var resource Open file handle
     */
    protected $handle;

    /**
     * @var string Current line in user input
     */
    protected $currentLine;

    /**
     * FileReader constructor.
     * @param $file string File path
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        $this->openFile();
    }

    /**
     * Open file
     *
     * @param string $mode
     */
    public function openFile($mode = "r")
    {
        $this->handle = fopen($this->file, $mode);
    }

    /**
     * Continuously get user input from the file until there is nothing left
     *
     * @return bool
     */
    public function getInput()
    {
        $line = fgets($this->handle);

        if ($line !== false) {
            $this->currentLine = trim($line);

            return true;
        } else {
            $this->currentLine = null;

            return false;
        }
    }

    /**
     * Get current line from user input
     *
     * @return string
     */
    public function getCurrentLine()
    {
        return trim($this->currentLine);
    }
}