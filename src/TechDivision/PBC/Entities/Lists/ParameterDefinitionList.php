<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 16.07.13
 * Time: 14:06
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities\Lists;

class ParameterDefinitionList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Entities\Definitions\ParameterDefinition';
    }
}