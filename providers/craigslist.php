<?php


//require __DIR__ . '../vendor/autoload.php';
require_once('provider.php');


class Craigslist extends Provider
{
    const NAME = 'Craigslist';
//    const URL = 'https://montreal.craigslist.org/search/zip?sort=date&postedToday=1&lang=en&cc=us';
    const URL = 'https://montreal.craigslist.org/search/zip?sort=date&lang=en&cc=us';
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
