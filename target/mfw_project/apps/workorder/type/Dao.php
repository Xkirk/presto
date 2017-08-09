<?php

namespace apps\workorder\type;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'business' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_business_type',
            'key'   => 'id',
        ),
        'mapping' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_category_mapping',
            'key'   => 'id',
        ),
        'category' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_category',
            'key'   => 'id',
        ),
        'type' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_category_type',
            'key'   => 'id',
        ),
        'attr' => array(
            'type'  => 'db_single',
            'kind'  => 'workorder_category_attr',
            'key'   => 'id',
        ),
    );
}