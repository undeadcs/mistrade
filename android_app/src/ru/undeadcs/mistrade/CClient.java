package ru.undeadcs.mistrade;

public class CClient {
	int id, manager_id, price;
	float limit;
	String manager_code, code, name, phone, addr;
	
	public CClient( ) {
	    id = manager_id = price = 0;
	    limit = 0.0f;
	    manager_code = code = name = phone = addr = "";
	}
}
