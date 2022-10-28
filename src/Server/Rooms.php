<?php

declare(strict_types=1);

namespace AnonyChat\Server;

class Rooms
{
    private $rooms = [];
    private $timeOutStorage = [];

    const TIMEOUT = 7200;

    public function add(string $userName, string $roomName): void
    {
        if (!isset($this->rooms[$roomName])) {
            $this->rooms[$roomName] = [];
            $this->timeOutStorage[$roomName] = time();
        }
        $this->rooms[$roomName][] = $userName;
    }

    public function get(string $roomName) // : array|null // this is not a PSR-11 container, so we won't throw exception if the entry wasn't found
    {
        return $this->rooms[$roomName] ?? null;
    }

    public function has(string $roomName): bool
    {
        return isset($this->rooms[$roomName]);
    }

    public function getRoomTimeout(string $roomName): int
    {
        if (isset($this->timeOutStorage[$roomName])) {
            return time() - $this->timeOutStorage[$roomName] - self::TIMEOUT;
        } else {
            return 0;
        }
    }

    public function removeByUsername(string $userName): void
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

    public function findByUsername(string $userName) // : string|null
    {
        foreach ($this->rooms as $roomName => $userNames) {
            if (in_array($userName, $userNames)) {
                return $roomName;
            }
        }
        return null;
    }

    public function getUsers(string $roomName): array
    {
        return $this->rooms[$roomName] ?? [];
    }

    public function clean(string $roomName): array
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
