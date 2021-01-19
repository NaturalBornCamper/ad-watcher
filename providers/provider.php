<?php


abstract class Provider
{
    var $titleBlacklist;
    var $descriptionBlacklist;

    var $identifier = null;
    var $title = null;
    var $link = null;
    var $date = null;
    var $location = null;
    var $description = null;
    var $imageUrl = null;


    function __construct($titleBlacklist, $descriptionBlacklist)
    {
        $this->titleBlacklist = $titleBlacklist ? "/{$titleBlacklist}/sui" : false;
        $this->descriptionBlacklist = $descriptionBlacklist ? "/{$descriptionBlacklist}/sui": false;
    }


    function reset()
    {
        $this->identifier = $this->title = $this->link = $this->date = $this->location = $this->description = $this->imageUrl = null;
    }


    function init($item)
    {
        $this->identifier = $this->getIdentifier($item);
        $this->title = $this->getTitle($item);
        $this->link = $this->getLink($item);
        $this->date = $this->getDate($item);
        $this->location = $this->getLocation($item);
        $this->description = $this->getDescription($item);
        $this->imageUrl = $this->getImageUrl($item);
    }


    function getBlacklistedWord($item)
    {
        // Check for blacklisted words in title
        $title = $this->getTitle($item);
        if ($this->titleBlacklist && preg_match($this->titleBlacklist, $title, $matches)) {
            return "Found blacklisted word in title: \"{$matches[0]}\", skipping post<br><br>";
        }

        // Check for blacklisted words in description
        $description = $this->getDescription($item);
        if ($this->descriptionBlacklist && preg_match($this->descriptionBlacklist, $description, $matches)) {
            return "Found blacklisted word in description: \"{$matches[0]}\", skipping post<br><br>";
        }

        return false;
    }


    function getIdentifier($item)
    {
        return '';
    }

    function getTitle($item)
    {
        return '';
    }

    function getLink($item)
    {
        return '';
    }

    function getDate($item)
    {
        return '';
    }

    function getLocation($item)
    {
        return '';
    }

    function getDescription($item)
    {
        return '';
    }

    function getImageUrl($item)
    {
        return '';
    }
}
