<?php

namespace AnonyChat\Server;

class Users
{
    const TIMEOUT = 7200;
    private $users = [];
    private $timeOutStorage = [];

    public function add($connection, $userName): void
    {
        if (isset($users[$userName]) && $connection != $users[$userName]) {
            $userName .= '_' . time();
        }
        $this->users[$userName] = $connection;
        $this->usersTimeOutStorage[$userName] = time();
    }

    public function getConnectionsByUsernames(array $userNames): array
    {
        $connections = [];
        foreach ($this->users as $userName => $connection) {
            if (in_array($userName, $userNames)) {
                $connections[] = $connection;
            }
        }
        return $connections;
    }

    public function getByUsername($username)
    {
        return $this->users[$username] ?? null;
    }

    public function findByConnection($connection)
    {
        return array_search($connection, $this->users);
    }

    public function removeByConnection($connection)
    {
        $userName = $this->findByConnection($connection);
        if (!empty($userName)) {
            unset($this->users[$userName]);
        }
    }

    public function clean()
    {
        foreach ($this->timeOutStorage as $userName => $time) {
            if ($time + self::TIMEOUT > time()) {
                unset($this->timeOutStorage[$userName]);
                unset($this->users[$userName]);
            }
        }
    }
}
