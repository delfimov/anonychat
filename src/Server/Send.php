<?php

namespace AnonyChat\Server;

class Send
{
    public static function system($userConnections, $data)
    {
        foreach ($userConnections as $connectionUserName => $userConnection) {
            $dataSend = json_encode([
                'type' => 'system',
                'data' => $data,
            ]);
            $userConnection->send($dataSend);
        }
    }

    public static function text($userName, $userConnections, $text)
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
