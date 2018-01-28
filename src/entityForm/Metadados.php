<?php

namespace EntityForm;

use \Helpers\Helper;

class Metadados
{
    /**
     * @param string $entity
     * @return mixed
     */
    public static function getDicionario($entity)
    {
        $path = PATH_HOME . "entity/cache/" . $entity . '.json';
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
        if($data) {
            unset($data[0]);
            return Helper::convertStringToValueArray($data);
        }

        return null;
    }

    /**
     * @param string $entity
     * @return mixed
     */
    public static function getInfo($entity)
    {
        $path = PATH_HOME . "entity/cache/info/" . $entity . '.json';
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
        if($data)
            return Helper::convertStringToValueArray($data);

        return null;
    }
}