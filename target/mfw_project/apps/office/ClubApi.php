<?php

/**
 * Created by PhpStorm.
 * User: jing
 * Date: 15/12/31
 * Time: 14:48
 */

namespace apps\office;

class MClubApi extends \Ko_Busi_Api
{
    public function aGet($id)
    {
        return $this->activeInfoDao->aGet($id);
    }

    public function aGetList($type = 0, $num = 5, $bDesc = true, $startTime = '', $endTime = '')
    {
        $option = new \Ko_Tool_SQL();
        if ($type > 0) {
            $option->oWhere('type=?', $type);
        }
        if ($startTime) {
            $option->oAnd('start_time>=?', $startTime);
        }
        if ($endTime) {
            $option->oAnd('end_time<=?', $endTime);
        }
        $option->oOrderBy('start_time ' . ($bDesc ? 'desc' : "asc"));
        if ($num) {
            $option->oLimit($num);
        }
        return $this->activeInfoDao->aGetList($option);
    }

    public function iAddActivity($aData)
    {
        return $this->activeInfoDao->iInsert($aData);
    }

    public function iEditActivity($id, $aData)
    {
        return $this->activeInfoDao->iUpdate($id, $aData);
    }

    public function aGetApply($id)
    {
        return $this->insterestClubApplyDao->aGet($id);
    }

    public function aGetUserApply($uid, $aid = 0, $limit = 0)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('uid=?', $uid);
        if ($aid) {
            $option->oAnd('aid=?', $aid);
        }
        if ($limit) {
            $option->oLimit($limit);
        }
        $option->oOrderBy('ctime asc');
        return $this->insterestClubApplyDao->aGetList($option);
    }

    /**
     * @param int $aid
     * @param bool|true $onlyConfirmed
     * @return mixed
     * 已报名被确认的人数
     */
    public function iGetApplyCount($aid = 0, $onlyConfirmed = true)
    {
        $option = new \Ko_Tool_SQL();
        $option->oSelect('count(1) as cnt');
        $option->oAnd('aid=?', $aid);
        if ($onlyConfirmed) {
            $option->oAnd('status not in(?)', array(
                MFacade_Club::APPLY_STATUS_PENDING,
                MFacade_Club::APPLY_STATUS_CANCEL
            ));
        }
        $ret = $this->insterestClubApplyDao->aGetList($option);
        return $ret[0]['cnt'];
    }

    /**
     * @param int $aid
     * @return mixed
     * 有效报名人数
     */
    public function iGetConfirmedApplyCount($aid = 0)
    {
        $option = new \Ko_Tool_SQL();
        $option->oSelect('count(1) as cnt');
        $option->oAnd('aid=?', $aid);
        $option->oAnd('status in(?)', array(MFacade_Club::APPLY_STATUS_CONFIRM, MFacade_Club::APPLY_STATUS_PRESENT));
        $ret = $this->insterestClubApplyDao->aGetList($option);
        return $ret[0]['cnt'];
    }

    /**
     * @param $aid
     * @param int $num
     * @param null $status
     * @return mixed
     * 或得报名列表
     */
    public function aGetApplyList($aid, $num = 0, $status = null)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('aid=?', $aid);
        if (is_numeric($status)) {
            $option->oAnd('status=?', $status);
        } else if (is_array($status) && count($status) > 0) {
            $option->oAnd('status in (?)', $status);
        }
        $option->oOrderBy('find_in_set(status,"'
            . MFacade_Club::APPLY_STATUS_CONFIRM . ','
            . MFacade_Club::APPLY_STATUS_PRESENT . ','
            . MFacade_Club::APPLY_STATUS_ABSENT . ','
            . MFacade_Club::APPLY_STATUS_PENDING . ','
            . MFacade_Club::APPLY_STATUS_LEAVE . ','
            . MFacade_Club::APPLY_STATUS_CANCEL . '"),ctime asc');
        if ($num) {
            $option->oLimit($num);
        }
        return $this->insterestClubApplyDao->aGetList($option);
    }

    public function iDelApplyListByAid($aid)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('aid=?', $aid);
        return $this->insterestClubApplyDao->iDeleteByCond($option);
    }

    public function iAddApply($aData)
    {
        return $this->insterestClubApplyDao->iInsert($aData);
    }

    public function iEditApply($id, $aData)
    {
        return $this->insterestClubApplyDao->iUpdate($id, $aData);
    }

    public function getActivityStats($type, $loginUid = 0, $start_time = '', $end_time = "")
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('type=?', $type);
        if ($start_time) {
            $start_time = date('Y-m-d H:i:s', strtotime($start_time));
            $option->oAnd('start_time >=?', $start_time);
        }
        if ($end_time) {
            $end_time = date('Y-m-d H:i:s', strtotime($end_time));
            $option->oAnd('start_time <=?', $end_time);
        }
        $option->oOrderBy('start_time desc');
        $activities = $this->activeInfoDao->aGetList($option);
        $list = array(
            'times' => 0,
            'firstTime' => '',
            'lastTime' => '',
            'userRec' => array(),
        );
        $users = array();

        $lastStraightRec = array();
        foreach ($activities as $i => $activity) {
            if ($loginUid > 0) {
                $records = $this->aGetUserApply($loginUid, $activity['id']);
            } else {
                $records = $this->aGetApplyList($activity['id']);
            }
            $straightRec = array();
            foreach ($records as $record) {
                $uid = $record['uid'];
                $users[$uid]['uid'] = $uid;
                $users[$uid]['times_' . $record['status']]++;
                if (($i == 0 || $lastStraightRec[$uid]) && $record['status'] == MFacade_Club::APPLY_STATUS_PRESENT) {
                    $users[$uid]['straight_present']++;
                    $straightRec[$uid] = true;
                }
            }
            $lastStraightRec = $straightRec;
            $i == 0 && $list['lastTime'] = strtotime($activity['start_time']);
            $i == count($activities) - 1 && $list['firstTime'] = strtotime($activity['start_time']);
            $list['times']++;
        }
        $order1 = $order2 = $order3 = array();
        foreach ($users as $user) {
            $order1[] = $user['times_' . MFacade_Club::APPLY_STATUS_PRESENT];
            $order2[] = $user['straight_present'];
            $order3[] = count($order1);
        }
        array_multisort($order1, SORT_DESC, $order2, SORT_DESC, $order3, SORT_ASC, $users);
        $list['userRec'] = \Ko_Tool_Utils::AObjs2map($users, 'uid');
        return $list;
    }
}