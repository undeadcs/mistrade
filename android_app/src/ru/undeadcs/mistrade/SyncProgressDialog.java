package ru.undeadcs.mistrade;

import android.app.Activity;
import android.app.AlertDialog;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;

public class SyncProgressDialog extends AlertDialog {
	private ProgressBar	m_pbDeterminate		= null;
	private TextView	m_txtMessage		= null,
						m_txtMax			= null;
	private Activity	m_objActivity		= null;
	
	public SyncProgressDialog( Activity activity ) {
		super( activity );
		m_objActivity = activity;
		setView( );
	} // SyncProgressDialog
	
	private void setView( ) {
		LayoutInflater inflater = m_objActivity.getLayoutInflater( );
		View view = inflater.inflate( R.layout.sync_progress_dialog, null );
		
		m_pbDeterminate		= ( ProgressBar ) view.findViewById( R.id.progressDeterminate );
		m_txtMessage		= ( TextView ) view.findViewById( R.id.progressMessage );
		m_txtMax			= ( TextView ) view.findViewById( R.id.progressMax );
		
		setView( view );
		hideDeterminate( );
	} // void setView
	
	public void setMessage( CharSequence szText ) {
		m_txtMessage.setText( szText );
	} // void setMessage
	
	public void setMax( int max ) {
		m_pbDeterminate.setMax( max );
	} // void setMax
	
	public void setProgress( int progress ) {
		m_pbDeterminate.setProgress( progress );
		m_txtMax.setText( String.format( "%d / %d", progress, m_pbDeterminate.getMax( ) ) );
	} // void setProgress
	
	public void hideDeterminate( ) {
		setDeterminateVisibility( View.INVISIBLE );
	} // void hideDeterminate
	
	public void showDeterminate( ) {
		setDeterminateVisibility( View.VISIBLE );
	} // void showDeterminate
	
	public void setDeterminateVisibility( int iVisibility ) {
		m_pbDeterminate.setVisibility( iVisibility );
		m_txtMax.setVisibility( iVisibility );
	} // void setDeterminateVisibility
	
} // class SyncProgressDialog
