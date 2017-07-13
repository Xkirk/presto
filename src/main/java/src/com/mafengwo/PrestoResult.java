package src.com.mafengwo;

import java.util.ArrayList;
import java.util.HashMap;

/**
 * Created by kirk on 2017/7/3.
 */
public class PrestoResult {
    protected String status;
    protected String exception = "";
    protected ArrayList<HashMap> rsList;

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getException() {
        return exception;
    }

    public void setException(String exception) {
        this.exception = exception;
    }

    public ArrayList<HashMap> getRsList() {
        return rsList;
    }

    public void setRsList(ArrayList<HashMap> rsList) {
        this.rsList = rsList;
    }
}
