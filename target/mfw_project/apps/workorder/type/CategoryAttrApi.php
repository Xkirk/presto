<?php

/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 15/11/4
 * Time: 下午2:28
 */

namespace apps\workorder\type;

/**
 * @property \Ko_Dao_Config $attrDao
 */
class MCategoryAttrApi extends \Ko_Busi_Api
{
    protected $_aConf = array(
        'item' => 'workorder_category_attr',
    );

    protected $_aFieldsConf = array(
        'id' => '',
        'name' => '属性名',
        'category_id' => '分类id',
        'sort' => '排序值',
    );
}