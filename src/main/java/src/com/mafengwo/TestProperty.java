package src.com.mafengwo;

import java.io.FileInputStream;
import java.util.Properties;

/**
 * Created by kirk on 2017/6/29.
 */
public class TestProperty {
    public static void main(String[] args) throws Exception {
        Properties pps = new Properties();
        pps.load(new FileInputStream("src/main/java/src/jdbc.properties"));
        System.out.println(pps.getProperty("HADOOP"));
    }
}
