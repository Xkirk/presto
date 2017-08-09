<?php

namespace apps\coin;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'record' => array(
            'type' => 'db_single',
            'kind' => 'coin_record',
            'key' => 'id',
        ),
        'redis' => array(
            'type' => 'redis'
        ),
    );
}
