<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 11:48
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Lists;

require_once __DIR__ . "/AbstractTypedList.php";

class AttributeDefinitionList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Entities\Definitions\AttributeDefinition';
    }
}