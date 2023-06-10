package ru.undeadcs.mistrade;

import android.os.Bundle;
import android.app.Activity;
import android.database.Cursor;
import android.text.TextUtils;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;

public class Sync extends Activity {
	private Button		m_btnExecuteSync	= null;
	private ProgressBar	m_pbExecuteSync		= null;
	private SyncTask	m_objSync			= null;
	
	@Override
	protected void onCreate( Bundle savedInstanceState ) {
		super.onCreate( savedInstanceState );
		setContentView( R.layout.activity_sync );
		getActionBar( ).setDisplayHomeAsUpEnabled( true );
		
		m_btnExecuteSync	= ( Button ) findViewById( R.id.btnExecuteSync );
		m_pbExecuteSync		= ( ProgressBar ) findViewById( R.id.pbExecuteSync );
		
		load( );
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
    }
	
	public void finish( ) {
		if ( m_objSync != null ) {
			m_objSync.cancel( true );
			m_objSync = null;
		}
		super.finish( );
	} // void finish
	
	public void onUpdateExecute( View view ) {
		m_btnExecuteSync.setEnabled( false );
		m_pbExecuteSync.setVisibility( View.VISIBLE );
		
		MISTradeApplication objApplication = ( MISTradeApplication ) getApplication( );
		m_objSync = new SyncTask( objApplication, this );
		m_objSync.execute( );
		objApplication.ResetSync( );
	} // void onUpdateExecute
	
	public void AfterSync( Cursor objExport, boolean bMainDatabase, boolean bProductDatabase, Object result ) {
		m_btnExecuteSync.setEnabled( true );
		m_pbExecuteSync.setVisibility( View.GONE );
		load( );
	} // void AfterSync
	
	private void load( ) {
		Cursor cursor = Database.m_objHelper.GetUpdate( Database.UPDATE_TYPE_EXEC, true );
		if ( cursor.getCount( ) > 0 ) {
			cursor.moveToFirst( );
			
			String tmp = cursor.getString( cursor.getColumnIndex( "update_date" ) );
			if ( tmp != null ) {
				String[ ] parts = TextUtils.split( tmp, " " );
				( ( TextView ) findViewById( R.id.syncLastExecution ) ).setText( Util.convertDateISOtoRU( parts[ 0 ] ) + " " + parts[ 1 ] );
			}
		}
	} // void load
	
} // class Sync
