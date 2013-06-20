<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 14:46
 * To change this template use File | Settings | File Templates.
 */

namespace TechDivision\PBC\Entities;

class FunctionDefinitionList extends TypedList
{
    /**
     *
     */
    public function __constructor()
    {
        $this->itemType = 'FunctionDefinition';
    }
}