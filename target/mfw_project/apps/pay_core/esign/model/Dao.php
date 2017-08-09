<?php
namespace apps\pay_core\esign;
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/6/6
 * Time: 下午1:44
 */
class MModel_Dao extends \Ko_Dao_Factory
{

    protected $_aDaoConf = array(
        'user' => array(
            'type' => 'db_single',
            'kind' => 'esign_user',
            'key' => 'id'
        ),
        'save' => array(
            'type' => 'db_single',
            'kind' => 'esign_save',
            'key' => 'id'
        ),

        'limit' => array(
            'type' => 'db_single',
            'kind' => 'pay_core_limit',
            'key' => array('name', 'action'),
        ),
        'reqLog' => array(
            'type' => 'db_single',
            'kind' => 'esign_req_log',
            'key' => 'log_id',
        ),
    );
}