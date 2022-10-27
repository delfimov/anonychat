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

    public function getRoomTimeout($roomName)
    {
        if (isset($this->timeOutStorage[$roomName])) {
            return time() - $this->timeOutStorage[$roomName] - self::TIMEOUT;
        } else {
            return 0;
        }
    }

    public function removeByUsername($userName)
    {
        $roomName = $this->findByUsername($userName);
        if (!empty($roomName)) {
            $index = array_search($userName, $this->rooms[$roomName]);
            unset($this->rooms[$roomName][$index]);
            if (count($this->rooms[$roomName]) == 0) {
                unset($this->rooms[$roomName]);
                unset($this->timeOutStorage[$roomName]);
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

    public function getUsers($roomName): array
    {
        return $this->rooms[$roomName] ?? [];
    }

    public function clean($roomName): array
    {
        $cleanUsers = [];
        $time = $this->timeOutStorage[$roomName] ?? 0;
        if ($time + self::TIMEOUT < time()) {
            $cleanUsers = $this->rooms[$roomName] ?? [];
            unset($this->timeOutStorage[$roomName]);
            unset($this->rooms[$roomName]);
        }
        return $cleanUsers;
    }
}
