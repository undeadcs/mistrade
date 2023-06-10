package ru.undeadcs.mistrade;

import android.os.Bundle;
import android.app.Activity;
import android.content.Intent;
import android.database.Cursor;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.EditText;
import android.widget.ListView;
import android.widget.SimpleCursorAdapter;

public class ClientList extends Activity implements ListView.OnItemClickListener, TextWatcher {
    private ListView			m_objList		= null;
    private SimpleCursorAdapter m_objAdapter	= null;
    private EditText			m_inpPattern	= null;

    @Override
    protected void onCreate( Bundle savedInstanceState ) {
        super.onCreate( savedInstanceState );
        setContentView(R.layout.activity_client_list );
        getActionBar().setDisplayHomeAsUpEnabled( true );
        
        m_inpPattern = ( EditText ) findViewById( R.id.pattern );
        m_inpPattern.addTextChangedListener( this );
        
        String[ ] columns = new String[ ] {
            "client_name",
            "client_phone"
        };
        
        int[ ] ids = {
            R.id.name,
            R.id.phone
        };
        
        m_objAdapter = new SimpleCursorAdapter( this, R.layout.list_client, Database.m_objHelper.GetClient( "" ), columns, ids, 0 );
        m_objList = ( ListView ) findViewById( R.id.list );
        m_objList.setAdapter( m_objAdapter );
        m_objList.setOnItemClickListener( this );
    }

    @Override
    public boolean onCreateOptionsMenu( Menu menu ) {
        return false;
    }

    @Override
    public boolean onOptionsItemSelected( MenuItem item ) {
        switch ( item.getItemId( ) ) {
        case android.R.id.home:
            finish( );
            return true;
        }
        return super.onOptionsItemSelected( item );
    }

    private void ExecuteSearch( ) {
    	m_objAdapter.swapCursor( Database.m_objHelper.GetClient( m_inpPattern.getText( ).toString( ) ) );
    } // void ExecuteSearch
    
    @Override
    public void onItemClick( AdapterView< ? > parent, View view, int position, long id ) {
        Object item = m_objList.getItemAtPosition( position );
        if ( item instanceof Cursor ) {
            Cursor cursor = ( Cursor ) item;
            Intent data = new Intent( );
            data.putExtra( "clientId",		cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
            data.putExtra( "clientName",	cursor.getString( cursor.getColumnIndex( "client_name" ) ) );
            data.putExtra( "clientPrice",	cursor.getInt( cursor.getColumnIndex( "client_price" ) ) );
            
            setResult( RESULT_OK, data );
        } else {
            setResult( RESULT_CANCELED );
        }
        
        finish( );
    } // void onItemClick
    
    @Override
    public void afterTextChanged( Editable s ) {
    	ExecuteSearch( );
    } // void afterTextChanged
    
    @Override
    public void beforeTextChanged( CharSequence s, int start, int count, int after ) { }
    
    @Override
    public void onTextChanged( CharSequence s, int start, int before, int count ) { }
    
} // class ClientList
