<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 15/11/4
 * Time: 下午2:28
 */
namespace apps\workorder\type;

/**
 * @property \Ko_Dao_Config $typeDao
 */
class MCategoryTypeApi extends \Ko_Busi_Api
{
    protected $_aConf = array(
        'item' => 'workorder_category_type',
    );

    protected $_aFieldsConf = array (
        'id' => '',
        'name' => '分类类型名',
    );
}