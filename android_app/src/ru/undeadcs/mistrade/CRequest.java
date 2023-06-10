package ru.undeadcs.mistrade;

import java.util.ArrayList;

public class CRequest {
	public long id;
	public int client_id, type, time1_from, time1_to, time2_from, time2_to,
		flag_money_must_be, flag_money_simple, flag_certificate, flag_sticker, manager_id;
	public String client_code, code, creation_date, receive_date, trade_point;
	public CClient client;
	public ArrayList< CRequestProduct > products;
	
	public CRequest( ) {
	    id = 0;
	    client_id = type = time1_from = time1_to = time2_from = time2_to =
        flag_money_must_be = flag_money_simple = flag_certificate = flag_sticker = manager_id = 0;
	    client_code = code = creation_date = receive_date = trade_point = "";
	    client = new CClient( );
	    products = new ArrayList< CRequestProduct >( );
	}
}
