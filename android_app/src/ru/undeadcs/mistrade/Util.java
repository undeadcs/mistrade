package ru.undeadcs.mistrade;

import android.text.TextUtils;
import android.util.Log;

public class Util {
	public static String convertDateISOtoRU( String dateISO ) {
		String[ ] date = TextUtils.split( dateISO, "-" );
        return ( date.length == 3 ) ? TextUtils.join( ".", new String[ ] { date[ 2 ], date[ 1 ], date[ 0 ] } ) : "";
	} // String convertDateISOtoRU
	
	public static String convertDateRUtoISO( String dateRU ) {
		String[ ] date = TextUtils.split( dateRU, "\\." );
		return ( date.length == 3 ) ? TextUtils.join( "-", new String[ ] { date[ 2 ], date[ 1 ], date[ 0 ] } ) : "";
	} // String convertDateRUtoISO
	
	public static void LogObject( String tag, Object object ) {
		Log.d( tag, ( object == null ? "NULL" : object.getClass( ).getName( ) ) );
	}
}
