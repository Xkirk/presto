<?php
namespace apps\finance;
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/6/14
 * Time: 下午1:35
 */

class MFacade_Tools_Api
{
    /**
     * @param array $data
     * @param array $title
     * @param string $name
     * @param array $properties
     * @notice 使用方式
     *  $data = array(
            array('奥迪s3', '34.05')
        );
        $title = array("名称" => 'string', "价格"=>"price"); or array("名称" ,"价格")
        $name = '汽车';
        $properties = array(
            'keywords' => 'keywords', //excel关键词属性
            ...
        );
     */
    public static function vOutputExcel($data = array(), $title = array(), $name = 'output', $properties = array())
    {
        MLib_Output_Excel::vOutput($data, $title, $name, $properties);
    }
}