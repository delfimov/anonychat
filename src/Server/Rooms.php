<?php

namespace AnonyChat\Server;

class Rooms
{
    private $rooms = [];
    private $timeOutStorage = [];

    const TIMEOUT = 7200;

    public function add($userName, $roomName): void
    {
        if (!isset($this->rooms[$roomName])) {
            $this->rooms[$roomName] = [];
            $this->timeOutStorage[$roomName] = time();
        }
        $this->rooms[$roomName][] = $userName;
    }

    public function getRooms(): array
    {
        return $this->rooms;
    }

    public function removeByUsername($userName)
    {
        $roomName = $this->findByUsername($userName);
        if (!empty($roomName)) {
            $index = array_search($userName, $this->rooms[$roomName]);
            unset($this->rooms[$roomName][$index]);
            if (empty($this->rooms[$roomName]) == 0) {
                unset($this->rooms[$roomName]);
            }
        }
    }

    public function findByUsername($userName)
    {
        foreach ($this->rooms as $roomName => $userNames) {
            if (in_array($userName, $userNames)) {
                return $roomName;
            }
        }
        return null;
    }

    public function getUsers($roomName)
    {
        return $this->rooms[$roomName] ?? [];
    }

    public function clean()
    {
        foreach ($this->timeOutStorage as $roomName => $time) {
            if ($time + self::TIMEOUT > time()) {
                unset($this->timeOutStorage[$roomName]);
                unset($this->rooms[$roomName]);
            }
        }
    }
}
