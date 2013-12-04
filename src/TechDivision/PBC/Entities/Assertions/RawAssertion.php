<?php

namespace TechDivision\PBC\Entities\Assertions;

/**
 * Class RawAssertion
 *
 * This class is used to provide an object base way to pass assertions as e.g. a precondition.
 */
class RawAssertion extends AbstractAssertion
{
    /**
     * @var string
     */
    public $content;

    /**
     * @param $_content
     */
    public function __construct($_content)
    {
        $this->content = $_content;

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getString()
    {
        return (string)$this->content;
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted === false) {

            $this->content = '!(' . $this->content . ')';
            $this->inverted = true;
            return true;

        } elseif ($this->inverted === true) {

            // Just unset the parts of $this->content we do not need
            unset($this->content[0]);
            unset($this->content[1]);
            unset($this->content[strlen($this->content) - 1]);

            $this->inverted = false;
            return true;

        } else {

            return false;
        }
    }
}