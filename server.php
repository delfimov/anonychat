<?php

use Workerman\Worker;
use AnonyChat\Config\Config;
use AnonyChat\Tools\StringNormilizer;
use AnonyChat\Server\Send;
use AnonyChat\Server\Users;
use AnonyChat\Server\Rooms;
use AnonyChat\Server\DecodeData;

require_once __DIR__ . '/vendor/autoload.php';

$users = [];

$context = [
    /*'ssl' => [
        'local_cert'  => __DIR__ . '/var/certificates/' . Config::get('local_cert'),
        'local_pk'    => __DIR__ . '/var/certificates/' . Config::get('local_pk'),
        'verify_peer' => false,
    ]*/
];
$websocket = new Worker('websocket://' . Config::get('server') . ':' . Config::get('port'), $context);
$users = new Users();
$rooms = new Rooms();

$websocket->onConnect = function($connection) use ($users, $rooms)
{
    $connection->onWebSocketConnect = function($connection) use ($users, $rooms)
    {
        $userName = StringNormilizer::name($_GET['user'] ?? 'user_' . bin2hex(random_bytes(10)));
        $roomName = StringNormilizer::room($_GET['room'] ?? '/');
        $color = StringNormilizer::hexcolor($_GET['color'] ?? '#000000');
        $users->clean();
        $users->add($connection, $userName);
        $rooms->add($userName, $roomName);
        $userNames = $rooms->getUsers($roomName);
        $connections = $users->getConnectionsByUsernames($userNames);
        Send::service($userName, $connections, $roomName, 'New user: ' . $userName);
        Send::color($userName, $connections, $roomName, $color);
    };
};

$websocket->onClose = function($connection) use ($users, $rooms)
{
    $users->clean();
    $userName = $users->findByConnection($connection);
    $roomName = $rooms->findByUsername($userName);
    $connections = $users->getConnectionsByUsernames($rooms->getUsers($roomName));
    $users->removeByConnection($connection);
    $rooms->removeByUsername($userName);
    Send::service($userName, $connections, $roomName, 'Disconnected user: ' . $userName);
    $users->clean();
    $rooms->clean();
};

$websocket->onMessage = function ($connection, $data) use ($users, $rooms) {
    if (!empty($data)) {
        $messageData = DecodeData::decode($data);
        $userName = $users->findByConnection($connection);
        $roomName = $rooms->findByUsername($userName);
        if (!empty($messageData) && $roomName == $messageData['room']) { // do not allow users to post to another rooms
            $connections = $users->getConnectionsByUsernames($rooms->getUsers($roomName));
            switch ($messageData['type']) {
                case 'text':
                    Send::text($userName, $connections, $roomName, $messageData['text']);
                    break;
                case 'disconnect':
                    $users->removeByConnection($connection);
                    $rooms->removeByUsername($userName);
                    Send::service($userName, $connections, $roomName, 'Disconnected user: ' . $userName);
                    break;
                case 'color':
                    Send::color($userName, $connections, $roomName, $messageData['text']);
                    break;
                default:
                    break;
            }
        }
    }
};

Worker::runAll();
