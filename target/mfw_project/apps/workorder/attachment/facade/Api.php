<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/10/16
 * Time: 17:42
 */

namespace apps\workorder\attachment;

class MFacade_Api
{
    public static function bAddAttachment($data)
    {
        return \apps\workorder\attachment\MApi::bAddAttachment($data);
    }

    public static function aGetAttachmentListById($id)
    {
        return \apps\workorder\attachment\MApi::aGetAttachmentListById($id);
    }

    public static function bDelAttachment($id)
    {
        return \apps\workorder\attachment\MApi::bDelAttachment($id);
    }
}