<?php
/**
 * File containing the abstract AbstractDefinition class
 *
 * PHP version 5
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\PBC\Entities\Definitions;

use TechDivision\PBC\Entities\AbstractLockableEntity;

/**
 * TechDivision\PBC\Entities\Definitions\AbstractDefinition
 *
 * This class is a combining parent class for all definition classes.
 * Just to give them a known parent
 *
 * @category   Php-by-contract
 * @package    TechDivision_PBC
 * @subpackage Entities
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
abstract class AbstractDefinition extends AbstractLockableEntity
{

}
