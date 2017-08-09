<?php
/**
 * Created by PhpStorm.
 * User: lixin
 * Date: 17/06/12
 * Time: 上午11:05
 */
namespace apps\mug;

class MFacade_templateApi
{
    
    //创建模板
    public function create($name,$remark,$type,$config,$did_types)
    {
        return \apps\MFacade_Mug_Api::createTemplate($name,$remark,$type,$config,$did_types);
    }
    
    public function isTemplate($type,$config){
        return \apps\MFacade_Mug_Api::isTemplate($type,$config);
    }

    public function createGroupByTemplate($name,$template_id,$params)
    {
        return \apps\MFacade_Mug_Api::createGroupByTemplate($name,$template_id,$params);
    }
}