<?php

namespace Mof\HexletSlimExample;

class UserRepository
{
    private $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function all()
    {
        return $this->users;
    }

    public function find(string $id)
    {
        return collect($this->users)->firstWhere('id', $id);
    }
    public function destroy(string $id)
    {
        unset($this->users[$id]);
        return $this->users;
    }

    public function save(array $item)
    {
        if (!isset($item['id'])) {
            $item['id'] = uniqid();
        }
        $this->users[$item['id']] = $item;
        return $this->users;
       
    }
}