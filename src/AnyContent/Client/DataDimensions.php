<?php

namespace AnyContent\Client;

class DataDimensions
{
    protected $viewName = 'default';

    protected $workspace = 'default';

    protected $language = 'default';

    protected $timeShift = 0;

    public const MAX_TIMESHIFT = 315532800; // roundabout 10 years, equals to 1.1.1980


    /**
     * @return null
     */
    public function getViewName()
    {
        return $this->viewName;
    }


    /**
     * @param null $viewName
     */
    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
    }


    /**
     * @return null
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }


    /**
     * @param null $workspace
     */
    public function setWorkspace($workspace)
    {

        $this->workspace = $workspace;
    }


    /**
     * @return null
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * @param null $language
     */
    public function setLanguage($language)
    {

        $this->language = $language;
    }


    /**
     * @return null
     */
    public function getTimeShift()
    {
        return $this->timeShift;
    }


    /**
     * @param null $timeShift
     */
    public function setTimeShift($timeShift)
    {
        $this->timeShift = $timeShift;
    }


    public function hasRelativeTimeShift()
    {
        if ($this->timeShift != 0 & $this->timeShift < self::MAX_TIMESHIFT) {
            return true;
        }

        return false;
    }


    public function __toString()
    {
        return 'workspace: ' . $this->getWorkspace() . ', language: ' . $this->getLanguage() . ', view: ' . $this->getViewName() . ', timeshift: ' . $this->getTimeShift();
    }
}
