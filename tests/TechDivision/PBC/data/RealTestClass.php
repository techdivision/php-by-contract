<?php

/**
 * TechDivision\Example\Servlets\IndexServlet
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace TechDivision\Example\Servlets;

use TechDivision\ServletContainer\Interfaces\Servlet;
use TechDivision\ServletContainer\Interfaces\ServletConfig;
use TechDivision\ServletContainer\Interfaces\Request;
use TechDivision\ServletContainer\Interfaces\Response;
use TechDivision\PersistenceContainerClient\Context\Connection\Factory;
use TechDivision\Example\Servlets\AbstractServlet;
use TechDivision\Example\Entities\Sample;
use TechDivision\Example\Utils\ContextKeys;

/**
 * @package     TechDivision\Example
 * @copyright  	Copyright (c) 2013 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://opensource.org/licenses/osl-3.0.php
 *              Open Software License (OSL 3.0)
 * @author      Tim Wagner <tw@techdivision.com>
 */
class IndexServlet extends AbstractServlet implements Servlet {

    /**
     * The relative path, up from the webapp path, to the template to use.
     * @var string
     */
    const INDEX_TEMPLATE = 'templates/index.phtml';

    /**
     * Class name of the persistence container proxy that handles the data.
     * @var string
     */
    const PROXY_CLASS = 'TechDivision\Example\Services\SampleProcessor';

    /**
     * Default action to invoke if no action parameter has been found in the request.
     *
     * Loads all sample data and attaches it to the servlet context ready to be rendered
     * by the template.
     *
     * @param Request $req The request instance
     * @param Response $res The response instance
     * @return void
     */
    public function indexAction(Request $req, Response $res) {
        $overviewData = $this->getProxy(self::PROXY_CLASS)->findAll();
        $this->addAttribute(ContextKeys::OVERVIEW_DATA, $overviewData);
        $res->setContent($this->processTemplate(self::INDEX_TEMPLATE, $req, $res));
    }

    /**
     * Loads the sample entity with the sample ID found in the request and attaches
     * it to the servlet context ready to be rendered by the template.
     *
     * @param Request $req The request instance
     * @param Response $res The response instance
     * @return void
     * @see IndexServlet::indexAction()
     */
    public function loadAction(Request $req, Response $res) {

        // load the params with the entity data
        $parameterMap = $req->getParams();

        // check if the necessary params has been specified and are valid
        if (!array_key_exists('sampleId', $parameterMap)) {
            throw new \Exception();
        } else {
            $sampleId = filter_var($parameterMap['sampleId'], FILTER_VALIDATE_INT);
        }

        // load the entity to be edited and attach it to the servlet context
        $viewData = $this->getProxy(self::PROXY_CLASS)->load($sampleId);
        $this->addAttribute(ContextKeys::VIEW_DATA, $viewData);

        // reload all entities and render the dialog
        $this->indexAction($req, $res);
    }

    /**
     * Deletes the sample entity with the sample ID found in the request and
     * reloads all other entities from the database.
     *
     * @param Request $req The request instance
     * @param Response $res The response instance
     * @return void
     * @see IndexServlet::indexAction()
     */
    public function deleteAction(Request $req, Response $res) {

        // load the params with the entity data
        $parameterMap = $req->getParams();

        // check if the necessary params has been specified and are valid
        if (!array_key_exists('sampleId', $parameterMap)) {
            throw new \Exception();
        } else {
            $sampleId = filter_var($parameterMap['sampleId'], FILTER_VALIDATE_INT);
        }

        // delete the entity
        $this->getProxy(self::PROXY_CLASS)->delete($sampleId);

        // reload all entities and render the dialog
        $this->indexAction($req, $res);
    }

    /**
     * Persists the entity data found in the request.
     *
     * @param Request $req The request instance
     * @param Response $res The response instance
     * @return void
     * @see IndexServlet::indexAction()
     */
    public function persistAction(Request $req, Response $res) {

        // load the params with the entity data
        $parameterMap = $req->getParams();

        // check if the necessary params has been specified and are valid
        if (!array_key_exists('sampleId', $parameterMap)) {
            throw new \Exception();
        } else {
            $sampleId = filter_var($parameterMap['sampleId'], FILTER_VALIDATE_INT);
        }
        if (!array_key_exists('name', $parameterMap)) {
            throw new \Exception();
        } else {
            $name = filter_var($parameterMap['name'], FILTER_SANITIZE_STRING);
        }

        // create a new entity and persist it
        $entity = new Sample();
        $entity->setSampleId((integer) $sampleId);
        $entity->setName($name);
        $this->getProxy(self::PROXY_CLASS)->persist($entity);

        // reload all entities and render the dialog
        $this->indexAction($req, $res);
    }

    /**
     * Creates and returns the URL to open the dialog to edit the passed entity.
     *
     * @param \TechDivision\Example\Entities\Sample $entity The entity to create the edit link for
     * @return string The URL to open the edit dialog
     */
    public function getEditLink($entity) {
        return '?action=load&sampleId=' . $entity->getSampleId();
    }

    /**
     * Creates and returns the URL that has to be invoked to delete the passed entity.
     *
     * @param \TechDivision\Example\Entities\Sample $entity The entity to create the deletion link for
     * @return string The URL with the deletion link
     */
    public function getDeleteLink($entity) {
        return '?action=delete&sampleId=' . $entity->getSampleId();
    }
}