<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 16/3/4
 * Time: 15:15
 */

namespace apps\workorder\type;

/**
 * @property \Ko_Dao_Config $businessDao
 */
class MBusinessTypeApi extends \Ko_Busi_Api
{
    protected $_aConf = array(
        'item' => 'workorder_business_type',
    );

    protected $_aFieldsConf = array (
        'id' => '',
        'name' => '业务类型名',
    );
}