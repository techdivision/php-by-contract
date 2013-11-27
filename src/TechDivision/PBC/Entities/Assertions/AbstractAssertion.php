<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 19.06.13
 * Time: 16:20
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Assertions;


use TechDivision\PBC\Exceptions\ParserException;
use TechDivision\PBC\Interfaces\Assertion;

/**
 * Class Assertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
abstract class AbstractAssertion implements Assertion
{
    /**
     * @var boolean
     */
    protected $inverted;

    /**
     *
     */
    public function __construct()
    {
        $this->inverted = false;

        if (!$this->isValid()) {

            throw new ParserException('Could not parse assertion string ' . $this->getString());
        }
    }

    /**
     *
     */
    public function getInvertString()
    {
        // Invert this instance
        $self = $this;

        $self->invert();

        // Return the string of the inverted instance
        return $self->getString();
    }

    /**
     * @return bool
     */
    public function isInverted()
    {
        return $this->inverted;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        // We need our PHP parser
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer);

        // Get the code wrapped in an if
        $code = '<?php if(' . $this->getString() . '){}';

        try {

            // Parse it
            $stmts = $parser->parse($code);

        } catch (PHPParser_Error $e) {

            // If we got a parsing error we can assume the assertion as being invalid
            return false;
        }

        // Assertion got parsed, check if it got parsed as the right thing
        if (is_a($stmts[0], 'PHPParser_Node_Stmt_If')) {

            return true;

        } else {

            return false;
        }
    }
}