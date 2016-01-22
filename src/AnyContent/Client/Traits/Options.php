<?php
namespace AnyContent\Client\Traits;

use AnyContent\AnyContentClientException;

trait Options
{

    protected $options = [ ];


    public function addOptions($options = [ ])
    {
        $this->options = array_merge($this->options, $options);
    }


    public function setOptions($options = [ ])
    {
        $this->options = $options;
    }


    public function hasOption($option)
    {
        if (array_key_exists($option, $this->options))
        {
            return true;
        }

        return false;
    }


    public function getOption($option, $default = null)
    {
        if (array_key_exists($option, $this->options))
        {
            return $this->options[$option];
        }

        return $default;

    }


    public function requireOption($option)
    {
        if ($this->hasOption($option))
        {
            return true;
        }
        throw new AnyContentClientException('Missing required option ' . $option);
    }


    public function requireOptions($options)
    {
        foreach ($options as $option)
        {
            $this->requireOption($option);
        }
    }
}