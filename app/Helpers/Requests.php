<?php


namespace App\Helpers;
use GuzzleHttp\Client;
use App\Helpers\SetCookie;

class Requests
{
    protected $pbhkIdent;
    protected $client;
    protected $cookie;

    public function __construct(SetCookie $cookie)
    {
        $port = (int)($_SERVER['REMOTE_PORT'] / 16);
        $this->pbhkIdent = "S{$_SERVER['REMOTE_ADDR']}:{$port}";
        $this->client = new Client();
        $this->cookie = $cookie;
    }
}