<?php

class PerooonModel
{
    public static function updateCount()
    {
        $db = Database::grow();
        $db->query('UPDATE perooon set times = times + 1;');
    }
}
