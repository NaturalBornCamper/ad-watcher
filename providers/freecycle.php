<?php


//require __DIR__ . '/../vendor/autoload.php';
require_once('provider.php');

use \Rct567\DomQuery\DomQuery;


class Freecycle extends Provider
{
    const NAME = 'Freecycle';
    const URL = 'https://groups.freecycle.org/group/MontrealQC/posts/offer';
    const BASE_URL = 'https://groups.freecycle.org/group/MontrealQC/posts/';
    const ITEM_SELECTOR = '#group_posts_table tr';


    function getIdentifier($item)
    {
        if (!$this->identifier) {
            preg_match('#posts/(\d+)/#', $this->getLink($item), $matches);
            $this->identifier = $matches[1];
        }

        return $this->identifier;
    }

    function getTitle($item)
    {
        $this->title || $this->title = trim($item->children('td')->first()->next()->find('a')->text());
        return $this->title;
    }

    function getLink($item)
    {
        return $item->find('.textCenter a')->attr('href');
    }

    function getDate($item)
    {
        preg_match('/<br>(.+?)<br>/s', $item->children('td')->first()->html(), $matches);
        return trim($matches[1]);
    }

    function getLocation($item)
    {
        preg_match('#\((.+?)\)#s', $item->children('td')->first()->next()->html(), $matches);
        return trim($matches[1]);
    }

    function getDescription($item)
    {
        if (!$this->description) {
            $context = stream_context_create(
                [
                    "http" => [
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36"
                    ],
                    "ssl" => [
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ],
                ]
            );

            $detailsDom = new DomQuery(file_get_contents($this->getLink($item), false, $context));
            $this->description = trim($detailsDom->find('#group_post p')->text());

//        $this->description = trim($item->find('td:first-child')->next()->text());
//        preg_match('/<br>(.+?)<br>/s', $item->find('td:first-child')->text(), $matches);

//        $this->description = trim($matches[1]);
        }
        return $this->description;
    }

    function getImageUrl($item)
    {
        return 'https://groups.freecycle.org/group/MontrealQC/post_image/' . $this->getIdentifier($item);
    }
}
