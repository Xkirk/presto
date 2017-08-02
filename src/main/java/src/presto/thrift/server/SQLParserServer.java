package src.presto.thrift.server;

import org.apache.thrift.protocol.TBinaryProtocol;
import org.apache.thrift.server.TServer;
import org.apache.thrift.server.TSimpleServer;
import org.apache.thrift.transport.TServerSocket;
import org.apache.thrift.transport.TTransportException;
import src.presto.thrift.impl.SQLParserImpl;
import src.presto.thrift.service.SQLParser;

import java.io.IOException;
import java.net.ServerSocket;

public class SQLParserServer {
    public static void main(String[] args) throws IOException, TTransportException {
        final int SERVER_PORT = 8090;
        ServerSocket socket = new ServerSocket(SERVER_PORT);
        TServerSocket serverTransport = new TServerSocket(socket);
        SQLParser.Processor processor = new SQLParser.Processor(new SQLParserImpl());
        TServer.Args tArgs = new TServer.Args(serverTransport);
        tArgs.processor(processor);
        tArgs.protocolFactory(new TBinaryProtocol.Factory());
        TServer server = new TSimpleServer(tArgs);
        System.out.println("Running server...");
        server.serve();
    }
}