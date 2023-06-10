package ru.undeadcs.mistrade;

import android.app.AlertDialog;
import android.content.Context;

public class MessageBox {
	public static void show( Context context, String message ) {
		AlertDialog dialog = new AlertDialog.Builder( context )
	    	.setMessage( message )
	    	.setCancelable( true )
	    	.setPositiveButton( R.string.ok, null )
	    	.create( );
	    dialog.setCanceledOnTouchOutside( true );
	    dialog.show( );
	}
	
	public static void show( Context context, String message, String title ) {
		AlertDialog dialog = new AlertDialog.Builder( context )
			.setTitle( title )
	    	.setMessage( message )
	    	.setCancelable( true )
	    	.setPositiveButton( R.string.ok, null )
	    	.create( );
	    dialog.setCanceledOnTouchOutside( true );
	    dialog.show( );
	}
}
