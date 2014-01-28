<?php
/**
 * TechDivision\PBC\Entities\Assertions\TypedCollectionAssertion
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Entities\Assertions;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class TypedCollectionAssertion extends AbstractAssertion
{
    /**
     * @var string
     */
    public $operand;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    protected $comparator;

    /**
     * @param $_operand
     * @param $_type
     */
    public function __construct($_operand, $_type)
    {
        $this->operand = $_operand;
        $this->type = $_type;
        $this->comparator = '===';

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getString()
    {
        $code = 'count(array_filter(' . $this->operand . ', function(&$value) {
        if (!$value instanceof ' . $this->type . ') {

            return true;
        }
        })) ' . $this->comparator . ' 0';

        return $code;
    }

    /**
     * @return bool
     */
    public function invert()
    {
        if ($this->inverted === false) {

            $this->comparator = '!==';
            $this->inverted = true;

            return true;

        } elseif ($this->inverted === true) {

            $this->comparator = '===';
            $this->inverted = false;

            return true;

        } else {

            return false;
        }
    }
}
