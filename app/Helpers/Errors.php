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
            case 100:
                $message = trans('errors.100');
                break;
            case 101:
                $message = trans('errors.101');
                break;
            case 102:
                $message = trans('errors.102');
                break;
            case 103:
                $message = trans('errors.103');
                break;
            case 109:
                $message = trans('errors.109');
                break;
            case 110:
                $message = trans('errors.110');
                break;
            case 111:
                $message = trans('errors.111');
                break;
            case 112:
                $message = trans('errors.112');
                break;
            case 113:
                $message = trans('errors.113');
                break;
            case 114:
                $message = trans('errors.114');
                break;
            case 115:
                $message = trans('errors.115');
                break;
            case 120:
                $message = trans('errors.120');
                break;
            default:
                $message = 'Success';
                break;
        }

        return $message;
    }
}