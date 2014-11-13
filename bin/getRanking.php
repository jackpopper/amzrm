<?php
// use kimono api
// https://www.kimonolabs.com/
define('KIMONO_API_HOST', 'https://www.kimonolabs.com/api/');
define('KIMONO_API_KEY', 'cDgHOAXUAvGpBoQAiifct4lDMRVuvCaF');
//define('KIMONO_API_AMAZON_PATH', '864ugh0o');
define('KIMONO_API_AMAZON_MAIN_PATH', 'ab2jf8r8');
define('KIMONO_API_AMAZON_SUB_PATH', 'e66sal32');
define('AMAZON_RSS_URL', 'http://www.amazon.co.jp/gp/rss/');
define('RANKING_TYPE', 'bestsellers,new-releases');
define('DATA_DIR', '../data');
define('LOG', '/var/log/app/amazon_ranking/get_ranking.log');

$category = [];
/*
$data = getAmazonCategory(KIMONO_API_AMAZON_PATH);
// main category
foreach ($data->results->main as $res) {
    if (empty($res->rss)) {
        echo "empty rss in ".$res->title."\n";
        continue;
    }
    $hrefInfo = explode('/', $res->rss->href);
    $category[$hrefInfo[6]]['all'] = $res->title;
}
// sub category
foreach ($data->results->sub as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]][$hrefInfo[6]] = $res->category->text;
}
*/

// main category
$data = getAmazonCategory(KIMONO_API_AMAZON_MAIN_PATH);
foreach ($data->results->collection1 as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]]['all'] = $res->category->text;
}

// sub category
$data = getAmazonCategory(KIMONO_API_AMAZON_SUB_PATH);
foreach ($data->results->collection1 as $res) {
    $hrefInfo = explode('/', $res->category->href);
    $category[$hrefInfo[5]][$hrefInfo[6]] = $res->category->text;
}
//print_r($category);exit;
file_put_contents(DATA_DIR.'/category/list.json', json_encode($category));

// get amazon ranking from RSS
foreach (explode(',', RANKING_TYPE) as $type) {
    echo "START get $type\n";
    foreach ($category as $catId => $catData) {
        echo "START get $catId\n";
        foreach ($catData as $catNumber => $catTitle) {
            echo "START get $catNumber : $catTitle\n";
            $catDataDir = DATA_DIR.'/ranking/'.$type.'/'.$catId;
            if (!file_exists($catDataDir)) mkdir($catDataDir, 0755, true);
            $data = json_encode(getAmazonRank($type, $catId, $catNumber));
//echo $data;
            file_put_contents($catDataDir.'/'.$catNumber.'.json', $data);
        }
    }
}

//-----------------------------------------------------------------------------
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
    $url = KIMONO_API_HOST.$apiPath.'?apikey='.KIMONO_API_KEY;
    $ret = json_decode(getWeb($url));
    if (empty($ret->count)) {
        echo "ERROR\n";
    }

    return $ret;
}

function getAmazonRank($type, $categoryId, $categoryNumber) {
    $ret = [];
    if ($categoryNumber === 'all') $categoryNumber = '';
    $xml = getWeb(AMAZON_RSS_URL.$type.'/'.$categoryId.'/'.$categoryNumber);
    $data = simplexml_load_string($xml);
//print_r($data);
//    $pubDate = $data->channel->pubDate;
//    echo "$pubDate\n";
    if (empty($data->channel->item)) return $ret;
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
