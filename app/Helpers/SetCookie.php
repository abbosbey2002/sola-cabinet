<?php


namespace App\Helpers;
use Illuminate\Support\Facades\Cookie;


class SetCookie
{

    /**
     * @param int $type
     */
    public function AbonentType(int $type)
    {
        //0 - временный
        //1 - разовый
        //2 - постоянный

        $data = json_encode([
            'type' => $type
        ]);

        Cookie::queue('data', $data, 1000);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        $data = request()->cookie('data');
        $json = json_decode($data, true);

        return $json['type'];
    }

    /**
     *
     */
    public function verifyUser()
    {
        Cookie::queue('verify', true, 1000);
    }

    /**
     * @param $login
     */
    public function setLogin($login)
    {
        Cookie::queue('login', $login, 1000);
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return (string) Cookie::get('login');
    }

    /**
     * @param $id
     */
    public function setAccID($id)
    {
        Cookie::queue('account', $id, 1000);
    }

    /**
     * @return string
     */
    public function getAccID(): string
    {
        return (string) Cookie::get('account');
    }

    /**
     *
     */
    public function logout()
    {
        Cookie::queue(Cookie::forget('account'));
        Cookie::queue(Cookie::forget('login'));
        Cookie::queue(Cookie::forget('verify'));
        Cookie::queue(Cookie::forget('data'));
    }

    /**
     * @param string $lang
     */
    public function setLang(string $lang)
    {
        Cookie::queue('lang', $lang, 10000);
    }
}