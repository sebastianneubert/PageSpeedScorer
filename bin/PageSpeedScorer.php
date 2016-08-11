<?php

include_once __DIR__ . "/../vendor/autoload.php";

if (count($argv) < 5) {
    echo "\n    PageSpeedScorer - Version ##development##";
    die("\n\n    Usage: PageSpeedScorer.phar url options [system_identifier] [project_api_key] [koalamonserver]\n\n");
}

$url = $argv[1];

$options = json_decode($argv[2]);
if (!is_null($options) && $options !== false && property_exists($options, 'limit')) {
    $limit = $options->limit;
}

$system = $argv[3];
$apiKey = $argv[4];

if ($argv[5]) {
    $reporter = new \Koalamon\Client\Reporter\Reporter('', $apiKey, new \GuzzleHttp\Client(), $argv[5]);
} else {
    $reporter = new \Koalamon\Client\Reporter\Reporter('', $apiKey, new \GuzzleHttp\Client());
}

$pageSpeed = new \PageSpeed\Insights\Service();
$psResult = $pageSpeed->getResults($url);
$pageSpeedScore = $psResult["ruleGroups"]["SPEED"]["score"];

$pageSpeedUrl = "https://developers.google.com/speed/pagespeed/insights/?url=" . $url;

if ($pageSpeedScore < $limit) {
    $status = \Koalamon\Client\Reporter\Event::STATUS_FAILURE;
    $message = "Page Speed Score low (" . $pageSpeedScore . ").";
} else {
    $status = \Koalamon\Client\Reporter\Event::STATUS_SUCCESS;
    $message = "Page Speed Score is " . $pageSpeedScore . ".";;
}

$event = new \Koalamon\Client\Reporter\Event('pagespeed_' . $url, $system, $status, 'pagespeedscore', $message, $pageSpeedScore, $pageSpeedUrl);

try {
    $reporter->sendEvent($event);
} catch (\GuzzleHttp\Exception\ServerException $e) {
    var_dump((string)$e->getResponse()->getBody());
}

echo "\n    $message\n\n";