<?php
// use kimono api
// https://www.kimonolabs.com/
define('KIMONO_API_HOST', 'https://www.kimonolabs.com/api/');
define('KIMONO_API_KEY', 'cDgHOAXUAvGpBoQAiifct4lDMRVuvCaF');
//define('KIMONO_API_AMAZON_BESTSELLER_PATH', '864ugh0o');
define('KIMONO_API_AMAZON_BESTSELLER_MAIN_PATH', 'ab2jf8r8');
define('KIMONO_API_AMAZON_BESTSELLER_SUB_PATH', 'e66sal32');
define('AMAZON_BESTSELLER_RSS_URL', 'http://www.amazon.co.jp/gp/rss/bestsellers/');
define('DATA_DIR', '../data');
define('LOG', '/var/log/app/amazon_ranking/get_bestseller_category.log');

$category = [];

/*
$data = getAmazonCategory(KIMONO_API_AMAZON_BESTSELLER_PATH);
// bestseller main category
foreach ($data->results->main as $res) {
    if (empty($res->rss)) {
        echo "empty rss in ".$res->title."\n";
        continue;
    }
    $hrefInfo = explode('/', $res->rss->href);
    $category[$hrefInfo[6]]['all'] = $res->title;
}
// bestseller sub category
foreach ($data->results->sub as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]][$hrefInfo[6]] = $res->category->text;
}
*/

// bestseller main category
$data = getAmazonCategory(KIMONO_API_AMAZON_BESTSELLER_MAIN_PATH);
foreach ($data->results->collection1 as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]]['all'] = $res->category->text;
}

// bestseller sub category
$data = getAmazonCategory(KIMONO_API_AMAZON_BESTSELLER_SUB_PATH);
foreach ($data->results->collection1 as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]][$hrefInfo[6]] = $res->category->text;
}
//print_r($category);exit;
file_put_contents(DATA_DIR.'/category/list.json', json_encode($category));

// get amazon ranking from RSS
foreach($category as $catId => $catData) {
    foreach ($catData as $catNumber => $catTitle) {
        echo "$catId : $catNumber : $catTitle\n";
        $catDataDir = DATA_DIR.'/ranking/'.$catId;
        if (!file_exists($catDataDir)) mkdir($catDataDir);
        $data = json_encode(getAmazonRank($catId, $catNumber));
//echo $data;
        file_put_contents($catDataDir.'/'.$catNumber.'.json', $data);
    }
}

//-----------------------------------------------------------------------------
function makeKimonoApiUrl($apiPath) {
    return KIMONO_API_HOST.$apiPath.'?apikey='.KIMONO_API_KEY;
}

function getWeb($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (! $ret = curl_exec($ch)) {
        echo "ERROR\n";
    }
    curl_close($ch);

    return $ret;
}

function getAmazonCategory($apiPath) {
    $ret = json_decode(getWeb(makeKimonoApiUrl($apiPath)));
    if (empty($ret->count)) {
        echo "ERROR\n";
    }

    return $ret;
}

function getAmazonRank($categoryId, $categoryNumber) {
    $ret = [];
    if ($categoryNumber === 'all') $categoryNumber = '';
    $xml = getWeb(AMAZON_BESTSELLER_RSS_URL.$categoryId.'/'.$categoryNumber);
    $data = simplexml_load_string($xml);
//print_r($data);
    $pubDate = $data->channel->pubDate;
    echo "$pubDate\n";
    foreach($data->channel->item as $item) {
        list($rank, $title) = explode(':', $item->title, 2);
        if (preg_match('|<img src="(?P<image>http://ecx.images-amazon.com/images/.*\.jpg)"|', $item->description, $match)) {
            $image = $match['image'];
        } else {
            $image = 'http://g-ecx.images-amazon.com/images/G/09/icons/books/comingsoon_books.gif';
        }
        $ret[] = [
            'rank' => trim($rank, ' #'),
            'title' => trim($title),
            'href' => trim($item->link),
            'image' => $image,
            ];
    }

    return $ret;
}
