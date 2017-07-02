package src;

import java.io.FileInputStream;
import java.sql.*;
import java.util.ArrayList;
import java.util.Properties;

/**
 * Created by kirk on 2017/6/28.
 */
public class PrestoConnector {
    final static String HADOOP = "jdbc:presto://192.168.7.56:8055/hive/default";
//    private static String schema = "";
//    private static String sql = "";
//    PrestoConnector(String sql,String schema){
//        this.schema = schema;
//        this.sql = sql;
//    }
//   static ArrayList<Object> objList=new ArrayList<Object>();


    public static String getConn(String catalog,String schema) throws Exception{
        Properties prop = new Properties();
        prop.load(new FileInputStream("/home/xujing/jdbc.properties"));
        String jdbcURL = prop.getProperty("jdbcURL");
        return jdbcURL+"/"+catalog+"/"+schema;
    }

    public static void prestoClient(String sql,String catalog,String schema,String user) throws Exception {
        Class.forName("com.facebook.presto.jdbc.PrestoDriver");
        Connection connection = DriverManager.getConnection(getConn(catalog,schema), user, null);
        Statement stmt = connection.createStatement();
        try {
            ResultSet rs = stmt.executeQuery(sql);
            ResultSetMetaData meta = rs.getMetaData();
            int columnCount = meta.getColumnCount();
            for (int i = 1; i < columnCount + 1; i++) {
                System.out.print(meta.getColumnName(i)+"\t");
            }
            System.out.println();
            while (rs.next()) {
                for (int i = 1; i < columnCount+1; i++) {
                    System.out.print(rs.getString(i)+"\t");
                }
                System.out.println();
            }
            rs.close();
            connection.close();
        } catch (Exception e) {
            System.out.println(e.getMessage());
            throw e;
        }

    }
}