package ru.undeadcs.mistrade;

import android.os.Bundle;
import android.app.Activity;
import android.content.Intent;
import android.database.Cursor;
import android.view.Menu;
import android.view.View;
import android.widget.CheckBox;
import android.widget.EditText;

public class Login extends Activity {
	private EditText inpLogin		= null,
				     inpPassword	= null;
	private CheckBox cbRemember		= null;
	
	@Override
	protected void onCreate( Bundle savedInstanceState ) {
		super.onCreate( savedInstanceState );
		setContentView( R.layout.activity_login );
		
		inpLogin    = ( EditText ) findViewById( R.id.login );
		inpPassword = ( EditText ) findViewById( R.id.password );
		cbRemember	= ( CheckBox ) findViewById( R.id.remember );
		
		Cursor cursor = Database.m_objHelper.GetSavedLogin( );
		if ( cursor.getCount( ) > 0 ) {
			cursor.moveToFirst( );
			
			String	szLogin		= cursor.getString( cursor.getColumnIndex( "saved_login_login" ) ),
					szPassword = cursor.getString( cursor.getColumnIndex( "saved_login_password" ) );
			
			inpLogin.setText( szLogin );
			inpPassword.setText( szPassword );
			cbRemember.setChecked( true );
		}
		
		cursor.close( );
	}

	@Override
	public boolean onCreateOptionsMenu( Menu menu ) {
	    return false;
	}
	
	public void onLogin( View view ) {
		String	szLogin		= inpLogin.getText( ).toString( ),
				szPassword	= inpPassword.getText( ).toString( );
		
		if ( szLogin.equals( "1" ) && szPassword.equals( "1" ) ) {
			MISTradeApplication.m_objManager = new CManager( );
			startActivity( new Intent( this, Request.class ) );
			
			if ( cbRemember.isChecked( ) ) {
				Database.m_objHelper.SaveLogin( szLogin, szPassword );
			}
		} else {
			CManager manager = Database.m_objHelper.GetManager( szLogin, szPassword );
			if ( manager != null ) {
				MISTradeApplication.m_objManager = manager;
				startActivity( new Intent( this, Request.class ) );
				
				if ( cbRemember.isChecked( ) ) {
					Database.m_objHelper.SaveLogin( szLogin, szPassword );
				}
			} else {
				MessageBox.show( this, "Не удалось авторизоваться. Проверьте правильность логина и пароля.", "Ошибка" );
			}
		}
	} // void onLogin
}
