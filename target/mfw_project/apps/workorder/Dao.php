<?php

namespace apps\workorder;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'info' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_info',
            'key'   => 'id',
        ),
    );
}