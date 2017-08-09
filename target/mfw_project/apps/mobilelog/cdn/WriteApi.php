<?php

namespace apps\mobilelog\cdn;

class MWriteApi
{
    public static function VWriteLogs($aLogList)
    {
        $keyTimes = array();
        foreach($aLogList as $log) {
            if (false == strpos($log['host'], 'mafengwo') || $log['time'] <= time() - 86400 * 3) {
                continue;
            }

            \apps\kafka\MFacade_logApi::serverEventLog(
                \apps\kafka\MFacade_logApi::SAPP_APP, 'image_load_time', $log);

            $key = $log['host']."\t".self::_IGetStatMddId($log['mdd_id']);
            if (!isset($keyTimes[$key])) {
                $keyTimes[$key] = array(
                    'size' => 0,
                    'time' => 0,
                    'total_times' => 0,
                    'fail_times' => 0
                );
            }

            $keyTimes[$key]['total_times']++;
            $failed = $log['code'] && ($log['code'] >= 400 || $log['code'] < 100);
            if($log['spend'] <= 0 || $log['spend'] > 30000 || $failed) {
                \apps\MFacade_Log_Api::webdlog('cdn_fail',
                    $log['host'].':ip:'.long2ip($log['ip']).
                    ':code:'.$log['code'].':time:'.date('Ymd H:i:s', $log['time']).
                    ':mddid:'.$log['mdd_id'].
                    ':network:'.$log['net'].
                    ':p_mddid:'.self::_IGetStatMddId($log['mdd_id'])
                );
                $keyTimes[$key]['fail_times']++;
            } else {
                $keyTimes[$key]['size'] += $log['size'];
                $keyTimes[$key]['time'] += $log['spend'];
            }
        }
        self::_VSave($keyTimes);
    }

    private static function _VSave($keyTimes)
    {
        foreach($keyTimes as $k => $v) {
            $insert = $change = array(
                'size_sum' => $v['size'],
                'time_sum' => $v['time'],
                'total_times' => $v['total_times'],
                'fail_times' => $v['fail_times']
            );
            list($insert['host'], $insert['mdd_id']) = explode("\t", $k);
            $insert['date'] = intval(date('Ymd'));
            MDao::CdnLogCountDao()->iInsert($insert, array(), $change);
        }
    }

    private static function _IGetStatMddId($mddId)
    {
        if (!$mddId) {
            return 0;
        }

        static $_cached = array();
        if (!isset($_cached[$mddId])) {
            $parentIds = \apps\mdd\MFacade_mddApi::aGetConfirmParents($mddId, true, true);
            array_pop($parentIds); // 去掉洲
            $countryMddId = array_pop($parentIds);
            if ($countryMddId == 21536) {
                $_cached[$mddId] = array_pop($parentIds);
            } else {
                $_cached[$mddId] = $countryMddId;
            }
        }
        return $_cached[$mddId];
    }
}
