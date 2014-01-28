<?php
/**
 * TechDivision\PBC\Entities\Lists\StructureDefinitionList
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\PBC\Entities\Lists;

/**
 * @package     TechDivision\PBC
 * @subpackage  Entities
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 */
class StructureDefinitionList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Interfaces\StructureDefinitionInterface';
        $this->defaultOffset = 'name';
    }
}
