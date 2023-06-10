package ru.undeadcs.mistrade;

import android.content.Context;
import android.database.Cursor;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.CursorAdapter;
import android.widget.TextView;

public class RequestListAdapter extends CursorAdapter {
	private class CViewData {
		public TextView txtClient, txtDate, txtTradePoint, txtExport;
	};
	
	public RequestListAdapter( Context context, Cursor cursor ) {
		super( context, cursor );
	} // RequestListAdapter

	@Override
	public void bindView( View view, Context context, Cursor cursor ) {
		Object tag = view.getTag( );
		if ( tag instanceof CViewData ) {
			CViewData data = ( CViewData ) tag;
			int iColIndex = 0;
			
			String szClientName = "";
			iColIndex = cursor.getColumnIndex( "client_name" );
			if ( iColIndex != -1 ) {
				szClientName = cursor.getString( iColIndex );
			} else {
				iColIndex = cursor.getColumnIndex( "request_client_id" );
				if ( iColIndex != -1 ) {
					Cursor curExtra = Database.m_objHelper.GetClient( cursor.getInt( iColIndex ) );
					if ( curExtra.getCount( ) > 0 ) {
						curExtra.moveToFirst( );
						szClientName = curExtra.getString( curExtra.getColumnIndex( "client_name" ) );
					}
					
					curExtra.close( );
				}
			}
			
			data.txtClient.setText( szClientName );
			
			data.txtDate.setText( cursor.getString( cursor.getColumnIndex( "request_receive_date" ) ) );
			data.txtTradePoint.setText( cursor.getString( cursor.getColumnIndex( "request_trade_point" ) ) );
	
			iColIndex = cursor.getColumnIndex( "export_state" );
			if ( iColIndex != -1 ) {
				data.txtExport.setVisibility( View.VISIBLE );
				
				if ( cursor.isNull( iColIndex ) ) {
					data.txtExport.setText( "ВЫГРУЖЕНА ОНЛАЙН" );
					data.txtExport.setTextColor( Color.parseColor( "#555555" ));
				} else {
					int iExportState = cursor.getInt( iColIndex );
					switch( iExportState ) {
					case Database.EXPORT_STATE_NEW:
						data.txtExport.setText( "НЕ ВЫГРУЖЕНА" );
						data.txtExport.setTextColor( Color.parseColor( "#555555" ));
						break;
						
					case Database.EXPORT_STATE_EXPORTED:
						data.txtExport.setText( "ВЫГРУЖЕНА" );
						data.txtExport.setTextColor( Color.parseColor( "#008800" ));
						break;
						
					case Database.EXPORT_STATE_FAILED:
						data.txtExport.setText( "ОШИБКА ПРИ ВЫГРУЗКЕ" );
						data.txtExport.setTextColor( Color.parseColor( "#880000" ));
						break;
					}
				}
			} else {
				data.txtExport.setVisibility( View.GONE );
				data.txtExport.setText( "" );
			}
		}
	} // void bindView

	@Override
	public View newView( Context context, Cursor cursor, ViewGroup parent ) {
		LayoutInflater inflater = LayoutInflater.from( context );
		View view = inflater.inflate( R.layout.request_list_row, parent, false );
		
		CViewData data = new CViewData( );
		data.txtClient		= ( TextView ) view.findViewById( R.id.client );
		data.txtDate		= ( TextView ) view.findViewById( R.id.date );
		data.txtTradePoint	= ( TextView ) view.findViewById( R.id.trade_point );
		data.txtExport		= ( TextView ) view.findViewById( R.id.export );
		view.setTag( data );
		
		bindView( view, context, cursor );
		
		return view;
	} // View newView

} // class RequestListAdapter
