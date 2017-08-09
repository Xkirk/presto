<?php
/**
 * 文章扩展信息类
 *
 * @author  Zhoulei
 * @version  1.0
 * @date  2017-03-01
 */

namespace apps\office;


class MArticle_articleExtApi extends \Ko_Busi_Api {

    public function aGetInfoByArticleId($articleId) {

        $aRet = $this->articleExtDao->aGet($articleId);
        if (count($aRet))
            return $aRet;
        return array();
    }

    public function iInsert($aRecord) {

	$now = date('Y-m-d H:i:s', time());
        $aRecord['mtime'] = $now;
        $aRecord['ctime'] = $now;
        return $this->articleExtDao->iInsert($aRecord);
    }

    public function iUpdate($iId, $aUpdate) {

        if ($iId <= 0 || empty($aUpdate)) 
		return 0;
        
        $aUpdate['mtime'] = date('Y-m-d H:i:s', time());
        return $this->articleExtDao->iUpdate($iId, $aUpdate);
    }

}
