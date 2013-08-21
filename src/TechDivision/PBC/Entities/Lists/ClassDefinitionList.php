<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 11.07.13
 * Time: 15:12
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities\Lists;

class ClassDefinitionList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Entities\Definitions\ClassDefinition';
    }
}