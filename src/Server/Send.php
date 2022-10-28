<?php

namespace AnonyChat\Server;

use \Workerman\Connection\ConnectionInterface;

class Send
{
    /**
     * @param array $userConnections
     * @param mixed $data
     * @return void
     */
    public static function system(array $userConnections, $data)
    {
        foreach ($userConnections as $connectionUserName => $userConnection) {
            $dataSend = json_encode([
                'type' => 'system',
                'data' => $data,
            ]);
            $userConnection->send($dataSend);
        }
    }

    /**
     * @param string $userName
     * @param ConnectionInterface $userConnections
     * @param string $text
     * @return void
     */
    public static function text(string $userName, ConnectionInterface $userConnections, string $text)
    {
        foreach ($userConnections as $connectionUserName => $userConnection) {
            $dataSend = json_encode([
                'type' => 'text',
                'from' => $userName,
                'text' => $text,
            ]);
            $userConnection->send($dataSend);
        }
    }
}
