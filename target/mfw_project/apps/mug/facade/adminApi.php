<?php
/**
 * Created by PhpStorm.
 * User: lixin
 * Date: 17/06/12
 * Time: 上午11:05
 */
namespace apps\mug;

class MFacade_adminApi
{


    public function getGroupInfoById($id){
        return \apps\MFacade_Mug_Api::getGroupInfoById($id);
    }

    public function getGroupInfoList($keyword="",$status="all",$start=0,$end=null,$didType="all"){

        return \apps\MFacade_Mug_Api::getGroupInfoList($keyword,$status,$start,$end,$didType);
    }

    public function getDeviceListByGroupId($id){
        $mql="select distinct did from ups.mug where gid='{$id}'";
        $status="0";
        $err_msg="";
        $data=array();
        try{
            $res=\apps\presto\MFacade_Api::dosql($mql);
            foreach ($res as $val){
                $data[]=$val[0];
            }
        }catch (Exception $e) {
            $err_msg = $e->getMessage();
            $status='-3';
        }
        return array(
            "status"=>$status,
            "data"=>$data,
            "msg"=>$err_msg
        );
    }
}