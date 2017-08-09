<?php
namespace apps\office\center;

class MLeave_departmenttreeApi extends \Ko_Mode_Tree {
    protected $_aConf = array(
        'treeApi' => 'departmenttreeDao',
        'mc' => 'modulemc',
    );

    public function sGetAllDepartmentOptions($selid = 0, $excludeids = array()) {
        $tree = $this->aGetChild(0, 0);
        $dids = $this->aTree2Arr($tree);
        $infos = $this->departmentApi->aGetListByKeys($dids);
        \Ko_Tool_Str::VConvert2GB18030($infos);
        return $this->_sGetDepartmentOptions($selid, $excludeids, $tree, $infos, 0);
    }

    private function _sGetDepartmentOptions($selid, $excludeids, $tree, $infos, $level = 0) {
        $options = '';
        foreach ($tree as $id => $subtree) {
            if (in_array($id, $excludeids)) {
                continue;
            }
            $options .= '<option value="' . htmlspecialchars($id) . '"' . ($selid == $id ? ' selected' : '') . '>';
            for ($i = 0; $i < $level; ++$i) {
                $options .= '|--';
            }
            $options .= ' ' . htmlspecialchars($infos[$id]['name']) . '</option>';
            $options .= $this->_sGetDepartmentOptions($selid, $excludeids, $subtree, $infos, $level + 1);
        }
        return $options;
    }

    public function aGetAllDepatmentTree($open = false) {
        $tree = $this->aGetChild(0, 0);
        $dids = $this->aTree2Arr($tree);
        $infos = $this->departmentApi->aGetListByKeys($dids);
        return $this->_aGetDepartmentTree($tree, $infos, $open);
    }

    private function _aGetDepartmentTree($tree, $infos, $open) {
        $tree_data = array();
        foreach ($tree as $id => $subtree) {
            $item = $infos[$id];
            $item['isParent'] = true;
            $open && $item['open'] = true;
            $item['children'] = $this->_aGetDepartmentTree($subtree, $infos, $open);
            $tree_data[] = $item;
        }
        return $tree_data;
    }

    public function aGetChildByDid($iDid = 0) {
        $tree = $this->aGetChild($iDid, 0);
        $alldids = $this->aTree2Arr($tree);
        $alldids[] = $iDid;
        return $alldids;
    }

    /**
     * 临时方法
     */
    public function iInsertAll($aParam) {
        return $this->departmenttreeDao->iInsert($aParam);
    }
}
