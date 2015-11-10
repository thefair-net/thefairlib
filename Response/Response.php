<?php
namespace TheFairLib\Response;

class Response
{
    public static function Json($array = [], $callback = null)
    {
        header('Content-Type: application/json; charset=utf8');
        $data = json_encode($array, JSON_UNESCAPED_UNICODE);
        if (isset($callback)) {
            echo $callback . "({$data});";
        } else {
            echo $data;
        }
    }
}