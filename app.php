<?php

use RC\platform\http\Response;
use RC\SDK;

//////////

$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '_cache';
$file = $cacheDir . DIRECTORY_SEPARATOR . 'platform.json';

if (!file_exists($cacheDir)) {
    mkdir($cacheDir);
}

$cachedAuth = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

$credentials = require('credentials.php');

//////////

$rcsdk = new SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server']);

$platform = $rcsdk->getPlatform();

$platform->setAuthData($cachedAuth);

try {

    $platform->isAuthorized();

    print 'Authorization was restored' . PHP_EOL;

} catch (Exception $e) {

    print 'Exception: ' . $e->getMessage() . PHP_EOL;

    $auth = $platform->authorize($credentials['username'], $credentials['extension'], $credentials['password'], true);

    print 'Authorized' . PHP_EOL;

}

$refresh = $platform->refresh();

print 'Refreshed' . PHP_EOL;

$extensions = $platform->get('/account/~/extension', ['query' => ['perPage' => 10]])
                       ->json()->records;

print 'Users loaded ' . count($extensions) . PHP_EOL;

$presences = $platform->get('/account/~/extension/' . $extensions[0]->id . ',' . $extensions[0]->id . '/presence')->getResponses();

print 'Presence loaded ' .
      $extensions[0]->name . ' - ' . $presences[0]->json()->presenceStatus . ', ' .
      $extensions[0]->name . ' - ' . $presences[1]->json()->presenceStatus . PHP_EOL;

try {

    $platform->get('/account/~/whatever');

} catch (\GuzzleHttp\Exception\RequestException $e) {

    $response = $e->getResponse();

    if ($response instanceof Response) {
        $message = $response->getError() . ' (from backend)';
    } else {
        $message = $e->getMessage();
    }

    print 'Expected HTTP Error: ' . $message . PHP_EOL;

}

print 'Sending SMS' . PHP_EOL;

$response = $platform->post('/account/~/extension/~/sms', [
    'json' => [
        'from' => ['phoneNumber' => $credentials['smsNumber']],
        'to'   => [
            ['phoneNumber' => $credentials['mobileNumber']],
        ],
        'text' => 'Test from PHP',
    ]
]);

print 'Sent ' . $response->json()->uri . PHP_EOL;

//////////

file_put_contents($file, json_encode($platform->getAuthData(), JSON_PRETTY_PRINT));