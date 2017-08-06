<?php

namespace Acme;

use Acme\Exceptions\InputParserException;
use Acme\Exceptions\InputReaderException;

class InputParser
{
    /**
     * @var array Integer values of the Roman numerals
     */
    protected $romansToNumbers = [
        'M' => 1000,
        'D' => 500,
        'C' => 100,
        'L' => 50,
        'X' => 10,
        'V' => 5,
        'I' => 1,
    ];

    /**
     * @var array Extra values of the specific combinations of Roman numerals, used in conversion method
     */
    protected $extraRomansToNumbers = [
        'CM' => 900,
        'CD' => 400,
        'XC' => 90,
        'XL' => 40,
        'IX' => 9,
        'IV' => 4,
    ];

    /**
     * @var array Map of the metals to their value
     */
    protected $metalValues = [];

    /**
     * @var string Current line we're on
     */
    protected $currentLine;

    /**
     * @var string Left side of the user input after being split by the word "is"
     */
    protected $leftSide;

    /**
     * @var string Right side of the user input after being split by the word "is"
     */
    protected $rightSide;

    /**
     * @var string Map of the foreign unit names to roman numerals
     */
    protected $assignments;

    /**
     * @var string Regex pattern to extract the value of the metal
     */
    protected $metalAssignmentPattern = '/^(\d+) credits$/i';

    /**
     * @var InputReaderContract Contract for accepting user input
     */
    protected $input;

    /**
     * InputParser constructor.
     *
     * @param InputReaderContract $input
     */
    public function __construct(InputReaderContract $input = null)
    {
        $this->setInput($input);
    }

    /**
     * Set input stream
     *
     * @param $input
     */
    public function setInput($input)
    {
        $this->input = $input;
    }

    /**
     * Process a direct single command
     *
     * @param string $command
     * @return null|string
     */
    public function processCommand(string $command)
    {
        $this->currentLine = $command;

        return $this->processCurrentLine();
    }

    /**
     * Process input and print the result on the screen
     */
    public function processInput()
    {
        try {
            while ($this->getInput()) {
                $result = $this->processCurrentLine();

                if ($result !== null) {
                    echo $result."\n";
                }
            }
        } catch (InputParserException $e) {
            echo "I have no idea what you are talking about";
        }
    }

    /**
     * Process current command line
     *
     * @return null|string
     */
    public function processCurrentLine()
    {
        if ($this->isQuestion()) {
            $answer = $this->answerQuestion();

            $this->clearCurrentLine();

            return $answer;
        } else {
            $this->processInstruction();

            $this->clearCurrentLine();
            
            return null;
        }
    }

    /**
     * Clear current command line
     */
    public function clearCurrentLine()
    {
        $this->currentLine = null;
    }

    /**
     * Continuously get new lines from the input until there is no more
     *
     * @return mixed
     * @throws InputReaderException
     */
    public function getInput()
    {
        if ($this->input === null) {
            throw new InputReaderException("You need to set up an input stream first via constructor or setInput() method");
        }

        return $this->input->getInput();
    }

    /**
     * Check if current line is a question
     *
     * @return bool
     */
    public function isQuestion()
    {
        return substr($this->getLine(), -1) == "?";
    }

    /**
     * Get current input line
     *
     * @return string
     */
    public function getLine()
    {
        return $this->currentLine ?: $this->currentLine = $this->input->getCurrentLine();
    }

    /**
     * Process the instruction given from the input
     *
     * @throws InputParserException
     */
    public function processInstruction()
    {
        $this->splitSentence();

        if ($this->isUnitAssignment()) {
            $this->assignNewUnit();
        } elseif ($this->isMetalAssignment()) {
            $this->assignNewMetal();
        } else {
            throw new InputParserException("Invalid input: you either need to assign a number to a Roman letter, or metal to credits.");
        }
    }

    /**
     * Answer the question given from the input
     *
     * @return string Answer
     * @throws InputParserException
     */
    public function answerQuestion()
    {
        $this->splitSentence();

        if ($this->isConvertingUnitsToInteger()) {
            $result = $this->convertUnitsToInteger(explode(' ', $this->rightSide));

            return $this->rightSide.' is '.$result;
        } elseif ($this->isConvertingMetalToCredits()) {
            return $this->convertMetalToCredits();
        } else {
            throw new InputParserException("Invalid input: you need to ask the right question.");
        }
    }

    /**
     * Split the sentence given in the input by the word "is"
     *
     * @throws InputParserException
     */
    protected function splitSentence()
    {
        if ($this->isValidStructure()) {
            $explode = explode(' is ', $this->getLine());

            $this->leftSide = trim($explode[0]);
            $this->rightSide = trim($explode[1], ' ?');
        } else {
            throw new InputParserException("Invalid input: missing the keyword \"is\" in the sentence.");
        }
    }

    /**
     * Check if given sentence has the word "is"
     *
     * @return bool
     */
    protected function isValidStructure()
    {
        return strpos($this->getLine(), ' is ') !== false;
    }

    /**
     * Check if given instruction is about assigning a Roman letter to a unit
     *
     * @return bool
     */
    protected function isUnitAssignment()
    {
        return isset($this->romansToNumbers[$this->rightSide]);
    }

    /**
     * Assign a Roman letter to a unit
     */
    protected function assignNewUnit()
    {
        $this->assignments[$this->leftSide] = $this->rightSide;
    }

    /**
     * Check if given instruction is about assigning a value to a metal
     *
     * @return bool
     */
    protected function isMetalAssignment()
    {
        return preg_match($this->metalAssignmentPattern, $this->rightSide) === 1;
    }

    /**
     * Assign a value to a metal
     */
    protected function assignNewMetal()
    {
        preg_match($this->metalAssignmentPattern, $this->rightSide, $matches);

        $creditsAmount = $matches[1];

        [$metal, $tokens] = $this->separateMetalFromUnits($this->leftSide);

        $unitsInCredits = $this->convertUnitsToInteger($tokens);

        $metalValue = $unitsInCredits > 0 ? $creditsAmount / $unitsInCredits : 0;

        $this->metalValues[$metal] = $metalValue;
    }

    /**
     * Separate metal name from the string of units
     *
     * @param string $string
     * @return array
     */
    protected function separateMetalFromUnits(string $string)
    {
        $tokens = explode(' ', $string);

        $metal = strtolower(trim(array_pop($tokens)));

        return [$metal, $tokens];
    }

    /**
     * Check if given instruction is a command to convert units to integers
     *
     * @return bool
     */
    protected function isConvertingUnitsToInteger()
    {
        return strpos($this->leftSide, 'how much') !== false;
    }

    /**
     * Convert given units to integer
     *
     * @param array $units
     * @return int
     * @throws InputParserException
     */
    protected function convertUnitsToInteger(array $units)
    {
        $romans = "";
        foreach ($units as $unit) {
            if (!isset($this->assignments[$unit])) {
                throw new InputParserException("Invalid input: unknown unit is found (".$unit.").");
            } else {
                $romans .= $this->assignments[$unit];
            }
        }

        return $this->convertRomansToInteger($romans);
    }

    /**
     * Check if given instruction is a command to convert amount of metal to credits
     *
     * @return bool
     */
    protected function isConvertingMetalToCredits()
    {
        return strpos($this->leftSide, 'how many') !== false;
    }

    /**
     * Convert amount of metal to credits
     *
     * @return string
     * @throws InputParserException
     */
    protected function convertMetalToCredits()
    {
        [$metal, $units] = $this->separateMetalFromUnits($this->rightSide);

        if (!isset($this->metalValues[$metal])) {
            throw new InputParserException("Invalid input: you haven't assigned metal value yet.");
        }

        $unitsInCredits = $this->convertUnitsToInteger($units);

        return $this->rightSide.' is '.($unitsInCredits * $this->metalValues[$metal]).' Credits';
    }

    /**
     * Convert Roman string to integer value
     *
     * @param string $romans
     * @return int
     */
    protected function convertRomansToInteger(string $romans)
    {
        $romansToNumbers = $this->getRomansToNumbersArray();

        $result = 0;
        foreach ($romansToNumbers as $key => $value) {
            while (strpos($romans, $key) === 0) {
                $result += $value;
                $romans = substr($romans, strlen($key));
            }
        }

        return $result;
    }

    /**
     * Get a map of roman letters to their values, including specific combinations
     *
     * @return array
     */
    protected function getRomansToNumbersArray()
    {
        $romansToNumbers = array_merge($this->romansToNumbers, $this->extraRomansToNumbers);

        arsort($romansToNumbers);

        return $romansToNumbers;
    }
}