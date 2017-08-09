<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 15/10/20
 * Time: 下午3:07
 */

namespace apps\workorder\record;

/**
 * @property \Ko_Dao_Config $recordDao
 */
class MRecordApi extends \Ko_Busi_Api
{
    private static $_oRecord;

    public static function oGetRecord()
    {
        if (!is_object(self::$_oRecord)) {
            self::$_oRecord = new self();
        }
        return self::$_oRecord;
    }


    public static function aGetListByOption($option)
    {
        return self::oGetRecord()->recordDao->aGetList($option);
    }

    public static function bUpdateRecord($id, $data)
    {
        return self::oGetRecord()->recordDao->iUpdate($id, $data);
    }

    public function aGetNoteFormatData($type, $note, $uid)
    {
        if ($type < \apps\workorder\record\MFacade_Api::RECORD_TYPE_ADD_NOTE
            && $type != \apps\workorder\record\MFacade_Api::RECORD_TYPE_CREATE){
            $note = json_decode($note, true);
        }

        $fun = '_aGetNoteFormatDataType' . $type;
        if (method_exists($this, $fun)) {
            return $this->$fun($note, $uid);
        }

        return array();
    }

    private function _aGetNoteFormatDataType101($note, $uid)
    {
        if (isset($note) && !empty($note)) {
            $record['title'] = sprintf("%s 添加了一条备注", \apps\workorder\MFacade_Api::getUserNameById($uid));
            $record['detail'] = $note;
            return $record;
        }
        return array();
    }

    private function _aGetNoteFormatDataType1($note, $uid)
    {
        $olds = $note['old'];
        $updates = $note['update'];
        if (isset($olds) && isset($updates)) {
            $record['title'] = sprintf("%s 修改了工单分类", \apps\workorder\MFacade_Api::getUserNameById($uid));
            $str = '';
            foreach ($olds as $field => $old) {
                $str = $this->_sSetFieldValue($field, $old) . " 修改为 " .
                    "<font color=\"#41c6ad\">" . $this->_sSetFieldValue($field, $updates[$field]) . "</font>";
            }
            $record['detail'] = $str;
            return $record;
        }
        return array();
    }

    private function _sSetFieldValue($field, $value)
    {
        switch ($field) {
            case 'category_id':
                if ($value > 0) {
                    $data = \apps\workorder\type\MFacade_Api::aGetCategoryList(array('id' => $value));

                    if ($data) {
                        $categoryStep = \apps\workorder\type\MFacade_Api::aGetCategoryParentStep($value);
                        $categoryNames = array();
                        array_walk($categoryStep, function ($v) use (&$categoryNames) {
                            $categoryNames[] = $v['name'];
                        });

                        $value = implode('●', $categoryNames);
                    }


                } else {
                    $value = '无';
                }
                break;
            case 'category_attr':
                if ($value) {
                    $data = \apps\workorder\type\MFacade_Api::aGetCategoryAttrList(array(array('id in (?)', $value)));
                    if ($data) {
                        $attrNames = '';
                        array_walk($data, function ($v) use (&$attrNames) {
                            $attrNames[] = $v['name'];
                        });

                        $value = implode(',', $attrNames);
                    }
                } else {
                    $value = '无';
                }

                break;
        }

        return $value;
    }

    private function _aGetNoteFormatDataType2($note, $uid)
    {
        if (isset($note) && !empty($note) && $note['old'] !== $note['update']) {
            $record['title'] = sprintf("%s 更改了工单处理人",
                \apps\workorder\MFacade_Api::getUserNameById($note['old']));
            $record['detail'] = \apps\workorder\MFacade_Api::getUserNameById($note['old']) . " 将工单指派给:"
                . \apps\workorder\MFacade_Api::getUserNameById($note['update']);
            return $record;
        }
        return array();
    }

    private function _aGetNoteFormatDataType3($note, $uid)
    {
        if (isset($note) && !empty($note)) {
            $record['title'] = sprintf("%s 创建了工单", \apps\workorder\MFacade_Api::getUserNameById($uid));
            $record['detail'] = $note;
            return $record;
        }
        return array();
    }

    private function _aGetNoteFormatDataType4($note, $uid)
    {
        if (isset($note) && !empty($note)) {
            $record['title'] = sprintf("%s 更改了工单状态", \apps\workorder\MFacade_Api::getUserNameById($uid));
            $record['detail'] = \apps\workorder\MFacade_Api::getUserNameById($record['uid']) . " 将工单状态从 "
                . "<font color=\"#41c6ad\">" . \apps\workorder\MFacade_Api::$_aStatusConf[$note['old']] . "</font>"
                . " 变更为 " . "<font color=\"#41c6ad\">"
                . \apps\workorder\MFacade_Api::$_aStatusConf[$note['update']] . "</font>";
            return $record;
        }
        return array();
    }

    public function sGetChanges($workorderId, $startDate, $endDate)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('workorder_id = ?', $workorderId);
        $records = $this->$recordDao->aGetList($option);
        if (!empty($records)) {
            foreach ($records as $recordItem) {
                if ($recordItem['ctime'] > $startDate && $recordItem['ctime'] < $endDate) {
                    $note = json_decode($recordItem['note'], true);
                    if (!empty($note) && 4 == $note['old'] && 2 == $note['update']) {
                        return '重开';
                    }
                }
            }
            foreach ($records as $recordItem) {
                if ($recordItem['ctime'] < $startDate) {
                    return '更新';
                }
            }
        }
        return '新建';
    }

}