package src.presto.schema;

import java.util.HashMap;

/**
 * Created by kirk on 2017/7/20.
 */
public class DateScopeMap {
    HashMap<String, ScopeList> dtScope = new HashMap<>();
    HashMap<String, String> aliasMap = new HashMap<>();

    public HashMap<String, ScopeList> getDtScope() {
        return dtScope;
    }

    public void setDtScope(HashMap<String, ScopeList> dtScope) {
        this.dtScope = dtScope;
    }

    public HashMap<String, String> getAliasMap() {
        return aliasMap;
    }

    public void setAliasMap(HashMap<String, String> aliasMap) {
        this.aliasMap = aliasMap;
    }

    public String getTableNameByAlias(String base) {
        return aliasMap.get(base);
    }

}
