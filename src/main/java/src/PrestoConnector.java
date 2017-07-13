package src;


import java.io.FileInputStream;
import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Properties;

/**
 * Created by kirk on 2017/6/28.
 */
public class PrestoConnector {
    public static String getConn(String catalog,String schema) throws Exception{

        Properties prop = new Properties();
        prop.load(new FileInputStream("/mfw_rundata/presto/conf/conf.properties"));
        String jdbcURL = prop.getProperty("presto_on_hadoop");
        return jdbcURL+"/"+catalog+"/"+schema;
    }


    /**
     *
     * @param sql
     * @param catalog
     * @param schema
     * @param user
     * @return
     * @throws Exception
     */
    public static ArrayList<HashMap> connectPresto(String sql,String catalog,String schema,String user) throws Exception {
        Class.forName("com.facebook.presto.jdbc.PrestoDriver");
        Connection connection = DriverManager.getConnection(getConn(catalog,schema), user, null);
        Statement stmt = connection.createStatement();
        try {
            ResultSet rs = stmt.executeQuery(sql);
            ResultSetMetaData meta = rs.getMetaData();
            int columnCount = meta.getColumnCount();
            ArrayList<HashMap> rsList = new ArrayList<HashMap>();
            while (rs.next()) {
                HashMap<String, String> rsMap = new HashMap<String, String>();
                for (int i = 1; i < columnCount+1; i++) {
                    rsMap.put(meta.getColumnName(i),rs.getString(i));
                }
                rsList.add(rsMap);
            }
            rs.close();
            connection.close();
            return rsList;
        } catch (Exception e) {
            throw e;
        }

    }
}