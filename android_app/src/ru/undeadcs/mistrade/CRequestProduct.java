package ru.undeadcs.mistrade;

public class CRequestProduct {
	public long id;
	public int request_id, product_id;
	public String code;
	public float amount;
	
	public CRequestProduct( ) {
		id = 0;
		request_id = product_id = 0;
		code = "";
		amount = 0;
	}
}
