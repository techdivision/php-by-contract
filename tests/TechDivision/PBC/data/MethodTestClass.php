<?php
/**
 * TechDivision\Tests\Parser\AnnotationTestClass
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\Tests\Parser;

/**
 * @package     TechDivision\Tests
 * @subpackage  Parser
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
 *
 */
class MethodTestClass
{
    /**
     * @param string $req
     */
    public function bumpyBrackets($req)
    {
        $part = $req->getPart('file');

        file_put_contents("/opt/appserver/deploy/{$part->getFilename()}", $part->getInputStream());

        $application = new \stdClass();
        $application->name = $part->getFilename();

        $this->service->create($application);
    }

    /**
     * @ensures $pbcResult === '/Users/wickb/Workspace/src/TechDivision_DesignByContract/tests/TechDivision/PBC/data'
     * @return string
     */
    public function returnDir()
    {
        return __DIR__;
    }

    /**
     * @ensures $pbcResult === '/Users/wickb/Workspace/src/TechDivision_DesignByContract/tests/TechDivision/PBC/data/MethodTestClass.php'
     * @return string
     */
    public function returnFile()
    {
        return __FILE__;
    }
} 