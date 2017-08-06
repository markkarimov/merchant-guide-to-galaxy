<?php

namespace Tests;

use Acme\Exceptions\InputParserException;
use Acme\InputParser;
use PHPUnit\Framework\TestCase;

class InputParserTest extends TestCase
{
    public function test_assignment_for_I()
    {
        $inputParser = new InputParser();

        $inputParser->processCommand("glob is I");

        $this->assertEquals("glob is 1", $inputParser->processCommand("how much is glob?"));
    }

    public function test_assignment_for_I_and_V()
    {
        $inputParser = new InputParser();

        $inputParser->processCommand("glob is I");
        $inputParser->processCommand("prok is V");

        $this->assertEquals("glob prok is 4", $inputParser->processCommand("how much is glob prok?"));
    }

    public function test_assignment_for_metal_Silver()
    {
        $inputParser = new InputParser();

        $inputParser->processCommand("glob is I");
        $inputParser->processCommand("prok is V");
        $inputParser->processCommand("glob glob Silver is 34 Credits");
        
        $this->assertEquals("glob prok Silver is 68 Credits", $inputParser->processCommand("how many Credits is glob prok Silver?"));
    }

    public function test_wrong_command()
    {
        $inputParser = new InputParser();

        $this->expectException(InputParserException::class);

        $inputParser->processCommand("how much wood could a woodchuck chuck if a woodchuck could chuck wood?");
    }
}