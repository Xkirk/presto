package src;


import src.com.mafengwo.PrestoException;
import src.com.mafengwo.PrestoResult;

import java.util.ArrayList;
import java.util.HashMap;

/**
 * Created by kirk on 2017/6/29.
 */
public class PrestoClient {
    /**
     *
     * @param sql
     * @param catalog
     * @param schema
     * @param userName
     * @return
     * @throws PrestoException
     */
    public PrestoResult query(String sql , String catalog,String schema, String userName) throws Exception {
        PrestoResult prestoResult = new PrestoResult();
        try {
            prestoResult.setRsList( PrestoConnector.connectPresto(sql,catalog,schema,userName) );
            prestoResult.setStatus("success");
        } catch (Exception e) {
            prestoResult.setStatus("failed");
            prestoResult.setException(e.getMessage());
        }
        return prestoResult;
    }
    public PrestoResult query(String sql, String userName) throws Exception{
        PrestoResult prestoResult = new PrestoResult();
        try {
            prestoResult.setRsList( PrestoConnector.connectPresto(sql,"hive","default",userName) );
            prestoResult.setStatus("success");
        } catch (Exception e) {
            prestoResult.setStatus("failed");
            prestoResult.setException(e.getMessage());
        }
        return prestoResult;
    }
}
