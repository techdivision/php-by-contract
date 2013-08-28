<?php

namespace TechDivision\PBC\Entities\Lists;

class TypedListList extends AbstractTypedList
{

    public function __construct()
    {
        $this->itemType = 'TechDivision\PBC\Interfaces\TypedList';
    }
}