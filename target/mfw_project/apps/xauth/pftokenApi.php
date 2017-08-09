<?php

namespace apps\xauth;

class MpftokenApi extends \Ko_Busi_Api
{
    public function get($token)
    {
        return $this->pftokenDao->aGet($token);
    }

    public function getByUid($uid, $cid)
    {
        $key = compact('uid', 'cid');
        return $this->pfuidcidDao->aGet($key);
    }

    public function isPasswordChanged($token)
    {
        $info = $this->pfchgpassDao->aGet($token);
        return !empty($info);
    }

    public function add($token, $secret, $uid, $cid)
    {
        $uid = intval($uid);
        $cid = intval($cid);
        if (!$uid || !$cid)
        {
            return false;
        }
        $data = compact('token', 'secret', 'uid', 'cid');
        $data['ctime'] = date('Y-m-d H:i:s');
        try
        {
            $this->pftokenDao->aInsert($data);
            $this->pfuidcidDao->vDeleteCache($data);
        }
        catch (\Exception $e)
        {
            return false;
        }
        return true;
    }

    public function onPasswordChanged($uid)
    {
        $uid = intval($uid);
        if (!$uid)
        {
            return;
        }
        $option = new \Ko_Tool_SQL;
        $option->oWhere('uid = ?', $uid);
        $list = $this->pftokenDao->aGetList($option);
        if ($list) {
            foreach ($list as $v) {
                $data = array(
                    'token' => $v['token'],
                    'ctime' => date('Y-m-d H:i:s'),
                );
                try {
                    $this->pfchgpassDao->aInsert($data);
                }
                catch (\Exception $e) {
                }
                $this->pftokenDao->iDelete($v);
                $this->pfuidcidDao->vDeleteCache($v);
            }
        }
    }
}
