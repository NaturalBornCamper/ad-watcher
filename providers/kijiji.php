<?php


//require __DIR__ . '../vendor/autoload.php';
require_once('provider.php');


class Kijiji extends Provider
{
    const NAME = 'Kijiji';
//    const URL = 'https://www.kijiji.ca/b-objets-gratuits/grand-montreal/c17220001l80002?a-vendre-par=ownr?siteLocale=en_CA';
    const URL = 'https://www.kijiji.ca/b-objets-gratuits/ville-de-montreal/c17220001l1700281?siteLocale=en_CA';
    const BASE_URL = 'https://www.kijiji.ca';
    const ITEM_SELECTOR = '.regular-ad';


    function getIdentifier($item)
    {
        $this->identifier || $this->identifier = $item->data('listing-id');
        return $this->identifier;
    }

    function getTitle($item)
    {
        $this->title || $this->title = trim($item->find('.title a')->text());
        return $this->title;
    }

    function getLink($item)
    {
        return self::BASE_URL . str_replace('"', '', trim($item->find('.title a')->attr('href')));
    }

    function getDate($item)
    {
        return trim($item->find('.date-posted')->text());
    }

    function getLocation($item)
    {
        return trim($item->find('.location span:first-child')->text());
    }

    function getDescription($item)
    {
        $this->description || $this->description = trim($item->find('.description')->text());
        return $this->description;
    }

    function getImageUrl($item)
    {
        return trim($item->find('picture source')->attr('data-srcset'));
    }
}
