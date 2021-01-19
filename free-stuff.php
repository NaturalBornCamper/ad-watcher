<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

if (!isset($_GET['to_email']))
    exit('No \'to_email\' $_GET parameter sent, cannot send updates');

require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/providers/kijiji.php';
require __DIR__ . '/providers/freecycle.php';
require __DIR__ . '/providers/craigslist.php';

// https://github.com/Rct567/DomQuery
use \Rct567\DomQuery\DomQuery;

$CACHE_DIRECTORY = __DIR__ . '/cache';
$CACHE_COUNT = 10; // How many item ids to keep in cache

$TITLE_BLACKLIST = implode('|', [
    '\$',
    '\bvendre\b', '\bvend\b', '\bvends\b', '\bvente\b', '\bsale\b', '\bsell\b', '\bselling\b',
    '\bservice\b', '\brepair\b',
    '\béchang', '\bswap\b',
    '\bachèt', '\bachet', '\bbuy\b',
    '\blivraison\b', '\btransport\b', '\bdelivery\b', '\bexperts', '\bpick\-up',
    '\bramass', '\bpick ',
    '\bwanted\b', '\bcherch', '\brecherch', '\blooking for\b',
    '\bessai', '\bsample', '\bestimat', '\btracker', '\bsoumission',
    '\bremplissage',
]);
$DESCRIPTION_BLACKLIST = implode('|', [
    '\$',
    '\bvendre\b', '\bvend\b', '\bvends\b', '\bvente\b', '\bsale\b', '\bsell\b', '\bselling\b',
    '\bservice\b', '\brepair\b',
    '\bprix\b', '\bprice\b', '\bdollar',
    '\béchang', '\bswap\b',
    '\bachèt', '\bachet', '\bbuy\b',
    '\bestimation\b',
    '\bramasse\b',
    '\bforfait\b',
    '\bmoney\b', '\bargent\b',
    '\bwanted\b', '\bcherch', '\brecherch', '\blooking for\b',
    '\bessai',
    '\bcours\b',
    '\bproduits\b', '\bproducts\b',
]);


if (isset($_GET['dev'])) {
    $PROVIDERS = [
        new Craigslist($TITLE_BLACKLIST, $DESCRIPTION_BLACKLIST),
    ];
} else {
    $PROVIDERS = [
        new Kijiji($TITLE_BLACKLIST, $DESCRIPTION_BLACKLIST),
        new Freecycle('', ''),
    ];
}


// 1- Make cronjob to get last useragent and change it in an ini file with this: https://accounts.whatismybrowser.com/ (Once per day)
// 2- Grab latestuser agent from that file
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

if (!is_dir($CACHE_DIRECTORY)) {
    mkdir($CACHE_DIRECTORY, 0755, true);
}


foreach ($PROVIDERS as $PROVIDER) {
    // Get latest cached item id
    $identifier = str_replace(' ', '_', strtolower($PROVIDER::NAME));
    $cachedFilename = "{$CACHE_DIRECTORY}/{$identifier}.ini";
    var_dump($cachedFilename);
    $cachedIds = [];
    if (is_file($cachedFilename)) {
        $cachedIds = explode("\n", file_get_contents($cachedFilename));
    }

    // Loop in page's items
    $dom = new DomQuery(file_get_contents($PROVIDER::URL, false, $context));
    $latestVersion = null;
    $newItems= [];
    foreach ($dom->find($PROVIDER::ITEM_SELECTOR) as $item) {
        $PROVIDER->reset();
        // if item id == cached item id, break
        $itemId = $PROVIDER->getIdentifier($item);
//        echo '<br>current:'; var_dump($itemId);
//        echo '<br>cached:'; var_dump($cachedIds);

        // Check if current item id was sent before
        if (in_array($itemId, $cachedIds)) {
            echo 'ALL ITEMS SENT<br><br>';
            break;
        } else {
            // This is a new item, add it in the list of item ids to cache
            $newItems[] = $itemId;
        }

        // Check for blacklisted words in title and description
        if ($blacklistedWordMessage = $PROVIDER->getBlacklistedWord($item)) {
            echo $blacklistedWordMessage;
            continue;
        }

        // No blacklisted words, scrape the rest of the data needed
        $PROVIDER->init($item);

        // Prepare notification email and sms
        $subject = $PROVIDER::NAME . ': ' . $PROVIDER->title;
        $html = "<a href=\"{$PROVIDER->link}\">{$PROVIDER->title}</a>";
        $html .= "<br><a href=\"https://www.google.ca/maps/place/{$PROVIDER->location}\">{$PROVIDER->location}</a>";
        $html .= '<br>' . $PROVIDER->description;
        $html .= "<br><a href=\"{$PROVIDER->link}\"><img src=\"{$PROVIDER->imageUrl}\"></a>";
        $text = $PROVIDER->link;
        $to = [];
        $to[] = [
            'Email' => $_GET['to_email'],
            'Name' => 'You'
        ];
//        var_dump($to);
        echo $html . '<br><br><br><br>';
//        break;
//        continue;

        // Send email and SMS
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3.1/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'Messages' => [
                [
                    'From' => [
                        'Email' => FROM_EMAIL,
                        'Name' => $PROVIDER::NAME . ' Watcher'
                    ],
                    'To' => $to,
                    'Subject' => $subject,
                    'TextPart' => $text,
                    'HTMLPart' => $html
                ]
            ]
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, MAILJET_USER . ':' . MAILJET_PASSWORD);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            var_dump($result);
        }
        curl_close($ch);
    }
    
    // If there are any new items, add them to the cached item id list, keeping only $CACHE_COUNT of them (10 by default)
    if ($newItems) {
        $cachedIds = array_merge($newItems, $cachedIds);
        file_put_contents($cachedFilename, implode(PHP_EOL, array_slice($cachedIds, 0, $CACHE_COUNT)));
    }
}

