<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 15/10/20
 * Time: 下午3:07
 */

namespace apps\workorder\attachment;

/**
 * @property \Ko_Dao_Config $attachmentDao
 */
class MApi extends \Ko_Busi_Api
{
    private static $_oAttachment;

    public static function oGetAttachment()
    {
        if (!is_object(self::$_oAttachment)) {
            self::$_oAttachment = new self();
        }
        return self::$_oAttachment;
    }

    public static function bAddAttachment($data)
    {
        return self::oGetAttachment()->attachmentDao->iInsert($data);
    }

    public static function aGetAttachmentListById($id)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('workorder_id = ? and del = ?', $id, 0);

        return self::oGetAttachment()->attachmentDao->aGetList($option);
    }

    public static function bDelAttachment($id)
    {
        return self::oGetAttachment()->attachmentDao->iUpdate($id, array('del' => 1));
    }

}