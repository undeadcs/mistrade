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
import android.widget.Spinner;

public class ProductList extends Activity implements ListView.OnItemClickListener, TextWatcher {
    private Spinner 			m_selCategory			= null,
                    			m_selSubcategory		= null;
    private ListView			m_objList				= null;
    private ProductListAdapter	m_objAdapter			= null;
    private CCategoryPick		m_objCategoryPick		= new CCategoryPick( );
    private CSubCategoryPick	m_objSubCategoryPick	= new CSubCategoryPick( );
    private SimpleCursorAdapter	m_scaSubcategory		= null;
    private EditText 			m_inpPattern			= null;

    @Override
    protected void onCreate( Bundle savedInstanceState ) {
        super.onCreate( savedInstanceState );
        setContentView( R.layout.activity_product_list );
        getActionBar( ).setDisplayHomeAsUpEnabled( true );
        
        m_inpPattern		= ( EditText ) findViewById( R.id.pattern );
        m_selCategory		= ( Spinner ) findViewById( R.id.category );
        m_selSubcategory	= ( Spinner ) findViewById( R.id.subcategory );
        m_objList			= ( ListView ) findViewById( R.id.list );
        
        initList( );
        initCategory( );
        initSubcategory( );
        
        m_inpPattern.addTextChangedListener( this );
        
        // из-за того, что адаптер может подгрузить данные позже, то вызов листенера происходит позже на выпадающих списках, чем на текстовом поле
        boolean bExecuteSearch = false, byPattern = false;
        
        if ( m_objSavedData.subcategoryPosition != -1 ) {
        	m_selCategory.setSelection( m_objSavedData.categoryPosition );
        	
        	Object item = m_selCategory.getItemAtPosition( m_objSavedData.categoryPosition );
            if ( item instanceof Cursor ) {
                Cursor cursor = ( Cursor ) item;
                Cursor cursorSubcategory = Database.m_objHelper.GetCategory( cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
                m_scaSubcategory.swapCursor( cursorSubcategory );
            }
        	
	        m_selSubcategory.setSelection( m_objSavedData.subcategoryPosition );
	        bExecuteSearch = true;
        } else if ( m_objSavedData.categoryPosition != -1 ) {
        	m_selCategory.setSelection( m_objSavedData.categoryPosition );
        	bExecuteSearch = true;
        }
        if ( ( m_objSavedData.pattern != null ) && ( m_objSavedData.pattern.length( ) > 0 ) ) {
        	m_inpPattern.setText( m_objSavedData.pattern );
        	m_inpPattern.setSelection( m_objSavedData.pattern.length( ) );
        	bExecuteSearch = false;
        	byPattern = true;
        }
        
        if ( bExecuteSearch ) {
        	ExecuteSearch( byPattern );
        }
        
        MISTradeApplication objApplication = ( MISTradeApplication ) getApplication( );
        SyncTask m_objSync = new SyncTask( objApplication, this );
		m_objSync.execute( );
		objApplication.ResetSync( );
    }
    
    private void initCategory( ) {
        String[ ] columns = new String[ ] { "category_name" };
        int[ ] ids = { android.R.id.text1 };
        SimpleCursorAdapter adapter = new SimpleCursorAdapter( this, R.layout.spinner_drop_down_item, Database.m_objHelper.GetCategory( ), columns, ids, 0 );
        m_selCategory.setAdapter( adapter );
        m_selCategory.setOnItemSelectedListener( m_objCategoryPick );
    }
    
    private void initSubcategory( ) {
        Object item = m_selCategory.getSelectedItem( );
        if ( item instanceof Cursor ) {
            Cursor cursor = ( Cursor ) item;
            int id = cursor.getInt( cursor.getColumnIndex( "_id" ) );
            String[ ] columns = new String[ ] { "category_name" };
            int[ ] ids = { android.R.id.text1 };
            m_scaSubcategory = new SimpleCursorAdapter( this, R.layout.spinner_drop_down_item, Database.m_objHelper.GetCategory( id ), columns, ids, 0 );
            m_selSubcategory.setAdapter( m_scaSubcategory );
            m_selSubcategory.setOnItemSelectedListener( m_objSubCategoryPick );
        }
    } // void initSubcategory
    
    private void initList( ) {
        m_objAdapter = new ProductListAdapter( this, Database.m_objHelper.GetProduct( ) );
        m_objList.setAdapter( m_objAdapter );
        m_objList.setOnItemClickListener( this );
    } // void initList

    @Override
    public boolean onCreateOptionsMenu( Menu menu ) {
        return false;
    }

    @Override
    public boolean onOptionsItemSelected( MenuItem item ) {
        switch ( item.getItemId( ) ) {
        case android.R.id.home:
            setResult( RESULT_CANCELED );
            finish( );
            return true;
        }
        return super.onOptionsItemSelected( item );
    }
    
    private static class CSavedData {
    	public int categoryPosition = -1;
    	public int subcategoryPosition = -1;
    	public String pattern = null;
    };
    
    private static CSavedData m_objSavedData = new CSavedData( );
    
    @Override
    protected void onSaveInstanceState( Bundle outState ) {
    	super.onSaveInstanceState( outState );
    	
    	m_objSavedData.categoryPosition = m_selCategory.getSelectedItemPosition( );
        m_objSavedData.subcategoryPosition = m_selSubcategory.getSelectedItemPosition( );
        m_objSavedData.pattern = m_inpPattern.getText( ).toString( );
    }
    
    @Override
    protected void onDestroy( ) {
    	super.onDestroy( );
    	
    	m_objSavedData.categoryPosition = m_selCategory.getSelectedItemPosition( );
        m_objSavedData.subcategoryPosition = m_selSubcategory.getSelectedItemPosition( );
        m_objSavedData.pattern = m_inpPattern.getText( ).toString( );
    }
    
    protected void ExecuteSearch( boolean byPattern ) {
    	Cursor cursor = null;
    	
    	if ( byPattern ) {
    		String pattern = m_inpPattern.getText( ).toString( );
    		cursor = Database.m_objHelper.GetProduct( pattern );
    	} else {
    		int categoryId = 0;
    		
    		Object item = m_selCategory.getSelectedItem( );
            if ( item instanceof Cursor ) {
                Cursor tmp = ( Cursor ) item;
                if ( tmp.getCount( ) > 0 ) {
                	categoryId = tmp.getInt( tmp.getColumnIndex( "_id" ) );
                }
            }
            
            item = m_selSubcategory.getSelectedItem( );
            if ( item instanceof Cursor ) {
                Cursor tmp = ( Cursor ) item;
                if ( tmp.getCount( ) > 0 ) {
    	            categoryId = tmp.getInt( tmp.getColumnIndex( "_id" ) );
                }
            }
            
            cursor = Database.m_objHelper.GetProduct( categoryId );
    	}
        
        m_objAdapter.swapCursor( cursor );
    } // void executeSearch
    
    @Override
    public void onItemClick( AdapterView< ? > parent, View view, int position, long id ) {
        Object item = m_objList.getItemAtPosition( position );
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
        
        finish( );
    } // void onItemClick
    
    private class CCategoryPick implements AdapterView.OnItemSelectedListener {
        @Override
        public void onItemSelected( AdapterView< ? > parent, View view, int position, long id ) {
        	if ( m_objSavedData.categoryPosition != position ) {
        		m_objSavedData.categoryPosition = position;
        		m_objSavedData.subcategoryPosition = -1;
        		
	            Object item = m_selCategory.getItemAtPosition( position );
	            if ( item instanceof Cursor ) {
	                Cursor cursor = ( Cursor ) item;
	                Cursor cursorSubcategory = Database.m_objHelper.GetCategory( cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
	                m_scaSubcategory.swapCursor( cursorSubcategory );
	                
	                if ( ( cursorSubcategory != null ) && ( cursorSubcategory.getCount( ) == 0 ) ) {
	                	ProductList.this.ExecuteSearch( false );
	                }
	            }
        	}
        }
        
        @Override
        public void onNothingSelected( AdapterView< ? > parent ) { }
    }
    
    private class CSubCategoryPick implements AdapterView.OnItemSelectedListener {
    	@Override
        public void onItemSelected( AdapterView< ? > parent, View view, int position, long id ) {
    		if ( m_objSavedData.subcategoryPosition != position ) {
    			m_objSavedData.subcategoryPosition = position;
    		
    			ProductList.this.ExecuteSearch( false );
    		}
        }
        
        @Override
        public void onNothingSelected( AdapterView< ? > parent ) { }
    }
    
    @Override
    public void afterTextChanged( Editable s ) {
    	ExecuteSearch( true );
    } // void afterTextChanged
    
    @Override
    public void beforeTextChanged( CharSequence s, int start, int count, int after ) { }
    
    @Override
    public void onTextChanged( CharSequence s, int start, int before, int count ) { }
    
} // class ProductList
