<?php
namespace apps\office;

class MDelivery_ApplyApi extends \Ko_Busi_Api
{
	public function aGetList($query, $uid = 0, $departmentId = 0)
	{
	    $option = new \Ko_Tool_SQL();

		if($uid != 0)
            $option->oAnd('uid=?', $uid);

		if($departmentId!= 0)
            $option->oAnd('department_id=?', $departmentId);

        $option->oAnd('tag !=?', 5);//申请失败
		$offset = ($query['page'] - 1) * $query['pagesize'];
        $option->oOffset($offset)->oLimit($query['pagesize']);
        $option->oOrderBy('ctime desc');
		$option->oCalcFoundRows(true);
        return array('list' => $this->applyDao->aGetList($option), 'total' => $option->iGetFoundRows());
	}

	public function iInsert()
	{
	
	}
}
