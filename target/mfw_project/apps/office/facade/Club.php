<?php
/**
 * Created by PhpStorm.
 * User: jing
 * Date: 2017/5/26
 * Time: 14:55
 */

namespace apps\office;
class MFacade_Club
{
    CONST TYPE_BADMINTON = 1; //羽毛球
    CONST TYPE_SKIING = 2; //滑雪
    CONST TYPE_BASKETBALL = 3; //篮球
    CONST TYPE_FOOTBALL = 4; //足球
    CONST TYPE_TENNIS = 5; //网球
    CONST TYPE_TRAINING = 6; //培训
    CONST TYPE_BILLIARD = 7; //台球
    CONST TYPE_MOVIE = 8; //电影
    CONST TYPE_SWIMMING = 9; //游泳
    CONST TYPE_WELFARE = 10; //福利
    CONST TYPE_YOGA = 11; //瑜伽
    CONST TYPE_WOLFKILL = 12; //狼人杀
    CONST TYPE_OTHER = 100; //其它

    public static $type_names = array(
        self::TYPE_BADMINTON => '羽毛球',
        self::TYPE_BASKETBALL => '篮球',
        self::TYPE_SWIMMING => '游泳',
        self::TYPE_TRAINING => '培训',
        self::TYPE_TENNIS => '网球',
        self::TYPE_SKIING => '滑雪',
        self::TYPE_WELFARE => '员工福利',
        self::TYPE_WOLFKILL => '狼人杀',
        self::TYPE_MOVIE => '电影',
        self::TYPE_FOOTBALL => '足球',
        self::TYPE_BILLIARD => '台球',
        self::TYPE_YOGA => '瑜伽',
        self::TYPE_OTHER => '其它',
    );

    CONST APPLY_STATUS_PENDING = 0; //待确认
    CONST APPLY_STATUS_CONFIRM = 1; //已确认
    CONST APPLY_STATUS_CANCEL = 2; //已取消
    CONST APPLY_STATUS_PRESENT = 3;//已参加
    CONST APPLY_STATUS_ABSENT = 4; //缺席
    CONST APPLY_STATUS_LEAVE = 5;//请假

    public static function aGet($id)
    {
        $api = new MClubApi();
        return $api->aGet($id);
    }

    public static function aGetList($type = 0, $num = 5, $bDesc = true, $startTime = '', $endTime = '')
    {
        $api = new MClubApi();
        return $api->aGetList($type, $num, $bDesc, $startTime, $endTime);
    }

    public static function iAddActivity($aData)
    {
        $api = new MClubApi();
        return $api->iAddActivity($aData);
    }

    public static function iEditActivity($id, $aData)
    {
        $api = new MClubApi();
        return $api->iEditActivity($id, $aData);
    }

    public static function aGetApply($id)
    {
        $api = new MClubApi();
        return $api->aGetApply($id);
    }

    public static function aGetUserApply($uid, $aid = 0, $limit = 0)
    {
        $api = new MClubApi();
        return $api->aGetUserApply($uid, $aid, $limit);
    }

    /**
     * @param int $aid
     * @param bool|true $onlyConfirmed
     * @return mixed
     * 已报名被确认的人数
     */
    public static function iGetApplyCount($aid = 0, $onlyConfirmed = true)
    {
        $api = new MClubApi();
        return $api->iGetApplyCount($aid, $onlyConfirmed);
    }

    /**
     * @param int $aid
     * @return mixed
     * 有效报名人数
     */
    public static function iGetConfirmedApplyCount($aid = 0)
    {
        $api = new MClubApi();
        return $api->iGetConfirmedApplyCount($aid);
    }

    /**
     * @param $aid
     * @param int $num
     * @param null $status
     * @return mixed
     * 或得报名列表
     */
    public static function aGetApplyList($aid, $num = 0, $status = null)
    {
        $api = new MClubApi();
        return $api->aGetApplyList($aid, $num, $status);
    }

    public static function iDelApplyListByAid($aid)
    {
        $api = new MClubApi();
        return $api->iDelApplyListByAid($aid);
    }

    public static function iAddApply($aData)
    {
        $api = new MClubApi();
        return $api->iAddApply($aData);
    }

    public static function iEditApply($id, $aData)
    {
        $api = new MClubApi();
        return $api->iEditApply($id, $aData);
    }

    public static function getActivityStats($type, $loginUid, $start_time, $end_time)
    {
        $api = new MClubApi();
        return $api->getActivityStats($type, $loginUid, $start_time, $end_time);
    }
}