<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 16/3/3
 * Time: 11:41
 */

namespace apps\workorder\type;

/**
 * @property \Ko_Dao_Config $mappingDao
 */
class MCategoryMappingApi extends \Ko_Busi_Api
{
    protected $_aConf = array(
        'item' => 'workorder_category_mapping',
    );

    protected $_aFieldsConf = array(
        'id' => '',
        'name' => '业务线名称',
        'category_id' => '工单分类id',
        'business_id' => '业务线id',
    );

    public static $_aBussiness = array(
        '1' => '机+酒',
        '2' => '当地游',
        '3' => '有鱼',
        '4' => '小客栈',
        '5' => '签证',
        '6' => '其他',
        '10' => '酒店',
    );
}