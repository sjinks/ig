<?php

namespace WildWolf;

class User
{
    private $id;
//     private $fbid;
//     private $phone;
    private $whitelisted;
    private $paid;
//     private $banned;
    private $credits;
//     private $lastseen;
    private $token;

    public function __construct(\stdClass $obj)
    {
        static $properties = [
            'id', 'fbid', 'phone', 'whitelisted', 'paid', 'banned', 'credits', 'lastseen', 'token'
        ];

        foreach ($properties as $p) {
            $this->$p = $obj->$p;
        }
    }

    public function id()
    {
        return $this->id;
    }

    public function isPrivileged()
    {
        return $this->whitelisted || $this->paid;
    }

    public function isWhitelisted()
    {
        return $this->whitelisted;
    }

    public function logout(\WildWolf\AccountKit $kit)
    {
        try {
            $kit->logout($this->token);
        }
        catch (\Exception $e) {
            // Ignore exception
        }
    }

    public function setCredits($v)
    {
        $this->credits = (int)$v;
    }
}
