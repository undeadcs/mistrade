package ru.undeadcs.mistrade;

import android.content.Context;
import android.database.Cursor;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.CursorAdapter;
import android.widget.TextView;

public class ProductListAdapter extends CursorAdapter {
	private class CViewData {
		TextView txtName, txtPrice, txtSaldo, txtUnit;
	};
	
	public ProductListAdapter( Context context, Cursor cursor ) {
		super( context, cursor );
	}

	@Override
	public void bindView( View view, Context context, Cursor cursor ) {
		Object tag = view.getTag( );
		if ( tag instanceof CViewData ) {
			CViewData data = ( CViewData ) tag;
			
			data.txtName.setText( cursor.getString( cursor.getColumnIndex( "product_name" ) ) );
			data.txtPrice.setText( cursor.getString( cursor.getColumnIndex( "product_price" ) ) );
			data.txtSaldo.setText( cursor.getString( cursor.getColumnIndex( "product_saldo" ) ) );
			
			int iUnit = cursor.getInt( cursor.getColumnIndex( "product_unit" ) );
			if ( iUnit == ProductUnit.KG.ordinal( ) ) {
				data.txtUnit.setText( "кг" );
			} else if ( iUnit == ProductUnit.PIECE.ordinal( ) ) {
				data.txtUnit.setText( "шт" );
			} else {
				data.txtUnit.setText( "" );
			}
		}
	} // void bindView

	@Override
	public View newView( Context context, Cursor cursor, ViewGroup parent ) {
		LayoutInflater inflater = LayoutInflater.from( context );
		View view = inflater.inflate( R.layout.list_product, parent, false );
		
		CViewData data = new CViewData( );
		data.txtName	= ( TextView ) view.findViewById( R.id.name );
		data.txtPrice	= ( TextView ) view.findViewById( R.id.price );
		data.txtSaldo	= ( TextView ) view.findViewById( R.id.saldo );
		data.txtUnit	= ( TextView ) view.findViewById( R.id.unit );
		view.setTag( data );
		
		bindView( view, context, cursor );
		
		return view;
	} // View newView

} // class ProductListAdapter
