<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/9/16
 * Time: 18:54
 */

namespace apps\workorder\type;

/**
 * @property \Ko_Dao_Config $categoryDao
 */
class MCategoryApi extends \Ko_Busi_Api
{
    public static $_aCategories = array(
        '咨询' => array('售前','售后'),
        '变更' => array('姓名','证件','电话','邮箱','酒店','酒店地址','航班号','航班时间','时间'),
        '取消' => array(),
        '投诉' => array('产品与页面不符','价格高','供应商态度','客服态度'),
        '转接' => array('三亚','泰国','台湾'),
    );

    protected $_aConf = array(
        'item' => 'workorder_category',
    );

    protected $_aFieldsConf = array (
        'id' => '',
        'name' => '分类标题',
        'type_id' => '分类类型',
        'parent_id' => '分类父级id',
        'sort' =>'排序值',
    );

    public function aGetCategoryById($id)
    {
        return $this->categoryDao->aGet($id);
    }

    public function aGetCategoryListByPid($iParentId)
    {
        $option = new \Ko_Tool_SQL();
        $option
            ->oWhere('parent_id=?', $iParentId);

        return $this->categoryDao->aGetList($option);
    }
}