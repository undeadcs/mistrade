package ru.undeadcs.mistrade;

import android.app.Activity;
import android.app.Application;
import android.content.Context;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.os.Bundle;
import android.os.Handler;

public class MISTradeApplication extends Application {
	public static CManager m_objManager = null;
	private Handler m_objHandler = new Handler( );
	private Application.ActivityLifecycleCallbacks m_objCallbacks = new CallbackHandler( );
	private Activity m_objCurrentActivity = null;
	
	final static int SYNC_START_DELAY = 5000;
	final static int SYNC_DELAY = //300000; // 5 минут
									900000; // 15 минут
	
	@Override
	public void onCreate( ) {
		super.onCreate( );
		
		Database.init( this );
		m_objHandler.postDelayed( m_runSync, SYNC_START_DELAY );
		registerActivityLifecycleCallbacks( m_objCallbacks );
	} // void onCreate
	
	public boolean isNetworkAvailable( ) {
        ConnectivityManager manager = ( ConnectivityManager ) getSystemService( Context.CONNECTIVITY_SERVICE );
        NetworkInfo network = manager.getActiveNetworkInfo( );
        
        return ( network != null ) && network.isConnected( );
    } // boolean isNetworkAvailable
	
	@Override
	public void onTerminate( ) {
		super.onTerminate( );
		
		m_objCurrentActivity = null;
		unregisterActivityLifecycleCallbacks( m_objCallbacks );
		m_objHandler.removeCallbacks( m_runSync );
		Database.terminate( );
	} // void onTerminate
	
	public Activity GetCurrentActivity( ) {
		return m_objCurrentActivity;
	} // Activity GetCurrentActivity
	
	public void ResetSync( ) {
		m_objHandler.removeCallbacks( m_runSync );
		m_objHandler.postDelayed( m_runSync, SYNC_DELAY );
	} // void ResetSync
	
	private Runnable m_runSync = new Runnable( ) {
		public void run( ) {
			if ( ( m_objCurrentActivity != null ) && !( m_objCurrentActivity instanceof Login ) ) {
				( new SyncTask( MISTradeApplication.this ) ).execute( );
			}
			m_objHandler.postDelayed( m_runSync, SYNC_DELAY );
		}
	};
	
	private class CallbackHandler implements Application.ActivityLifecycleCallbacks {
		@Override
		public void onActivityCreated( Activity activity, Bundle savedInstanceState ) {
			Util.LogObject( "onActivityCreated", activity );
		}
		
		@Override
		public void onActivityDestroyed( Activity activity ) {
			Util.LogObject( "onActivityDestroyed", activity );
		}
		
		@Override
		public void onActivityPaused( Activity activity ) {
			Util.LogObject( "onActivityPaused", activity );
			m_objCurrentActivity = null;
		}
		
		@Override
		public void onActivityResumed( Activity activity ) {
			Util.LogObject( "onActivityResumed", activity );
			m_objCurrentActivity = activity;
		}
		
		@Override
		public void onActivitySaveInstanceState( Activity activity, Bundle outState ) {
			Util.LogObject( "onActivitySaveInstanceState", activity );
		}
		
		@Override
		public void onActivityStarted( Activity activity ) {
			Util.LogObject( "onActivityStarted", activity );
		}
		
		@Override
		public void onActivityStopped( Activity activity ) {
			Util.LogObject( "onActivityStopped", activity );
		}
	}
	
} // class MISTradeApplication
