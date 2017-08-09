<?php

namespace apps\workorder\record;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'record' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_record',
            'key'   => 'id',
        ),
        'log' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_log',
            'key'   => 'id',
        ),
    );
}