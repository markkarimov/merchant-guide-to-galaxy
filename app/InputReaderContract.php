<?php


namespace Acme;

interface InputReaderContract
{
    public function getInput();

    public function getCurrentLine();
}