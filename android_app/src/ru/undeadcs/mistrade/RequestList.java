package ru.undeadcs.mistrade;

import java.util.Calendar;

import android.os.Bundle;
import android.app.Activity;
import android.app.DatePickerDialog;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.ListView;
import android.widget.RadioButton;
import android.widget.RadioGroup;

public class RequestList extends Activity implements ListView.OnItemClickListener {
	private Button				m_btnDateFrom	= null,
								m_btnDateTo		= null;
	private RadioGroup			m_rgType		= null;
	private RadioButton			m_rbMain		= null;
	private ListView			m_objList		= null;
	private RequestListAdapter	m_objAdapter	= null;
	private String				m_szDateFrom	= "",
								m_szDateTo		= "";
	
	@Override
	protected void onCreate( Bundle savedInstanceState ) {
		super.onCreate( savedInstanceState );
		setContentView( R.layout.activity_request_list );
		getActionBar( ).setDisplayHomeAsUpEnabled( true );
		
		m_btnDateFrom	= ( Button ) findViewById( R.id.btnDateFrom );
		m_btnDateTo		= ( Button ) findViewById( R.id.btnDateTo );
		m_rgType		= ( RadioGroup ) findViewById( R.id.db_type );
		m_objList		= ( ListView ) findViewById( R.id.list );
		m_rbMain		= ( RadioButton ) findViewById( R.id.db_main );
		
		m_rbMain.setChecked( true );
		m_rgType.setOnCheckedChangeListener( new RadioGroup.OnCheckedChangeListener( ) {
			@Override
			public void onCheckedChanged( RadioGroup group, int checkedId ) {
				ExecuteSearch( );
			}
		});
		
		int year = 0, month = 0, day = 0;
		Calendar calendar = Calendar.getInstance( );
    	
		calendar.add( Calendar.DAY_OF_MONTH, -1 );
		year	= calendar.get( Calendar.YEAR );
    	month	= calendar.get( Calendar.MONTH );
    	day		= calendar.get( Calendar.DAY_OF_MONTH );
    	
    	m_szDateFrom = String.format( "%02d.%02d.%04d", day, month + 1, year );
        m_btnDateFrom.setText( m_szDateFrom );
        
        calendar.add( Calendar.DAY_OF_MONTH, 2 );
		year	= calendar.get( Calendar.YEAR );
    	month	= calendar.get( Calendar.MONTH );
    	day		= calendar.get( Calendar.DAY_OF_MONTH );
        
        m_szDateTo = String.format( "%02d.%02d.%04d", day, month + 1, year );
        m_btnDateTo.setText( m_szDateTo );
        
        m_objAdapter = new RequestListAdapter( this, Database.m_objHelper.GetRequest( m_szDateFrom, m_szDateTo, true ) );
        m_objList.setAdapter( m_objAdapter );
        m_objList.setOnItemClickListener( this );
	} // void onCreate

	@Override
	public boolean onCreateOptionsMenu( Menu menu ) {
		return false;
	} // boolean onCreateOptionsMenu
	
	@Override
	public boolean onOptionsItemSelected( MenuItem item ) {
		switch ( item.getItemId( ) ) {
        case android.R.id.home:
        	finish( );
            return true;
        }
        return super.onOptionsItemSelected( item );
	} // boolean onOptionsItemSelected
	
	public void onDatePick( View view ) {
		int year = 0, month = 0, day = 0;
		String szCurrentDate = ( view.getId( ) == R.id.btnDateFrom ) ? m_szDateFrom : m_szDateTo;
		
		if ( ( szCurrentDate.length( ) > 0 ) && szCurrentDate.matches( "\\d{2}\\.\\d{2}\\.\\d{4}" ) ) {
        	String[ ] arrPart = szCurrentDate.split( "\\." );
        	year	= Integer.parseInt( arrPart[ 2 ] );
        	month	= Integer.parseInt( arrPart[ 1 ] ) - 1;
        	day		= Integer.parseInt( arrPart[ 0 ] );
        }
		
		if ( ( year <= 0 ) || ( month <= 0 ) || ( day <= 0 ) ) {
        	Calendar calendar = Calendar.getInstance( );
        	year	= calendar.get( Calendar.YEAR );
        	month	= calendar.get( Calendar.MONTH );
        	day		= calendar.get( Calendar.DAY_OF_MONTH );
        }
		
		DatePickerDialog dialog = null;
		
		if ( view.getId( ) == R.id.btnDateFrom ) {
			dialog = new DatePickerDialog( this, new DatePickerDialog.OnDateSetListener( ) {
				@Override
				public void onDateSet( DatePicker view, int year, int monthOfYear, int dayOfMonth ) {
					m_szDateFrom = String.format( "%02d.%02d.%04d", dayOfMonth, monthOfYear + 1, year );
			        m_btnDateFrom.setText( m_szDateFrom );
			        ExecuteSearch( );
				}
			}, year, month, day );
		} else {
			dialog = new DatePickerDialog( this, new DatePickerDialog.OnDateSetListener( ) {
				@Override
				public void onDateSet( DatePicker view, int year, int monthOfYear, int dayOfMonth ) {
					m_szDateTo = String.format( "%02d.%02d.%04d", dayOfMonth, monthOfYear + 1, year );
			        m_btnDateTo.setText( m_szDateTo );
			        ExecuteSearch( );
				}
			}, year, month, day );
		}

        dialog.show( );
	} // void onDatePick
	
	private void ExecuteSearch( ) {
		m_objAdapter.swapCursor( Database.m_objHelper.GetRequest( m_szDateFrom, m_szDateTo, ( m_rgType.getCheckedRadioButtonId( ) == R.id.db_main ) ? true : false ) );
	} // void ExecuteSearch
	
	@Override
    public void onItemClick( AdapterView< ? > parent, View view, int position, long id ) {
        /*Object item = m_objList.getItemAtPosition( position );
        if ( item instanceof Cursor ) {
            Cursor cursor = ( Cursor ) item;
            Intent data = new Intent( );
            data.putExtra( "productId", cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
            data.putExtra( "productName", cursor.getString( cursor.getColumnIndex( "product_name" ) ));
            data.putExtra( "productUnit", cursor.getInt( cursor.getColumnIndex( "product_unit" ) ) );
            data.putExtra( "productPrice", cursor.getFloat( cursor.getColumnIndex( "product_price" ) ) );
            setResult( RESULT_OK, data );
        } else {
            setResult( RESULT_CANCELED );
        }
        
        finish( );*/
    } // void onItemClick
	
} // class RequestList
