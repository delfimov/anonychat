<?php

namespace AnonyChat\Server;

class Send
{
    public static function text($userName, $userConnections, $room, $text)
    {
        self::send($userName, $userConnections, $room, $text, 'text');
    }

    public static function service($userName, $userConnections, $room, $text)
    {
        self::send($userName, $userConnections, $room, $text, 'service');
    }

    public static function color($userName, $userConnections, $room, $text)
    {
        self::send($userName, $userConnections, $room, $text, 'color');
    }

    public static function send($userName, $userConnections, $room, $data, $type)
    {
        foreach ($userConnections as $connectionUserName => $userConnection) {
            $dataSend = json_encode([
                'type' => $type,
                'from' => $userName,
                'room' => $room,
                'text' => $data,
            ]);
            $userConnection->send($dataSend);
        }
    }
}
