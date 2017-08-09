<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/10/16
 * Time: 16:15
 */
namespace apps\workorder\type;

class MFacade_Api
{
    private static $_oCategory;
    private static $_oCategoryType;
    private static $_oCategoryAttr;

    private static $_oBusinessType;

    private static $_oCategoryMapping;

    public static function oGetCategory()
    {
        if (!is_object(self::$_oCategory)) {
            self::$_oCategory = new \apps\workorder\type\MCategoryApi();
        }
        return self::$_oCategory;
    }

    public static function oGetCategoryType()
    {
        if (!is_object(self::$_oCategoryType)) {
            self::$_oCategoryType = new \apps\workorder\type\MCategoryTypeApi();
        }
        return self::$_oCategoryType;
    }

    public static function oGetCategoryAttr()
    {
        if (!is_object(self::$_oCategoryAttr)) {
            self::$_oCategoryAttr = new \apps\workorder\type\MCategoryAttrApi();
        }
        return self::$_oCategoryAttr;
    }

    public static function oGetBusinessType()
    {
        if (!is_object(self::$_oBusinessType)) {
            self::$_oBusinessType = new \apps\workorder\type\MBusinessTypeApi();
        }
        return self::$_oBusinessType;
    }

    public static function oGetCategoryMapping()
    {
        if (!is_object(self::$_oCategoryMapping)) {
            self::$_oCategoryMapping = new \apps\workorder\type\MCategoryMappingApi();
        }
        return self::$_oCategoryMapping;
    }

    public static function iAddCategory($data)
    {
        $id = self::oGetCategory()->categoryDao->iInsert($data);
    }

    public static function iDelCategory($id)
    {
        self::oGetCategory()->categoryDao->iDelete($id);
    }

    public static function aGetCategoryParentStep($categoryId)
    {
        $categorys = array();
        while (true) {
            if (!$categoryId) break;
            $data = self::aGetCategoryList($categoryId);
            if (empty($data)) break;
            $categorys[] = $data[0];
            $categoryId = $data[0]['parent_id'];
        }

        krsort($categorys);

        return $categorys;

    }

    public static function aGetCategoryById($id)
    {
        return self::oGetCategory()->categoryDao->aGet($id);
    }

    public static function aGetCategoryTypeById($id)
    {
        return self::oGetCategoryType()->typeDao->aGet($id);
    }

    public static function aGetCategoryList($option)
    {
        $orderList = self::oGetCategory()->categoryDao->aGetList($option);
        return $orderList;
    }

    public static function aGetCategoryTree($type)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('type_id = ?', $type);
        $categories = self::aGetCategoryList($option);

        $categories = \Ko_Tool_Utils::AObjs2map($categories, 'id');

        foreach ($categories as $category)
            $categories[$category['parent_id']]['son'][$category['id']] = & $categories[$category['id']];

        return isset($categories[0]['son']) ? $categories[0]['son'] : array();
    }


    public static function aGetCategoryListByLevel($type, $sOffset = '', $exclude = array())
    {
        $aCategoryTree = self::aGetCategoryTree($type);

        $exclude = (array)$exclude;
        $recursiveFun = function ($v, $k) use (&$list, $sOffset, &$recursiveFun, $exclude) {

            if (!empty($exclude) && in_array($k, $exclude)) return;

            $item = & $list[$k];
            $item = $v;
            $itemParent = $list[$v['parent_id']];
            $item['level']++;

            if ($v['parent_id']) {
                if (!isset($itemParent['offset'])) {
                    $item['offset'] = $sOffset;
                } else {
                    $item['offset'] .= $itemParent['offset'] . substr($sOffset, 1);
                }
            }

            if (!empty($v['son'])) {
                array_walk($v['son'], $recursiveFun);
            }
        };
        $list = array();
        array_walk($aCategoryTree, $recursiveFun);

        return $list;
    }

    public static function sGetCategoryName($value)
    {
        if ($value > 0) {
            $data = self::aGetCategoryList($value);
            if ($data) {
                $categoryStep = self::aGetCategoryParentStep($value);
                $categoryNames = array();
                array_walk($categoryStep, function ($v) use (&$categoryNames) {
                    $categoryNames[] = $v['name'];
                });
                $value = implode('●', $categoryNames);
            }
        } else {
            $value = '无';
        }
        return $value;
    }


    public static function bUpdateCategory($id, $data)
    {
        $r = self::oGetCategory()->categoryDao->iUpdate($id, $data);
        return $r;

    }

    /**
     * 获取分类分组信息
     * @param $option
     * @return array
     */
    public static function aGetCategoryTypeList($option)
    {
        $orderList = self::oGetCategoryType()->typeDao->aGetList($option);
        return $orderList;
    }

    /**
     * 插入工单分组信息
     * @param $data
     */
    public static function iAddCategoryType($data)
    {
        $id = self::oGetCategoryType()->typeDao->iInsert($data);
    }

    /**
     * 修改分组信息
     * @param $id
     * @param $data
     */
    public static function iUpdateCategoryType($id, $data)
    {
        self::oGetCategoryType()->typeDao->iUpdate($id, $data);
    }

    /**
     * 删除分组信息
     * @param $id
     */
    public static function iDeleteCategoryType($id)
    {
        self::oGetCategoryType()->typeDao->iDelete($id);
    }

    /**
     * 插入分类属性
     * @param $data
     * @return int|void
     */
    public static function iAddCategoryAttr($data)
    {
        return self::oGetCategoryAttr()->attrDao->iInsert($data);
    }

    /**
     * 修改分类属性
     * @param $id
     * @param $data
     * @return int|void
     */
    public static function iUpdateCategoryAttr($id, $data)
    {
        return self::oGetCategoryAttr()->attrDao->iUpdate($id, $data);
    }

    /**
     * 得到某个分类下的所有属性
     * @param $option
     * @return array
     */
    public static function aGetCategoryAttrList($option)
    {
        $orderList = self::oGetCategoryAttr()->attrDao->aGetList($option);
        return $orderList;
    }

    /**
     * 删除属性
     * @param $id
     */
    public static function iDeleteCategoryAttr($id)
    {
        self::oGetCategoryAttr()->attrDao->iDelete($id);
    }


    // 业务线类型相关增删改查方法
    public static function aGetBusinessTypeById($id)
    {
        return self::oGetBusinessType()->businessDao->aGet($id);
    }

    public static function aGetBusinessTypeList()
    {
        $option = new \Ko_Tool_SQL();
        $option->oSelect('*');
        return self::oGetBusinessType()->businessDao->aGetList($option);
    }

    public static function iAddBusinessType($data)
    {
        self::oGetBusinessType()->businessDao->iInsert($data);
    }

    public static function iUpdateBusinessType($id, $data)
    {
        self::oGetBusinessType()->businessDao->iUpdate($id, $data);
    }

    public static function iDeleteBusinessType($id)
    {
        self::oGetBusinessType()->businessDao->iDelete($id);
    }


    // 业务线和具体工单分类映射关系的相关增删改查方法
    public static function aGetCategoryMaping($option)
    {
        return self::oGetCategoryMapping()->mappingDao->aGetList($option);
    }

    public static function aGetCategoryMapingListByBusinessId($id)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('business_id = ?', $id);
        return self::oGetCategoryMapping()->mappingDao->aGetList($option);
    }

    public static function iAddCategoryMapping($data)
    {
        self::oGetCategoryMapping()->mappingDao->iInsert($data);
    }

    public static function iUpdateCategoryMapping($id, $data)
    {
        self::oGetCategoryMapping()->mappingDao->iUpdate($id, $data);
    }

    public static function iDeleteCategoryMapping($id)
    {
        self::oGetCategoryMapping()->mappingDao->iDelete($id);
    }
}