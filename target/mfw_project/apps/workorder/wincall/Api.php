<?php
namespace apps\workorder\wincall;

class MApi {
    protected $mBaseHost = "http://m.icsoc.net";
    public $mUsername = "6015031601";   //主平台和三亚：6015031601，当地游：6016061401
    protected $mNonce = "";
    protected $mCreated = "";
    protected $mSecret = "28395dfd93640d760c36cb858b4277de";
    protected $mPasswordDigest = "";
    protected $mWsse = "";

    public function __construct($vcc_code="6015031601")
    {
        $this->mUsername = $vcc_code;
        $this->mNonce = mt_rand();
        $this->mCreated = time();
        $this->mPasswordDigest = base64_encode(
            sha1(base64_decode($this->mNonce) . $this->mCreated . $this->mSecret, true)
        );
        $this->mWsse = 'UsernameToken Username="' . $this->mUsername . '",PasswordDigest="' . $this->mPasswordDigest
            . '", Nonce="' . $this->mNonce
            . '", Created="' . $this->mCreated . '"';
    }

    public function setAccount($vcc_code, $secret)
    {
        $this->mUsername = $vcc_code;
        $this->mNonce = mt_rand();
        $this->mCreated = time();
        $this->mSecret = $secret;
        $this->mPasswordDigest = base64_encode(
            sha1(base64_decode($this->mNonce) . $this->mCreated . $this->mSecret, true)
        );
        $this->mWsse = 'UsernameToken Username="' . $this->mUsername . '",PasswordDigest="' . $this->mPasswordDigest
            . '", Nonce="' . $this->mNonce
            . '", Created="' . $this->mCreated . '"';
    }

    //客服坐席列表
    public function aGetAgentList()
    {
        $url = $this->mBaseHost . "/v2/wintelapi/agent/list";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, '');
    }

    //通话记录
    public function aGetCallHistory($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/detail/call";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //通话录音
    public function getAudioRecord($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/record/playrecord";
        $method = "GET";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //客服坐席日报表
    public function aGetAgentDayService($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/data/agent/day";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //技能组日报表
    public function aGetQueueService($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/data/queue/day";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //呼叫中心整体日报表
    public function aGetCallcenterService($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/data/system/day";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //呼入明细
    public function aGetCallinDetail($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/detail/callin";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }
    //呼出明细
    public function aGetCalloutDetail($params)
    {
        $url = $this->mBaseHost . "/v2/wintelapi/detail/callout";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    //技能组日报表
    public function aGetQueueList($params=array())
    {
        $url = $this->mBaseHost . "/v2/wintelapi/queue/list";
        $method = "POST";
        return $this->aGetRequestExec($url, $method, $params);
    }

    public function iGetQueIdByQueNum($iQueNum)
    {
        $cacheKey = 'callcenter_wincall_queuelist';
        $aQueueList = \Ko_Tool_Cache::Get($cacheKey);
        if (empty($aQueueList)) {
            $aQueueList = json_decode($this->aGetQueueList(), true);
            \Ko_Tool_Cache::Set($cacheKey, $aQueueList, 60*60*24*2);   //2 day cache.
        }
        foreach ($aQueueList['data'] as $aQueue) {
            if ($aQueue['que_num'] == $iQueNum) {
                return $aQueue['id'];
            }
        }

        return 0;
    }

    /********* base function *********/
    private function aGetRequestExec($url, $method, $params) {
        $header = array("X-WSSE" => $this->mWsse);
        $res = $this->requestExec($url, $params, $method, $header);
        return $res['body'];
    }

    private function requestExec($url, $params, $method, $my_header)
    {
        $curl_session = curl_init();

        curl_setopt($curl_session, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl_session, CURLOPT_HEADER, true);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        $url_parts = $this->parse_raw_url($url);

        $header = array();
        $header[] = 'Host: ' . $url_parts['host'];
        if ($my_header && is_array($my_header)) {
            foreach ($my_header AS $key => $value) {
                $header[] = $key . ': ' . $value;
            }
        }

        if ($method === 'GET') {
            curl_setopt($curl_session, CURLOPT_HTTPGET, true);
            $url .= $params ? '?' . $params : '';
        } else {
            curl_setopt($curl_session, CURLOPT_POST, true);
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $header[] = 'Content-Length: ' . strlen($params);
            curl_setopt($curl_session, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($curl_session, CURLOPT_URL, $url);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $header);
        $http_response = curl_exec($curl_session);

        if (curl_errno($curl_session) != 0) {
            return false;
        }
        $separator = '/\r\n\r\n|\n\n|\r\r/';
        list($http_header, $http_body) = preg_split($separator, $http_response, 2);

        $http_response = array('header' => $http_header,
            'body' => $http_body);
        curl_close($curl_session);
        return $http_response;
    }

    private function parse_raw_url($raw_url)
    {
        $retval = array();
        $raw_url = (string)$raw_url;
        if (strpos($raw_url, '://') === false) {
            $raw_url = 'http://' . $raw_url;
        }
        $retval = parse_url($raw_url);
        if (!isset($retval['path'])) {
            $retval['path'] = '/';
        }
        if (!isset($retval['port'])) {
            $retval['port'] = '80';
        }
        return $retval;
    }
}
