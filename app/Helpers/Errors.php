<?php


namespace App\Helpers;

class Errors
{
    /**
     * @param $code
     * @return string
     */
    public function message($code): string
    {
        switch ($code) {
            case 1:
                $message = '1';
                break;
            default:
                $message = '2';
                break;
        }

        return $message;
    }
}