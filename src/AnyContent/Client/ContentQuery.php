<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;

use CMDL\Util;
use CMDL\ContentTypeDefinition;
use AnyContent\Client\ContentQueryFilter;

class ContentQuery
{

    protected $contentTypeDefinition;
    protected $workspace;
    protected $viewName;
    protected $language;
    protected $timeshift;
    protected $id;

    protected $filter = array();

    protected $limit;
    protected $page;


    protected function __construct(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        $this->contentTypeDefinition = $contentTypeDefinition;
        $this->setWorkspace($workspace);
        $this->viewName($viewName);
        $this->setLanguage($language);
        $this->setTimeshift($timeshift);

    }


    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
    }


    public function setId($id)
    {
        $this->id = $id;
    }


    public function setLanguage($language)
    {
        $this->language = $language;
    }


    public function setTimeshift($timeshift)
    {
        $this->timeshift = $timeshift;
    }


    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
    }


    public function addFilter(ContentQueryFilter $filter)
    {
        $this->filter[] = $filter;
    }


    public function clearFilter()
    {
        $this->filter = array();
    }


    public function setLimit($limit)
    {
        $this->limit = $limit;
    }


    public function setPage($page)
    {
        $this->page = $page;
    }

}