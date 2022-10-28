<?php

declare(strict_types=1);

namespace AnonyChat\Server;

class Send
{
    /**
     * @param array $userConnections array of \Workerman\Connection\ConnectionInterface
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
     * @param array $userConnections array of \Workerman\Connection\ConnectionInterface
     * @param string $text
     * @return void
     */
    public static function text(string $userName, array $userConnections, string $text)
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
