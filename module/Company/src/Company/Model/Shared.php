<?php
namespace Company\Model;

function updateIfSet($object, $default)
{
    if (isset($object)) {
        return $object;
    }
    return $default;
}
