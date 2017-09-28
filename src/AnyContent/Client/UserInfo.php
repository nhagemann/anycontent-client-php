<?php

namespace AnyContent\Client;

class UserInfo implements \JsonSerializable
{

    protected $username;

    protected $firstname;

    protected $lastname;

    protected $timestamp;


    public function __construct($username = '', $firstname = '', $lastname = '', $timestamp = null)
    {
        $this->setUsername($username);
        $this->setFirstname($firstname);
        $this->setLastname($lastname);
        $this->setTimestamp($timestamp);
    }


    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }


    public function getFirstname()
    {
        return $this->firstname;
    }


    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }


    public function getLastname()
    {
        return $this->lastname;
    }


    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }


    public function getUsername()
    {
        return $this->username;
    }


    public function userNameIsAnEmailAddress()
    {
        if (filter_var($this->getUsername(), FILTER_VALIDATE_EMAIL))
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    public function getName()
    {
        $name = trim($this->getFirstname() . ' ' . $this->getLastname());
        if ($name == '')
        {
            $name = $this->getUsername();
        }

        return $name;
    }


    public function setTimestampToNow()
    {
        $this->setTimestamp(time());

        return $this;
    }


    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }


    public function getTimestamp()
    {

        return $this->timestamp;
    }


    function jsonSerialize()
    {
        $userInfo              = [ ];
        $userInfo['timestamp'] = $this->getTimestamp();
        $userInfo['username']  = $this->getUsername();
        $userInfo['firstname'] = $this->getFirstname();
        $userInfo['lastname']  = $this->getLastname();

        return $userInfo;
    }
}