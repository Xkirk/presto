<?php

namespace apps\workorder\attachment;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'attachment' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_attachment',
            'key'   => 'id',
        ),
    );
}