<?php
/**
 * TechDivision\PBC\Tests\Data\MethodTestClass
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */
namespace TechDivision\PBC\Tests\Data;

/**
 * @package     TechDivision\PBC
 * @subpackage  Tests
 * @copyright   Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license     http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Bernhard Wick <b.wick@techdivision.com>
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
     * @return string
     */
    public function returnDir()
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function returnFile()
    {
        return __FILE__;
    }

    public function iHaveALambdaFunction()
    {
        // Process can not handle env values that are arrays
        $test = array_filter(
            array(array(), 'test'),
            function ($value) {
                if (!is_array($value)) {
                    return true;
                }
            }
        );

        return $test;
    }
}
