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

        $data = [
            'type' => $type
        ];

        Cookie::queue('data', $data, 1000);
    }
}