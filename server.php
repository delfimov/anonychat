<?php

/**
 * AnonyChat WebSocket server.
 */

declare(strict_types=1);

use Workerman\Worker;
use Workerman\Connection\ConnectionInterface;
use AnonyChat\Tools\StringNormalizer;
use AnonyChat\Server\Send;
use AnonyChat\Server\Users;
use AnonyChat\Server\Rooms;
use AnonyChat\Server\DecodeData;

require_once __DIR__ . '/vendor/autoload.php';

$users = [];

$config = require_once __DIR__ . '/config/config.php';

if ($config['protocol'] == 'wss') {
    $context = [
        'ssl' => [
            'local_cert'  => __DIR__ . '/var/certificates/' . $config['local_cert'],
            'local_pk'    => __DIR__ . '/var/certificates/' . $config['local_pk'],
            'verify_peer' => false,
        ]
    ];
    $websocket = new Worker('websocket://' . $config['server_name'] . ':' . $config['port'], $context);
    $websocket->transport = 'ssl';
} else {
    $websocket = new Worker('websocket://' . $config['server_name'] . ':' . $config['port']);
}

$users = new Users();
$rooms = new Rooms();

$websocket->onConnect = function(ConnectionInterface $connection) use ($users, $rooms)
{
    $connection->onWebSocketConnect = function(ConnectionInterface $connection) use ($users, $rooms)
    {
        $userName = StringNormalizer::name($_GET['user'] ?? 'user_' . bin2hex(random_bytes(10)));
        $roomName = StringNormalizer::room($_GET['room'] ?? '/');
        $color = StringNormalizer::hexcolor($_GET['color'] ?? '#000000');
        $userName = $users->add($connection, $userName);
        $rooms->add($userName, $roomName);
        $rooms->getRoomTimeout($roomName);
        $userNames = $rooms->getUsers($roomName);
        $connections = $users->getConnectionsByUsernames($userNames);
        Send::system([$connection], [
            'user_name' => $userName,
            'user_color' => ['user' => $userName, 'color' => $color],
            'room_name' => $roomName,
        ]);
        Send::system($connections, [
            'new_user' => $userName,
            'user_color' => ['user' => $userName, 'color' => $color],
            'room_users' => $userNames,
            'room_timeout' => $rooms->getRoomTimeout($roomName),
        ]);
    };
};

$websocket->onClose = function(ConnectionInterface $connection) use ($users, $rooms)
{
    $userName = $users->findByConnection($connection);
    if ($userName !== false) {
        $roomName = $rooms->findByUsername($userName);
        $users->removeByConnection($connection);
        $rooms->removeByUsername($userName);
        $userNames = $rooms->getUsers($roomName);
        $connections = $users->getConnectionsByUsernames($userNames);
        $disconnectedUserNames = $rooms->clean($roomName);
        if (count($disconnectedUserNames) > 0) { // the room is removed, all users must be disconnected
            foreach ($disconnectedUserNames as $disconnectedUserName) {
                $users->removeByUsername($disconnectedUserName);
            }
        } else {
            $disconnectedUserNames = $users->clean($userNames);
            foreach ($disconnectedUserNames as $disconnectedUserName) {
                $rooms->removeByUsername($disconnectedUserName);
            }
        }
        $userNames = $rooms->getUsers($roomName);
        array_unshift($disconnectedUserNames, $userName);
        Send::system($connections, [
            'disconnected_users' => $disconnectedUserNames,
            'room_users' => $userNames,
            'room_timeout' => $rooms->getRoomTimeout($roomName),
        ]);
    }
};

$websocket->onMessage = function (ConnectionInterface $connection, $data) use ($users, $rooms) {
    if (!empty($data)) {
        $messageData = DecodeData::decode($data);
        $userName = $users->findByConnection($connection);
        $roomName = $rooms->findByUsername($userName);
        if (isset($messageData['type'])) { // do not allow users to post to another rooms
            $userNames = $rooms->getUsers($roomName);
            $connections = $users->getConnectionsByUsernames($userNames);
            switch ($messageData['type']) {
                case 'text':
                    Send::text($userName, $connections, $messageData['text']);
                    break;
                case 'system':
                    switch ($messageData['method']) {
                        case 'keepalive':
                            Send::system([$connection], [
                                'keepalive' => time(),
                                'room_users' => $userNames,
                                'room_timeout' => $rooms->getRoomTimeout($roomName)
                            ]);
                            break;
                        case 'color':
                            Send::system($connections, [
                                'user_color' => ['user' => $userName, 'color' => $messageData['color']],
                            ]);
                            break;
                    }
                    break;
                default:
                    break;
            }
        }
    }
};

Worker::runAll();
