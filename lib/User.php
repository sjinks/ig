<?php

namespace WildWolf;

class User
{
    private $id;
    private $fbid;
    private $phone;
    private $whitelisted;
    private $paid;
    private $banned;
    private $credits;
    private $lastseen;
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

    public static function mock()
    {
        static $data = [
            'id'          => 0,
            'fbid'        => '',
            'phone'       => '+3800000000',
            'whitelisted' => 0,
            'paid'        => 0,
            'banned'      => 0,
            'credits'     => 3,
            'lastseen'    => '0000-00-00',
            'token'       => '',
        ];

        return new self((object)$data);
    }

    public function id()
    {
        return $this->id;
    }

    public function fbid()
    {
        return $this->fbid;
    }

    public function phone()
    {
        return $this->phone;
    }

    public function isPrivileged()
    {
        return $this->whitelisted || $this->paid;
    }

    public function isWhitelisted()
    {
        return $this->whitelisted > 0;
    }

    public function isPaid()
    {
        return $this->paid;
    }

    public function isBanned()
    {
        return $this->banned;
    }

    public function credits()
    {
        return $this->credits;
    }

    public function setCredits($v)
    {
        $this->credits = (int)$v;
    }

    public function lastSeen()
    {
        return new \DateTime($this->lastseen);
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
}
