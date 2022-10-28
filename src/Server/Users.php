<?php

namespace AnonyChat\Server;

class Users
{
    const TIMEOUT = 7200;
    const RAND_USER_SUFFIX = 8;
    private $users = [];
    private $timeOutStorage = [];

    /**
     * @param mixed $connection could be anything, this class is just a container
     * @param string $userName
     * @return string
     */
    public function add($connection, string $userName): string
    {
        if ($this->has($userName) && $connection != $this->get($userName)) {
            $userName .= '_' . substr(md5(uniqid(mt_rand(), true)), 0, self::RAND_USER_SUFFIX);;
        }
        $this->users[$userName] = $connection;
        $this->timeOutStorage[$userName] = time();
        return $userName;
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

    public function get(string $username) // : connection|null
    {
        return $this->users[$username] ?? null;
    }

    public function has(string $username): bool
    {
        return isset($this->users[$username]);
    }

    /**
     * @param mixed $connection could be anything, this class is just a container
     * @return false|int|string
     */
    public function findByConnection($connection)
    {
        return array_search($connection, $this->users);
    }

    /**
     * @param $connection
     * @return void
     */
    public function removeByConnection($connection)
    {
        $userName = $this->findByConnection($connection);
        if (!empty($userName)) {
            unset($this->users[$userName]);
        }
    }

    public function removeByUsername(string $userName): void
    {
        if (isset($this->users[$userName])) {
            unset($this->users[$userName]);
        }
    }

    public function clean(array $userNames): array
    {
        $cleanUsers = [];
        foreach ($userNames as $userName) {
            $time = $this->timeOutStorage[$userName] ?? 0;
            if ($time + self::TIMEOUT < time()) {
                $cleanUsers[] = $userName;
                unset($this->timeOutStorage[$userName]);
                unset($this->users[$userName]);
            }
        }
        return $cleanUsers;
    }
}
