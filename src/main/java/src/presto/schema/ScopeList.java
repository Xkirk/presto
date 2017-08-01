package src.presto.schema;

import java.util.ArrayList;
import java.util.List;

public class ScopeList {
    private List<Long> strDate = new ArrayList<>();
    private List<Long> endDate = new ArrayList<>();

    public List<Long> getStrDate() {
        return strDate;
    }

    public void setStrDate(List<Long> strDate) {
        this.strDate = strDate;
    }

    public List<Long> getEndDate() {
        return endDate;
    }

    public void setEndDate(List<Long> endDate) {
        this.endDate = endDate;
    }
}
