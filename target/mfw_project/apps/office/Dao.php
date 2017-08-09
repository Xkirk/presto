<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2016/12/19
 * Time: 下午1:53
 */
namespace apps\office;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'airData' => array(
            'type' => 'db_single',
            'kind' => 'office_air_data',
            'key' => 'device_id',
        ),
        'airLog' => array(
            'type' => 'db_split',
            'kind' => 'office_air_log',
            'key' => 'id',
            'split' => 'device_id',
        ),
        'insterestClubApply' => array(
            'type' => 'db_single',
            'kind' => 'insterest_club_apply',
            'key' => 'id',
        ),
        'activeInfo' => array(
            'type' => 'db_single',
            'kind' => 'insterest_club_active_record',
            'key' => 'id',
        ),
    );
}