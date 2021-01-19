<?php


//require __DIR__ . '../vendor/autoload.php';
require_once('provider.php');


class Letgo extends Provider
{
    const NAME = 'Letgo';
    const URL = 'https://www.letgo.com/en-ca/scl/quebec/quebec/montreal?price%5Bmax%5D=0';
    const BASE_URL = 'https://www.kijiji.ca';
    const ITEM_SELECTOR = 'li.result-row';


    function getIdentifier($item)
    {
        return $item->data('pid');
    }

    function getTitle($item)
    {
        return trim($item->find('.title a')->text());
    }

    function getLink($item)
    {
        return self::BASE_URL . trim($item->find('.title a')->attr('href'));
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
        return trim($item->find('.description')->text());
    }

    function getImageUrl($item)
    {
        return trim($item->find('picture source')->attr('data-srcset'));
    }
}
