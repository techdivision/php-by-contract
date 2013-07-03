<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 10:31
 * To change this template use File | Settings | File Templates.
 */
namespace TechDivision\PBC\Entities;

/**
 * Class MetaDefinition
 */
class MetaDefinition
{
    /**
     * @var string
     */
    public $filePath;

    /**
     * @var array
     */
    public $includes;

    /**
     * @var array
     */
    public $requires;

    /**
     * @var array
     */
    public $requireOnces;

    /**
     * @var array
     */
    public $includeOnces;

    /**
     * @var array
     */
    public $uses;
}