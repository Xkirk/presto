package src.presto.thrift.demo.service;

import org.apache.thrift.TException;

public class HelloImpl implements Hello.Iface {
    public HelloImpl() {
    }
    @Override
    public String helloString(String name) throws TException {
        System.out.println(name);
        return "Hi,"+name+"! Welcome To The Jungle!";
    }
}
