<?php
/**
 * article -- 核心Api
 *
 * @author  Zhoulei
 * @version  1.0
 * @date  2017-04-04
 */
namespace apps\office;

class MArticle_api extends \Ko_Busi_Api {

   
    public function aGetArticleExt($articleId)
    {
        $articleExtApi = new MArticle_articleExtApi();
		return $articleExtApi->aGetInfoByArticleId($articleId);
    }

    public function iUpdateArticleExt($articleId, $articleExtInfo) {

        $articleExtApi = new MArticle_articleExtApi();
        return $articleExtApi->iUpdate($articleId, $articleExtInfo);
    }

    public function iInsertArticleExt($articleExtInfo) {
	 
        $articleExtApi = new MArticle_articleExtApi();
        return $articleExtApi->iInsert($articleExtInfo);
    }
 }
