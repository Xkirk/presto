<?php
/**
 * Created by PhpStorm.
 * Date: 2017/5/25
 * Time: 下午6:19
 */

namespace apps\pay_core\esign;

/**
 * Class MFacade_OrgNotifyApi
 * @method  static MControl_OrgNotifyApi Control_OrgNotifyApi
 * @package apps\pay_core\esign
 */
class MFacade_OrgNotifyApi extends  Mlib_BaseApi
{

    public static function orgAuthNotify($code)
    {
        return self::Control_OrgNotifyApi()->orgAuthNotify($code);
    }

}