package ru.undeadcs.mistrade;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.util.ArrayList;
import java.util.Calendar;

import android.app.Activity;
import android.database.Cursor;
import android.os.AsyncTask;

public class SyncTask extends AsyncTask< Void, Object, Object > {
	static final int STEP_EXPORT	= 0;	// экспорт заявок, сохраненных локально
	static final int EXPORT_BEGIN	= 0;	// начало
	static final int EXPORT_COUNT	= 1;	// количество на выгрузку
	static final int EXPORT_REQUEST	= 2;	// выполнение запроса
	
	static final int STEP_FULL_DATABASE				= 1;	// синхронизация всей базы
	static final int FULL_DATABASE_BEGIN			= 0;	// начало
	static final int FULL_DATABASE_REQUEST			= 1;	// выполнение запроса
	static final int FULL_DATABASE_CONTENT_LENGTH	= 2;	// размер файла
	static final int FULL_DATABASE_PART_LOAD		= 3;	// загружена/скопирована порция файла
	static final int FULL_DATABASE_COPY				= 4;	// копирование файла
	
	static final int STEP_PRODUCT_DATABASE				= 2;	// синхронизация базы номенклатуры
	static final int PRODUCT_DATABASE_BEGIN				= 0;	// начало
	static final int PRODUCT_DATABASE_REQUEST			= 1;	// выполнение запроса
	static final int PRODUCT_DATABASE_CONTENT_LENGTH	= 2;	// размер файла
	static final int PRODUCT_DATABASE_PART_LOAD			= 3;	// загружена/скопирована порция файла
	static final int PRODUCT_DATABASE_COPY				= 4;	// копирование файла
	
	private class SyncResult {
		public boolean	success	= true;
		public String	message	= "";
	};
	
	private class SyncNeed {
		public boolean	fullDatabase	= false,
						productDatabase	= false;
		public Cursor	export = null;
	};
	
	private SyncNeed			m_objSyncNeed		= null;
	private SyncProgressDialog	m_dlgProgress		= null;
	private MISTradeApplication	m_objApplication	= null;
	private Activity			m_objActivity		= null;
	
	public SyncTask( MISTradeApplication objApplication ) {
		m_objApplication = objApplication;
	} // SyncTask
	
	public SyncTask( MISTradeApplication objApplication, Activity objActivity ) {
		m_objApplication	= objApplication;
		m_objActivity		= objActivity;
	} // SyncTask
	
	@Override
	protected Object doInBackground( Void... params ) {
		Calendar calendar = Calendar.getInstance( );
        String szUpdate = String.format(
        	"%04d-%02d-%02d %02d:%02d:%02d",
        	calendar.get( Calendar.YEAR ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.DAY_OF_MONTH ),
        	calendar.get( Calendar.HOUR_OF_DAY ), calendar.get( Calendar.MINUTE ), calendar.get( Calendar.SECOND )
        );
        Database.m_objHelper.SaveUpdate( Database.UPDATE_TYPE_EXEC, szUpdate );
		/*
		 * полное обновление производится посуточно
		 * значит надо из локальной базы данных выбирать запись с текущей датой (без времени)
		 * если такой записи об обновлении нет, то производить полную закачку файла БД, после замены добавлять запись с текущей датой
		 * файл БД номенклатуры является отдельным и обновляется чаще, для него достаточно периодичной проверки версии
		 */
		if ( m_objApplication.isNetworkAvailable( ) == true ) {
			try {
				// синхронизация начинается тогда, когда есть что эскпортировать или отличаются версии
				m_objSyncNeed = CheckSync( );
				
				SyncResult result = null;
				
				if ( m_objSyncNeed.export != null ) {
					result = Export( m_objSyncNeed.export );
					if ( !result.success ) {
						return result;
					}
				}
				
				if ( m_objSyncNeed.fullDatabase == true ) {
					result = FullDatabase( );
					if ( !result.success ) {
						return result;
					}
				}
				
				if ( m_objSyncNeed.productDatabase == true ) {
					result = ProductDatabase( );
				}
				
				return result;
			}
			catch( InterruptedException e ) {
				e.printStackTrace( );
			}
		}
		
		return null;
	} // Object doInBackground
	
	@Override
	protected void onPostExecute( Object result ) {
		super.onPostExecute( result );
		
		if ( m_dlgProgress != null ) {
			m_dlgProgress.dismiss( );
		}
		
		Activity objCurrentActivity = m_objApplication.GetCurrentActivity( );
		if ( ( objCurrentActivity != null )  && ( result instanceof SyncResult ) ) {
			SyncResult r = ( SyncResult ) result;
			
			String message = "";
			
			if ( r.success ) {
				if ( r.message.isEmpty( ) ) {
					message = "Синхронизация завершена успешно.";
				} else {
					message = r.message;
				}
			} else {
				message = "Ошибка при синхронизации.\n" + r.message;
			}
			
			MessageBox.show( objCurrentActivity, message );
		}
		
		if ( m_objActivity instanceof Sync ) {
			( ( Sync ) m_objActivity ).AfterSync( m_objSyncNeed.export, m_objSyncNeed.fullDatabase, m_objSyncNeed.productDatabase, result );
		}
	} // void onPostExecute
	
	@Override
	protected void onProgressUpdate( Object... values ) {
		super.onProgressUpdate( values );
		
		Activity objCurrentActivity = m_objApplication.GetCurrentActivity( );
		if ( objCurrentActivity == null ) {
			return;
		}
		
		if ( m_dlgProgress == null ) {
			m_dlgProgress = new SyncProgressDialog( objCurrentActivity );
			m_dlgProgress.setCancelable( false );
			m_dlgProgress.setTitle( "Синхронизация базы данных" );
			m_dlgProgress.show( );
		}
		
		int	iStep		= ( Integer ) values[ 0 ],
			iSubStep	= ( Integer ) values[ 1 ];
		
		switch( iStep ) {
		
		case STEP_EXPORT:			OnProgressExport( iSubStep, values );			break;
		case STEP_FULL_DATABASE:	OnProgressFullDatabase( iSubStep, values );		break;
		case STEP_PRODUCT_DATABASE:	OnProgressProductDatabase( iSubStep, values );	break;
		
		}
	} // void onProgressUpdate
	
	private void OnProgressExport( int iStep, Object... values ) {
		switch( iStep ) {
		
		case EXPORT_BEGIN: {
			m_dlgProgress.hideDeterminate( );
			m_dlgProgress.setMessage( "Экспорт заявок" );
		} break;
		
		case EXPORT_COUNT: {
			m_dlgProgress.showDeterminate( );
			m_dlgProgress.setMax( ( Integer ) values[ 2 ] );
			m_dlgProgress.setProgress( 0 );
		} break;
		
		case EXPORT_REQUEST: {
			m_dlgProgress.setProgress( ( Integer ) values[ 2 ] );
		} break;
		
		}
	} // void OnProgressExport
	
	private void OnProgressFullDatabase( int iStep, Object... values ) {
		switch( iStep ) {
		
		case FULL_DATABASE_BEGIN: {
			m_dlgProgress.hideDeterminate( );
			m_dlgProgress.setMessage( "Полная база" );
		} break;
		
		case FULL_DATABASE_REQUEST: {
			m_dlgProgress.setMessage( "Полная база - Выполнение запроса" );
		} break;
		
		case FULL_DATABASE_CONTENT_LENGTH: {
			m_dlgProgress.setMessage( "Полная база - Загрузка файла" );
			m_dlgProgress.showDeterminate( );
			m_dlgProgress.setMax( ( Integer ) values[ 2 ] );
			m_dlgProgress.setProgress( 0 );
		} break;
		
		case FULL_DATABASE_COPY: {
			m_dlgProgress.setMessage( "Полная база - Копирование файла" );
			m_dlgProgress.showDeterminate( );
			m_dlgProgress.setMax( ( Integer ) values[ 2 ] );
			m_dlgProgress.setProgress( 0 );
		} break;
		
		case FULL_DATABASE_PART_LOAD: {
			m_dlgProgress.setProgress( ( Integer ) values[ 2 ] );
		} break;
		
		}
	} // void OnProgressFullDatabase
	
	private void OnProgressProductDatabase( int iStep, Object... values ) {
		switch( iStep ) {
		
		case PRODUCT_DATABASE_BEGIN: {
			m_dlgProgress.hideDeterminate( );
			m_dlgProgress.setMessage( "База номенклатуры" );
		} break;
		
		case PRODUCT_DATABASE_REQUEST: {
			m_dlgProgress.setMessage( "База номенклатуры - Выполнение запроса" );
		} break;
		
		case PRODUCT_DATABASE_CONTENT_LENGTH: {
			m_dlgProgress.setMessage( "База номенклатуры - Загрузка файла" );
			m_dlgProgress.showDeterminate( );
			m_dlgProgress.setMax( ( Integer ) values[ 2 ] );
			m_dlgProgress.setProgress( 0 );
		} break;
		
		case PRODUCT_DATABASE_COPY: {
			m_dlgProgress.setMessage( "База номенклатуры - Копирование файла" );
			m_dlgProgress.showDeterminate( );
			m_dlgProgress.setMax( ( Integer ) values[ 2 ] );
			m_dlgProgress.setProgress( 0 );
		} break;
		
		case PRODUCT_DATABASE_PART_LOAD: {
			m_dlgProgress.setProgress( ( Integer ) values[ 2 ] );
		} break;
		
		}
	} // void OnProgressProductDatabase
	
	private SyncNeed CheckSync( ) {
		SyncNeed need = new SyncNeed( );
		
		// проверка наличия экспорта
		Cursor cursor = Database.m_objHelper.GetExport( Database.EXPORT_TYPE_REQUEST );
		if ( ( cursor != null ) && ( cursor.getCount( ) > 0 ) ) {
			need.export = cursor;
		}
		
		// сравнение версий
		URL url = null;
    	HttpURLConnection connection = null;
    	
        try {
			// @todo вынести в общий сервис взаимодействия со шлюзом
            url = new URL( "http://localhost/mobile_system/data/get-version/" );
            connection = ( HttpURLConnection ) url.openConnection( );
            connection.setDoInput( true );
            connection.setInstanceFollowRedirects( false );
            connection.setUseCaches( false );
            connection.setDefaultUseCaches( false );
            connection.setConnectTimeout( 30000 );
            connection.setReadTimeout( 30000 );
            
            int statusCode;
            try {
                statusCode = connection.getResponseCode( );
            } catch( IOException ex ) {
                statusCode = connection.getResponseCode( );
            }
            
            if ( statusCode == 200 ) {
            	BufferedReader reader = new BufferedReader( new InputStreamReader( connection.getInputStream( ) ) );
            	ArrayList< String > arrLine = new ArrayList< String >( );
                String line = "";
                while( ( arrLine.size( ) < 2 ) && ( line = reader.readLine( ) ) != null ) {
                	arrLine.add( line );
                }
                
                if ( arrLine.size( ) == 2 ) {
                	String	szServerVersion		= arrLine.get( 0 ),
                			szCurrentVersion	= Database.m_objHelper.GetVersion( );
                	
                	// главная база
                	if ( !szCurrentVersion.equals( szServerVersion ) ) {
                		Calendar calendar = Calendar.getInstance( );
                		String szUpdate = String.format( "%04d-%02d-%02d", calendar.get( Calendar.YEAR ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.DAY_OF_MONTH ) );
                    	Cursor cursorUpdate = Database.m_objHelper.GetUpdate( Database.UPDATE_TYPE_MAIN, szUpdate, true );
                    	
                    	if ( ( cursorUpdate != null ) && ( cursorUpdate.getCount( ) == 0 ) ) {
                    		need.fullDatabase = true;
                    	}
                	}
                	
                	// база номенклатуры
                	szServerVersion		= arrLine.get( 1 );
                	szCurrentVersion	= Database.m_objHelper.GetVersionProduct( );
                	
                	need.productDatabase = !szCurrentVersion.equals( szServerVersion );
                } else {
                	// какой-то корявый ответ от сервера
                	need.export = null;
                }
            }
        }
        catch( SocketTimeoutException e ) {
        	e.printStackTrace( );
        	// при сетевых ошибках синхронизацию лучше даже не запускать, только зря будет диалоговое окно мешать пользователю
        	// при таймаутах будет отсутствовать сеть, что будет приводить к показу окна при каждом запуске и раздражать пользователя
        	need.export = null;
        }
        catch ( IOException e ) {
            e.printStackTrace( );
            
            need.export = null;
        }
        finally {
        	if ( connection != null ) {
        		connection.disconnect( );
        	}
        }
		
		return need;
	} // SyncNeed CheckSync
	
	private SyncResult Export( Cursor cursor ) throws InterruptedException {
		SyncResult ret = new SyncResult( );
		
		publishProgress( STEP_EXPORT, EXPORT_BEGIN );
		Thread.sleep( 200 );
		
		int iCount = cursor.getCount( );
		
		if ( iCount > 0 ) {
			publishProgress( STEP_EXPORT, EXPORT_COUNT, iCount );
			Thread.sleep( 200 );
			
			cursor.moveToFirst( );
			
			int iExportNumber = 1, i = 0;
			long[ ] arrId = new long[ iCount ];
			int[ ] arrState = new int[ iCount ];
			String[ ] arrError = new String[ iCount ];
			String error = null;
			
			do {
				publishProgress( STEP_EXPORT, EXPORT_REQUEST, iExportNumber++ );
				Thread.sleep( 200 );
				
				arrId[ i ] = cursor.getLong( cursor.getColumnIndex( "_id" ) );
				
				error = ExportRequest( cursor.getString( cursor.getColumnIndex( "export_data" ) ) );
				
				if ( error == null ) {
					arrState[ i ] = Database.EXPORT_STATE_EXPORTED;
					arrError[ i ] = "";
				} else {
					arrState[ i ] = Database.EXPORT_STATE_FAILED;
					arrError[ i ] = error;
				}
				
				++i;
			} while ( cursor.moveToNext( ) );
			
			cursor.close( );
			
			Database.m_objHelper.SaveExport( arrId, arrState, arrError );
			Calendar calendar = Calendar.getInstance( );
            String szUpdate = String.format(
            	"%04d-%02d-%02d %02d:%02d:%02d",
            	calendar.get( Calendar.YEAR ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.DAY_OF_MONTH ),
            	calendar.get( Calendar.HOUR_OF_DAY ), calendar.get( Calendar.MINUTE ), calendar.get( Calendar.SECOND )
            );
            Database.m_objHelper.SaveUpdate( Database.UPDATE_TYPE_EXPORT, szUpdate );
		}
		
		return ret;
	} // SyncResult RunExport
	
	private String ExportRequest( String szData ) {
		URL url = null;
    	HttpURLConnection connection = null;
    	
        try {
            url = new URL( "http://localhost/mobile_system/data/save_request/" );
            connection = ( HttpURLConnection ) url.openConnection( );
            connection.setDoOutput( true );
            connection.setDoInput( true );
            connection.setRequestMethod( "POST" );
            connection.setInstanceFollowRedirects( false );
            connection.setUseCaches( false );
            connection.setDefaultUseCaches( false );
            connection.setRequestProperty( "Content-Type", "application/x-www-form-urlencoded" );
            connection.setFixedLengthStreamingMode( szData.getBytes( ).length );
            connection.setConnectTimeout( 30000 );
            connection.setReadTimeout( 30000 );
            
            OutputStreamWriter stream = new OutputStreamWriter( connection.getOutputStream( ), "UTF-8" );
            stream.write( szData );
            stream.flush( );
            stream.close( );
            
            int statusCode;
            try {
                statusCode = connection.getResponseCode( );
            } catch( IOException ex ) {
                statusCode = connection.getResponseCode( );
            }
            
            BufferedReader reader = new BufferedReader( new InputStreamReader( connection.getInputStream( ) ) );
            while( reader.readLine( ) != null ) {
            }
            
            if ( statusCode != 200 ) {
            	return String.format( "statusCode=%d", statusCode );
            }
            
            return null;
        }
        catch ( IOException e ) {
            e.printStackTrace( );
        }
        finally {
        	if ( connection != null ) {
        		connection.disconnect( );
        	}
        }
        
		return "exception";
	} // boolean ExportRequest
	
	private SyncResult FullDatabase( ) throws InterruptedException {
		SyncResult ret = new SyncResult( );
		
		publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_BEGIN );
		Thread.sleep( 200 );
		
		URL url = null;
    	HttpURLConnection connection = null;
    	
        try {
            url = new URL( "http://localhost/mobile_system/data/get-full-database/" );
            connection = ( HttpURLConnection ) url.openConnection( );
            connection.setDoInput( true );
            connection.setInstanceFollowRedirects( false );
            connection.setUseCaches( false );
            connection.setDefaultUseCaches( false );
            connection.setConnectTimeout( 30000 );
            connection.setReadTimeout( 30000 );
            
            publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_REQUEST );
            Thread.sleep( 200 );
            
            int statusCode;
            try {
                statusCode = connection.getResponseCode( );
            } catch( IOException ex ) {
                statusCode = connection.getResponseCode( );
            }
            
            if ( statusCode == 200 ) {
            	File file = new File( m_objApplication.getFilesDir( ) + "/full.db" );
            	if ( file.exists( ) ) {
            		file.delete( );
            	}
            	
            	file.createNewFile( );
            	
            	int iContentLength = connection.getContentLength( );
            	
            	publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_CONTENT_LENGTH, iContentLength );
            	Thread.sleep( 200 );
            	
            	InputStream sin = new BufferedInputStream( url.openStream( ) );
            	OutputStream sout = new FileOutputStream( file );
            	
            	byte data[ ] = new byte[ 1024 ];
            	int total = 0,
            		count = 0;
            	
            	while( ( count = sin.read( data ) ) != -1 ) {
            		total += count;
            		sout.write( data, 0, count );
            		publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_PART_LOAD, total );
            	}
            	
            	sout.flush( );
            	sout.close( );
            	sin.close( );
            	
            	if ( iContentLength == total ) {
            		String szFilePath = m_objApplication.getFilesDir( ) + "/full.db";
            		
            		Database.m_objHelper.close( );
					
					File fileOld = new File( Database.Helper.DB_PATH + "/" + Database.Helper.DB_NAME );
					if ( fileOld.exists( ) ) {
						fileOld.delete( );
					}
					
					fileOld.createNewFile( );
					
					File fileImport = new File( szFilePath );
					long size = fileImport.length( );
					publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_COPY, ( int ) size );
            		Thread.sleep( 200 );
					
					InputStream src = new FileInputStream( fileImport );
		            OutputStream dst = new FileOutputStream( fileOld );
		            
		            byte buf[ ] = new byte[ 1024 ];
		            int len = 0;
		            total = 0;
		            while( ( len = src.read( buf ) ) > 0 ) {
		            	total += len;
		            	publishProgress( STEP_FULL_DATABASE, FULL_DATABASE_PART_LOAD, total );
		                dst.write( buf, 0, len );
		            }
		            
		            dst.flush( );
		            dst.close( );
		            src.close( );
		            
		            fileImport.delete( );
		            
		            Database.m_objHelper.OpenDatabase( );
		            
		            Calendar calendar = Calendar.getInstance( );
		            String szUpdate = String.format(
		            	"%04d-%02d-%02d %02d:%02d:%02d",
		            	calendar.get( Calendar.YEAR ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.DAY_OF_MONTH ),
		            	calendar.get( Calendar.HOUR_OF_DAY ), calendar.get( Calendar.MINUTE ), calendar.get( Calendar.SECOND )
		            );
		            Database.m_objHelper.SaveUpdate( Database.UPDATE_TYPE_MAIN, szUpdate );
            	} else {
            		ret.success = false;
            		ret.message = "Не удалось скачать файл (полной базы) целиком";
            	}
            } else {
            	ret.success = false;
            	ret.message = String.format( "%d: ", statusCode ) + connection.getResponseMessage( );
            }
        }
        catch( SocketTimeoutException e ) {
        	e.printStackTrace( );
        	
        	ret.success = false;
        	ret.message = e.getMessage( );
        }
        catch( IOException e ) {
            e.printStackTrace( );
            
            ret.success = false;
        	ret.message = e.getMessage( );
        }
        finally {
        	if ( connection != null ) {
        		connection.disconnect( );
        	}
        }
        
        return ret;
	} // SyncResult FullDatabase
	
	private SyncResult ProductDatabase( ) throws InterruptedException {
		SyncResult ret = new SyncResult( );
		
		publishProgress( STEP_PRODUCT_DATABASE, PRODUCT_DATABASE_BEGIN );
		Thread.sleep( 200 );
		
		URL url = null;
    	HttpURLConnection connection = null;
    	
        try {
            url = new URL( "http://localhost/mobile_system/data/get-product-database/" );
            connection = ( HttpURLConnection ) url.openConnection( );
            connection.setDoInput( true );
            connection.setInstanceFollowRedirects( false );
            connection.setUseCaches( false );
            connection.setDefaultUseCaches( false );
            connection.setConnectTimeout( 30000 );
            connection.setReadTimeout( 30000 );
            
            int statusCode;
            try {
                statusCode = connection.getResponseCode( );
            }
            catch( IOException ex ) {
                statusCode = connection.getResponseCode( );
            }
            
            if ( statusCode == 200 ) {
            	File file = new File( m_objApplication.getFilesDir( ) + "/product.db" );
            	if ( file.exists( ) ) {
            		file.delete( );
            	}
            	
            	file.createNewFile( );
            	
            	int iContentLength = connection.getContentLength( );
            	
            	publishProgress( STEP_PRODUCT_DATABASE, PRODUCT_DATABASE_CONTENT_LENGTH, iContentLength );
            	Thread.sleep( 200 );
            	
            	InputStream sin = new BufferedInputStream( url.openStream( ) );
            	OutputStream sout = new FileOutputStream( file );
            	
            	byte data[ ] = new byte[ 1024 ];
            	int total = 0,
            		count = 0;
            	
            	while( ( count = sin.read( data ) ) != -1 ) {
            		total += count;
            		publishProgress( STEP_PRODUCT_DATABASE, PRODUCT_DATABASE_PART_LOAD, total );
            		sout.write( data, 0, count );
            	}
            	
            	sout.flush( );
            	sout.close( );
            	sin.close( );
            	
            	if ( iContentLength == total ) {
            		String szFilePath = m_objApplication.getFilesDir( ) + "/product.db";
            		
            		Database.m_objHelper.closeProduct( );
					
					File fileOld = new File( Database.Helper.DB_PATH + "/" + Database.Helper.DB_PRODUCT_NAME );
					if ( fileOld.exists( ) ) {
						fileOld.delete( );
					}
					
					fileOld.createNewFile( );
					
					File fileImport = new File( szFilePath );
					long size = fileImport.length( );
					publishProgress( STEP_PRODUCT_DATABASE, PRODUCT_DATABASE_COPY, ( int ) size );
            		Thread.sleep( 200 );
					
					InputStream src = new FileInputStream( fileImport );
		            OutputStream dst = new FileOutputStream( fileOld );
		            
		            byte buf[ ] = new byte[ 1024 ];
		            int len = 0;
		            total = 0;
		            while( ( len = src.read( buf ) ) > 0 ) {
		            	total += len;
		            	publishProgress( STEP_PRODUCT_DATABASE, PRODUCT_DATABASE_PART_LOAD, total );
		                dst.write( buf, 0, len );
		            }
		            
		            dst.flush( );
		            dst.close( );
		            src.close( );
		            
		            fileImport.delete( );
		            
		            Calendar calendar = Calendar.getInstance( );
		            String szUpdate = String.format(
		            	"%04d-%02d-%02d %02d:%02d:%02d",
		            	calendar.get( Calendar.YEAR ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.DAY_OF_MONTH ),
		            	calendar.get( Calendar.HOUR_OF_DAY ), calendar.get( Calendar.MINUTE ), calendar.get( Calendar.SECOND )
		            );
		            Database.m_objHelper.SaveUpdate( Database.UPDATE_TYPE_PRODUCT, szUpdate );
		            
		            Database.m_objHelper.OpenDatabaseProduct( );
            	}
            } else {
            	ret.success = false;
            	ret.message = String.format( "%d: ", statusCode ) + connection.getResponseMessage( );
            }
        }
        catch( SocketTimeoutException e ) {
        	e.printStackTrace( );
        	
        	ret.success = false;
        	ret.message = e.getMessage( );
        }
        catch( IOException e ) {
            e.printStackTrace( );
            
            ret.success = false;
        	ret.message = e.getMessage( );
        }
        finally {
        	if ( connection != null ) {
        		connection.disconnect( );
        	}
        }
		
		return ret;
	} // SyncResult ProductDatabase
	
} // class SyncTask
