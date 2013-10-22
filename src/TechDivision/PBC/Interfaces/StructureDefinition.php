<?php

namespace TechDivision\PBC\Interfaces;

interface StructureDefinition
{
    /**
     * Will return a list of all dependencies of a structure like parent class, implemented interfaces, etc.
     *
     * @return array
     */
    public function getDependencies();

    /**
     * Will finalize a definition by resolving dependencies and inherited/implemented contracts.
     *
     * @return bool
     */
    public function finalize();
}