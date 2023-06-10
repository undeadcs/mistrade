package ru.undeadcs.mistrade;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.Formatter;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteException;
import android.database.sqlite.SQLiteOpenHelper;
import android.text.TextUtils;
import android.util.Log;

// перейти на другое понятие: вместо Full - Main, потомучто Full не является полной базой, в ней отсутствуют товары, прайс

public class Database {
    public static Helper m_objHelper = null;
    
    public static final int EXPORT_STATE_NEW		= 0; // новая запись
    public static final int EXPORT_STATE_EXPORTED	= 1; // выгруженная запись
    public static final int EXPORT_STATE_FAILED		= 2; // выгрузка провалилась
    
    public static final int EXPORT_TYPE_REQUEST		= 0; // заявка
    
    public static final int UPDATE_TYPE_MAIN	= 0; // основная база
    public static final int UPDATE_TYPE_PRODUCT	= 1; // номенклатура
    public static final int UPDATE_TYPE_EXPORT	= 2; // экспорт
    public static final int UPDATE_TYPE_EXEC	= 3; // факт самого запуска
    
    private static final String CREATE_TABLE_EXPORT = "CREATE TABLE IF NOT EXISTS ud_export (" +
            "_id INTEGER PRIMARY KEY AUTOINCREMENT," +
            "export_table VARCHAR(254)," +
            "export_object_id INTEGER," +
            "export_type INTEGER," +
            "export_state INTEGER," +
            "export_error TEXT," +
            "export_data TEXT );";
    
    private static final String CREATE_TABLE_SAVED_LOGIN = "CREATE TABLE IF NOT EXISTS ud_saved_login (" +
        	"_id INTEGER PRIMARY KEY," +
        	"saved_login_login VARCHAR(254)," +
        	"saved_login_password VARCHAR(254)," +
        	"saved_login_date DATETIME );";
    
    public static void init( Context context ) {
        m_objHelper = new Helper( context );
        m_objHelper.OpenDatabase( );
        m_objHelper.OpenDatabaseProduct( );
        m_objHelper.OpenDatabaseLocal( );
    } // void init
    
    public static void terminate( ) {
    	m_objHelper.close( );
    	m_objHelper.closeProduct( );
    	m_objHelper.closeLocal( );
    } // void terminate
    
    public static class Helper extends SQLiteOpenHelper {
        public static String DB_PATH = "";
        public static String DB_NAME = "mistrade_android.db";
        public static String DB_PRODUCT_NAME = "mistrade_android_product.db";
        public static String DB_LOCAL_NAME = "mistrade_local.db";
        private SQLiteDatabase m_objDb = null;
        private SQLiteDatabase m_objDbProduct = null;
        private SQLiteDatabase m_objDbLocal = null;
        private Context m_objContext = null;
        
        public Helper( Context context ) {
            super( context, DB_NAME, null, 1 );
            m_objContext = context;
            DB_PATH = context.getFilesDir( ).getPath( );
        }
        
        @Override
        public void onCreate( SQLiteDatabase db ) { }
        
        @Override
        public void onUpgrade( SQLiteDatabase db, int oldVersion, int newVersion ) { }
        
        private void InitialQueries( ) {
            ArrayList< String > arrQuery = new ArrayList< String >( );

            /*arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_admin` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`admin_login` VARCHAR(20)," +
                "`admin_password` VARCHAR(128)," +
                "`admin_rank` INTEGER );"
            );*/
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_client` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`client_manager_id` INTEGER," +
                "`client_manager_code` VARCHAR(254)," +
                "`client_code` VARCHAR(254)," +
                "`client_name` VARCHAR(254)," +
                "`client_name_lower` VARCHAR(254)," +
                "`client_limit` FLOAT," +
                "`client_phone` VARCHAR(254)," +
                "`client_addr` VARCHAR(254)," +
                "`client_price` INTEGER );"
            );
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_category` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`category_parent_id` INTEGER," +
                "`category_code` VARCHAR(254)," +
                "`category_name` VARCHAR(254) );"
            );
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_manager` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`manager_code` VARCHAR(254)," +
                "`manager_name` VARCHAR(254)," +
                "`manager_login` VARCHAR(254)," +
                "`manager_password` VARCHAR(254) );"
            );
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_request` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`request_client_id` INTEGER," +
                "`request_client_code` VARCHAR(254)," +
                "`request_code` VARCHAR(254)," +
                "`request_type` INTEGER," +
                "`request_creation_date` DATETIME," +
                "`request_receive_date` DATETIME," +
                "`request_trade_point` VARCHAR(254)," +
                "`request_time1_from` INTEGER," +
                "`request_time1_to` INTEGER," +
                "`request_time2_from` INTEGER," +
                "`request_time2_to` INTEGER," +
                "`request_flag_money_must_be` INTEGER," +
                "`request_flag_money_simple` INTEGER," +
                "`request_flag_certificate` INTEGER," +
                "`request_flag_sticker` INTEGER );"
            );
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_request_product` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`request_product_request_id` INTEGER," +
                "`request_product_product_id` INTEGER," +
                "`request_product_code` VARCHAR(254)," +
                "`request_product_amount` FLOAT(10,2) );"
            );
            
            arrQuery.add(
            	"CREATE TABLE IF NOT EXISTS ud_version (" +
                "_id INTEGER PRIMARY KEY," +
                "version_number VARCHAR(254)," +
                "version_datetime DATETIME );"
            );
            
            CallQueries( m_objDb, arrQuery );
        } // void InitialQueries
        
        public void OpenDatabase( ) {
            if ( IsDatabaseExists( DB_NAME ) == false ) {
                CreateDatabase( DB_NAME );
            }
            
            try {
                m_objDb = SQLiteDatabase.openDatabase( DB_PATH + "/" + DB_NAME, null, SQLiteDatabase.OPEN_READONLY | SQLiteDatabase.NO_LOCALIZED_COLLATORS );
                InitialQueries( );
            }
            catch( SQLiteException e ) {
                e.printStackTrace( );
            }
        } // void OpenDatabase
        
        public synchronized void close( ) {
            if ( m_objDb != null ) {
                m_objDb.close( );
            }
        } // void close
        
        private void InitialQueriesProduct( ) {
            ArrayList< String > arrQuery = new ArrayList< String >( );

            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_product` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`product_category_id` INT(10)," +
                "`product_code` VARCHAR(254)," +
                "`product_category` VARCHAR(254)," +
                "`product_name` VARCHAR(254)," +
                "`product_name_lower` VARCHAR(254)," +
                "`product_price` FLOAT(10,2)," +
                "`product_saldo` FLOAT(10,2)," +
                "`product_unit` INTEGER );"
            );
            
            arrQuery.add(
        		"CREATE TABLE IF NOT EXISTS ud_product_price (" +
    			"_id INTEGER PRIMARY KEY," +
    			"product_price_product_id INTEGER," +
    			"product_price_product_code VARCHAR(254)," +
    			"product_price_category_code INTEGER," +
                "product_price_price FLOAT(10,2)," +
    			"product_price_nds FLOAT(10,2) );"
            );
            
            arrQuery.add(
            	"CREATE TABLE IF NOT EXISTS ud_version (" +
                "_id INTEGER PRIMARY KEY," +
                "version_number VARCHAR(254)," +
                "version_datetime DATETIME );"
            );
            
            CallQueries( m_objDbProduct, arrQuery );
        } // void InitialQueriesProduct
        
        public void OpenDatabaseProduct( ) {
            if ( IsDatabaseExists( DB_PRODUCT_NAME ) == false ) {
                CreateDatabase( DB_PRODUCT_NAME );
            }
            
            try {
                m_objDbProduct = SQLiteDatabase.openDatabase( DB_PATH + "/" + DB_PRODUCT_NAME, null, SQLiteDatabase.OPEN_READONLY | SQLiteDatabase.NO_LOCALIZED_COLLATORS );
                InitialQueriesProduct( );
            }
            catch( SQLiteException e ) {
                e.printStackTrace( );
            }
        } // void OpenDatabase
        
        public synchronized void closeProduct( ) {
            if ( m_objDbProduct != null ) {
            	m_objDbProduct.close( );
            }
        } // void closeProduct
        
        private void InitialQueriesLocal( ) {
            ArrayList< String > arrQuery = new ArrayList< String >( );

            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_request` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`request_client_id` INTEGER," +
                "`request_client_code` VARCHAR(254)," +
                "`request_code` VARCHAR(254)," +
                "`request_type` INTEGER," +
                "`request_creation_date` DATETIME," +
                "`request_receive_date` DATETIME," +
                "`request_trade_point` VARCHAR(254)," +
                "`request_time1_from` INTEGER," +
                "`request_time1_to` INTEGER," +
                "`request_time2_from` INTEGER," +
                "`request_time2_to` INTEGER," +
                "`request_flag_money_must_be` INTEGER," +
                "`request_flag_money_simple` INTEGER," +
                "`request_flag_certificate` INTEGER," +
                "`request_flag_sticker` INTEGER );"
            );
        
            arrQuery.add(
                "CREATE TABLE IF NOT EXISTS `ud_request_product` (" +
                "`_id` INTEGER PRIMARY KEY," +
                "`request_product_request_id` INTEGER," +
                "`request_product_product_id` INTEGER," +
                "`request_product_code` VARCHAR(254)," +
                "`request_product_amount` FLOAT(10,2) );"
            );
            
            arrQuery.add( CREATE_TABLE_EXPORT );
            arrQuery.add( CREATE_TABLE_SAVED_LOGIN );
            
            arrQuery.add(
            	"CREATE TABLE IF NOT EXISTS ud_update (" +
            	"_id INTEGER PRIMARY KEY," +
            	"update_type INTEGER," +
            	"update_date DATETIME );"
            );
            
            CallQueries( m_objDbLocal, arrQuery );
        } // void InitialQueriesLocal
        
        public void OpenDatabaseLocal( ) {
        	if ( IsDatabaseExists( DB_LOCAL_NAME ) == false ) {
                CreateDatabase( DB_LOCAL_NAME );
            }
            
            try {
                m_objDbLocal = SQLiteDatabase.openDatabase( DB_PATH + "/" + DB_LOCAL_NAME, null, SQLiteDatabase.OPEN_READWRITE | SQLiteDatabase.NO_LOCALIZED_COLLATORS );
                InitialQueriesLocal( );
            }
            catch( SQLiteException e ) {
                e.printStackTrace( );
            }
        } // void OpenDatabaseLocal
        
        public synchronized void closeLocal( ) {
            if ( m_objDbLocal != null ) {
            	m_objDbLocal.close( );
            }
        } // void closeLocal
        
        private void CreateDatabase( String szName ) {
            try {
            	File file = new File( DB_PATH + "/" + szName );
            	if ( !file.exists( ) ) {
            		file.createNewFile( );
            	}
            	
                CopyDatabaseFile( szName );
            }
            catch( IOException e ) {
            }
        } // void CreateDatabase
        
        private boolean IsDatabaseExists( String szName ) {
            SQLiteDatabase checkDB = null;
 
            try {
                checkDB = SQLiteDatabase.openDatabase( DB_PATH + "/" + szName, null, SQLiteDatabase.OPEN_READONLY | SQLiteDatabase.NO_LOCALIZED_COLLATORS );
            }
            catch( SQLiteException e ) {
            }
 
            if( checkDB != null ) {
                checkDB.close( );
            }
             
            return checkDB != null;
        } // boolean IsDatabaseExists
        
        private void CopyDatabaseFile( String szName ) throws IOException {
            InputStream src = m_objContext.getAssets( ).open( szName );
            OutputStream dst = new FileOutputStream( DB_PATH + "/" + szName );
            
            byte buf[ ] = new byte[ 1024 ];
            int len = 0;
            while( ( len = src.read( buf ) ) > 0 ) {
                dst.write( buf, 0, len );
            }
            
            dst.flush( );
            dst.close( );
            src.close( );
        } // void CopyDatabaseFile
        
        private void CallQueries( SQLiteDatabase db, ArrayList< String > arrQuery ) {
            if ( db == null ) {
                return;
            }
            
            for( String tmp : arrQuery ) {
            	db.execSQL( tmp );
            }
        }
        
        /**
         * Выборка объектов
         */
        static final String[ ] columnsManager = new String[ ] {
        	"_id",
        	"manager_code",
        	"manager_name",
        	"manager_login",
        	"manager_password"
        };
        
        /**
         * Получение пользователя
         */
        public CManager GetManager( String login, String password ) {
        	String where = "manager_login = ?";
        	String[ ] whereArgs = new String[ ] { login };
        	Cursor cursor = m_objDb.query( "ud_manager", columnsManager, where, whereArgs, null, null, null, "1" );
        	if ( cursor.getCount( ) > 0 ) {
        		cursor.moveToFirst( );
        		String szPassword = cursor.getString( cursor.getColumnIndex( "manager_password" ) );
        		
        		try {
        			MessageDigest digest = MessageDigest.getInstance( "sha1" );
        			digest.reset( );
        			digest.update( password.getBytes( ) );
        			
        			byte[ ] hash = digest.digest( );
        			
        			Formatter formatter = new Formatter( );
        		    for( byte b : hash ) {
        		        formatter.format( "%02x", b );
        		    }
        		    
        		    String result = formatter.toString( );
        		    formatter.close( );
        		    
        		    if ( result.equals( szPassword ) ) {
        		    	CManager manager = new CManager( );
        		    	manager.id		= cursor.getInt( cursor.getColumnIndex( "_id" ) );
        		    	manager.code	= cursor.getString( cursor.getColumnIndex( "manager_code" ) );
        		    	manager.name	= cursor.getString( cursor.getColumnIndex( "manager_name" ) );
        		    	manager.login	= login;
        		    	return manager;
        		    }
        		}
        		catch ( NoSuchAlgorithmException e ) {
        			e.printStackTrace( );
        		}
        		
        		cursor.close( );
        	}
        	
            return null;
        } // CManager GetManager
        
        public void ListManager( ) {
        	Cursor cursor = m_objDb.query( "ud_manager", columnsManager, null, null, null, null, null );
        	int iCount = cursor.getCount( );
        	Log.i( "Manager", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Manager", String.format(
    					"manager_id=%d, " +
    		        	"manager_code='%s', " +
    		        	"manager_name='%s', " +
    		        	"manager_login='%s', " +
    		        	"manager_password='%s'",
    		            cursor.getInt( cursor.getColumnIndex( "_id" ) ),
    		            cursor.getString( cursor.getColumnIndex( "manager_code" ) ),
    		            cursor.getString( cursor.getColumnIndex( "manager_name" ) ),
    		            cursor.getString( cursor.getColumnIndex( "manager_login" ) ),
    		            cursor.getString( cursor.getColumnIndex( "manager_password" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListManager
        
        static final String[ ] columnsClient = new String[ ] {
            "_id",
            "client_manager_id",
            "client_manager_code",
            "client_code",
            "client_name",
            "client_name_lower",
            "client_limit",
            "client_phone",
            "client_addr",
            "client_price"
        };
        
        /**
         * Получение списка контрагентов
         */
        public Cursor GetClient( String search ) {
            String where = null;
            String[ ] whereArgs = null;
            
            if ( search.length( ) > 0 ) {
                where = "client_name_lower LIKE ?";
                whereArgs = new String[ ] { "%" + search.toLowerCase( ) + "%" };
            }
            
            return m_objDb.query( "ud_client", columnsClient, where, whereArgs, null, null, null, null );
        } // Cursor GetClient
        
        public Cursor GetClient( int iClientId ) {
        	String where = "_id = ? ";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iClientId ) };
            
            return m_objDb.query( "ud_client", columnsClient, where, whereArgs, null, null, null, "1" );
        } // Cursor GetClient
        
        public void ListClient( ) {
        	Cursor cursor = m_objDb.query( "ud_client", columnsClient, null, null, null, null, null );
        	int iCount = cursor.getCount( );
        	Log.i( "Client", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Manager", String.format(
    					"_id=%d, " +
    		            "client_manager_id=%d, "+
    		            "client_manager_code='%s', "+
    		            "client_code='%s', "+
    		            "client_name='%s', "+
    		            "client_name_lower='%s', "+
    		            "client_limit=%f, "+
    		            "client_phone='%s', "+
    		            "client_addr='%s', "+
    		            "client_price=%d",
    		            cursor.getInt( cursor.getColumnIndex( "_id" ) ),
    		            cursor.getInt( cursor.getColumnIndex( "client_manager_id" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_manager_code" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_code" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_name" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_name_lower" ) ),
    		            cursor.getFloat( cursor.getColumnIndex( "client_limit" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_phone" ) ),
    		            cursor.getString( cursor.getColumnIndex( "client_addr" ) ),
    		            cursor.getInt( cursor.getColumnIndex( "client_price" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListClient
        
        static final String[ ] columnsCategory = new String[ ] {
            "_id",
            "category_parent_id",
            "category_code",
            "category_name"
        };
        
        public Cursor GetCategory( ) {
            return GetCategory( 0 );
        } // Cursor GetCategory
        
        public Cursor GetCategory( int iParentId ) {
            String where = "category_parent_id = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iParentId ) };
            
            return m_objDb.query( "ud_category", columnsCategory, where, whereArgs, null, null, null, null );
        } // Cursor GetCategory
        
        static final String[ ] columnsProduct = new String[ ] {
            "_id",
            "product_category_id",
            "product_code",
            "product_category",
            "product_name",
            "product_name_lower",
            "product_price",
            "product_saldo",
            "product_unit"
        };
        
        public Cursor GetProduct( ) {
        	String where = "product_saldo > ?";
            String[ ] whereArgs = new String[ ] { "0" };
            
            return m_objDbProduct.query( "ud_product", columnsProduct, where, whereArgs, null, null, null, null );
        } // Cursor GetProduct
        
        public Cursor GetProduct( int iCategoryId, String search ) {
            String where = "product_saldo > 0 AND product_category_id = ? AND product_name_lower LIKE ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iCategoryId ), "%" + search.toLowerCase( ) + "%" };
            
            return m_objDbProduct.query( "ud_product", columnsProduct, where, whereArgs, null, null, null, null );
        } // Cursor GetProduct
        
        /**
         * Выборка только по ID категории
         * @param int iCategoryId
         * @return Cursor
         */
        public Cursor GetProduct( int iCategoryId ) {
        	String where = "product_saldo > 0 AND product_category_id = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iCategoryId ) };
            
            return m_objDbProduct.query( "ud_product", columnsProduct, where, whereArgs, null, null, null, null );
        }
        
        /**
         * Поиск по шаблону названия
         * @param String pattern
         * @return Cursor
         */
        public Cursor GetProduct( String pattern ) {
        	String where = "product_saldo > 0 AND product_name_lower LIKE ?";
            String[ ] whereArgs = new String[ ] { "%" + pattern.toLowerCase( ) + "%" };
            
            return m_objDbProduct.query( "ud_product", columnsProduct, where, whereArgs, null, null, null, null );
        }
        
        static final String[ ] columnsExport = new String[ ] {
        	"_id",
            "export_table",
            "export_object_id",
            "export_type",
            "export_state",
            "export_error",
            "export_data"
        };
        
        public Cursor GetExport( int iType ) {
        	String where = "export_state = ? AND export_type = ?";
        	String[ ] whereArgs = new String[ ] { String.format( "%d", EXPORT_STATE_NEW ), String.format( "%d", iType ) };
        	
        	return m_objDbLocal.query( "ud_export", columnsExport, where, whereArgs, null, null, null );
        } // Cursor GetExport
        
        public long AddExport( String szTable, long iId, int iType, String szData ) {
        	ContentValues values = new ContentValues( );
        	values.put( "export_table",		szTable				);
        	values.put( "export_object_id",	iId					);
        	values.put( "export_type",		iType				);
        	values.put( "export_state",		EXPORT_STATE_NEW	);
        	values.put( "export_error",     ""   				);
        	values.put( "export_data",		szData				);
        	
        	return m_objDbLocal.insert( "ud_export", null, values );
        } // long AddExport
        
        /**
         * Сохранение экспорта
         */
        public long SaveExport( String data ) {
        	ContentValues values = new ContentValues( );
        	values.put( "export_table",     ""   );
        	values.put( "export_object_id", 0    );
        	values.put( "export_type",      0    );
        	values.put( "export_error",     ""   );
        	values.put( "export_data",      data );
        	
        	return m_objDbLocal.insert( "ud_export", null, values );
        } // long SaveExport
        
        public void SaveExport( long[ ] arrId, int[ ] arrState, String[ ] arrError ) {
        	ContentValues values = new ContentValues( );
        	String where = "_id = ?";
        	String[ ] whereArgs = new String[ 1 ];
        	
        	for( int i = 0; i < arrId.length; ++i ) {
        		values.put( "export_state", arrState[ i ] );
        		values.put( "export_error", arrError[ i ] );
        		whereArgs[ 0 ] = String.format( "%d", arrId[ i ] );
        		m_objDbLocal.update( "ud_export", values, where, whereArgs );
        	}
        } // void SaveExport
        
        public void ListExport( ) {
        	Cursor cursor = m_objDbLocal.query( "ud_export", columnsExport, null, null, null, null, null );
        	int iCount = cursor.getCount( );
        	Log.i( "Export", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Export", String.format(
    					"export_table='%s', " +
    		            "export_object_id=%d, " +
    		            "export_type=%d, " +
    		            "export_state=%d, " +
    		            "export_error='%s', " +
    		            "export_data='%s'",
    		            cursor.getString( cursor.getColumnIndex( "export_table" ) ),
    		            cursor.getInt( cursor.getColumnIndex( "export_object_id" ) ),
    		            cursor.getInt( cursor.getColumnIndex( "export_type" ) ),
    		            cursor.getInt( cursor.getColumnIndex( "export_state" ) ),
    		            cursor.getString( cursor.getColumnIndex( "export_error" ) ),
    		            cursor.getString( cursor.getColumnIndex( "export_data" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListExport
        
        static final String[ ] columnsVersion = new String[ ] {
        	"_id",
            "version_number",
            "version_datetime"
        };
        
        /**
         * Получение версии текущей базы данных
         */
        public String GetVersion( ) {
        	String ret = "";
        	Cursor cursor = m_objDb.query( "ud_version", columnsVersion, null, null, null, null, "version_number DESC", "1" );
        	if ( cursor.getCount( ) > 0 ) {
        		cursor.moveToFirst( );
        		ret = cursor.getString( cursor.getColumnIndex( "version_number" ) );
        	}
        	
        	return ret;
        } // String GetVersion
        
        /**
         * Получение версии текущей базы данных
         */
        public String GetVersionProduct( ) {
        	String ret = "";
        	Cursor cursor = m_objDbProduct.query( "ud_version", columnsVersion, null, null, null, null, "version_number DESC", "1" );
        	if ( cursor.getCount( ) > 0 ) {
        		cursor.moveToFirst( );
        		ret = cursor.getString( cursor.getColumnIndex( "version_number" ) );
        	}
        	
        	return ret;
        } // String GetVersion
        
        /**
         * Получение версии из другой базы в постороннем файле
         */
        public String GetVersion( String szDatabaseFilePath ) {
        	String ret = null;
        	SQLiteDatabase db = null;
        	
        	try {
        		db = SQLiteDatabase.openDatabase( szDatabaseFilePath, null, SQLiteDatabase.OPEN_READONLY | SQLiteDatabase.NO_LOCALIZED_COLLATORS );
        	}
        	catch( SQLiteException e ) {
        		e.printStackTrace( );
        	}
        	
        	if ( db != null ) {
        		Cursor cursor = db.query( "ud_version", columnsVersion, null, null, null, null, "version_number DESC", "1" );
            	if ( cursor.getCount( ) > 0 ) {
            		cursor.moveToFirst( );
            		ret = cursor.getString( cursor.getColumnIndex( "version_number" ) );
            	}
            	
        		db.close( );
        	}
        	
        	return ret;
        } // String GetVersion
        
        static final String[ ] columnsUpdate = new String[ ] {
        	"_id",
        	"update_type",
        	"update_date"
        };
        
        /**
         * Получение апдейта по дате
         */
        public Cursor GetUpdate( int iType, String szDate, boolean bDateOnly ) {
        	String where = "update_type = ? AND " + ( bDateOnly ? "date(update_date)" : "update_date" ) + " = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iType ), szDate };
            
            return m_objDbLocal.query( "ud_update", columnsUpdate, where, whereArgs, null, null, "update_date DESC", null );
        } // Cursor GetUpdate
        
        /**
         * Получение самой последней записи об обновлении
         */
        public Cursor GetUpdate( int iType, boolean bLast ) {
        	String where = "update_type = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iType ) };
            
            return m_objDbLocal.query( "ud_update", columnsUpdate, where, whereArgs, null, null, "update_date DESC", bLast ? "1" : null );
        } // Cursor GetUpdate
        
        /**
         * Сохранение даты обновления
         */
        public long SaveUpdate( int iType, String date ) {
        	ContentValues values = new ContentValues( );
        	values.put( "update_type", iType	);
        	values.put( "update_date", date		);
        	
        	return m_objDbLocal.insert( "ud_update", null, values );
        } // long SaveUpdate
        
        public void ListUpdate( ) {
        	Cursor cursor = m_objDbLocal.query( "ud_update", columnsUpdate, null, null, null, null, null, null );
        	int iCount = cursor.getCount( );
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Update", String.format(
        		        "update_type=%d, " +
        		        "update_date='%s'",
        		        cursor.getInt( cursor.getColumnIndex( "update_type" ) ),
        		        cursor.getString( cursor.getColumnIndex( "update_date" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListUpdates
        
        static final String[ ] columnsSavedLogin = new String[ ] {
        	"_id",
        	"saved_login_login",
        	"saved_login_password",
        	"saved_login_date"
        };
        
        public Cursor GetSavedLogin( ) {
        	return m_objDbLocal.query( "ud_saved_login", columnsSavedLogin, null, null, null, null, null, null );
        } // Cursor GetSavedLogin
        
        public void SaveLogin( String szLogin, String szPassword ) {
        	m_objDbLocal.execSQL( "DROP TABLE IF EXISTS ud_saved_login;" );
        	m_objDbLocal.execSQL( CREATE_TABLE_SAVED_LOGIN );
        	
        	SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss"); 
        	Date date = new Date( );
        	
        	ContentValues values = new ContentValues( );
        	values.put( "saved_login_login",	szLogin						);
        	values.put( "saved_login_password",	szPassword					);
        	values.put( "saved_login_date",		dateFormat.format( date )	);
        	
        	m_objDbLocal.insert( "ud_saved_login", null, values );
        } // void SaveLogin
        
        static final String[ ] columnsProductPrice = new String[ ] {
        	"_id",
			"product_price_product_id",
			"product_price_product_code",
			"product_price_category_code",
            "product_price_price",
			"product_price_nds"
        };
        
        public Cursor GetPrice( int iProductId, int iCategoryId ) {
        	String where = "product_price_product_id = ? AND product_price_category_code = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iProductId ), String.format( "%d", iCategoryId ) };
            
            return m_objDbProduct.query( "ud_product_price", columnsProductPrice, where, whereArgs, null, null, null, null );
        } // Cursor GetPrice
        
        static final String[ ] columnsRequest = new String[ ] {
        	 "_id",
             "request_client_id",
             "request_client_code",
             "request_code",
             "request_type",
             "request_creation_date",
             "request_receive_date",
             "request_trade_point",
             "request_time1_from",
             "request_time1_to",
             "request_time2_from",
             "request_time2_to",
             "request_flag_money_must_be",
             "request_flag_money_simple",
             "request_flag_certificate",
             "request_flag_sticker"
        };
        
        static final String[ ] columnsRequestProduct = new String[ ] {
        	"_id",
            "request_product_request_id",
            "request_product_product_id",
            "request_product_code",
            "request_product_amount"
        };
        
        public long SaveRequestLocal( CRequest objRequest ) {
        	ContentValues values = new ContentValues( );
        	values.put( "request_client_id",			objRequest.client_id			);
        	values.put( "request_client_code",			objRequest.client_code			);
        	values.put( "request_code",					objRequest.code					);
        	values.put( "request_type",					objRequest.type					);
        	values.put( "request_receive_date",			Util.convertDateRUtoISO( objRequest.receive_date ) );
        	values.put( "request_trade_point",			objRequest.trade_point			);
        	values.put( "request_time1_from",			objRequest.time1_from			);
        	values.put( "request_time1_to",				objRequest.time1_to				);
        	values.put( "request_time2_from",			objRequest.time2_from			);
        	values.put( "request_time2_to",				objRequest.time2_to				);
        	values.put( "request_flag_money_must_be",	objRequest.flag_money_must_be	);
        	values.put( "request_flag_money_simple",	objRequest.flag_money_simple	);
        	values.put( "request_flag_certificate",		objRequest.flag_certificate		);
        	values.put( "request_flag_sticker",			objRequest.flag_sticker			);
        	
        	long iRequestId = m_objDbLocal.insert( "ud_request", null, values );
        	
        	if ( iRequestId > -1 ) {
        		objRequest.id = iRequestId;
        		
        		CRequestProduct product = null;
        		for( int i = 0; i < objRequest.products.size( ); ++i ) {
        			product = objRequest.products.get( i );
        			if ( product != null ) {
        				values = new ContentValues( );
        				values.put( "request_product_request_id",	iRequestId			);
        				values.put( "request_product_product_id",	product.product_id	);
        				values.put( "request_product_code",			product.code		);
        				values.put( "request_product_amount",		product.amount		);
        				
        				m_objDbLocal.insert( "ud_request_product", null, values );
        			}
        		}
        	}
        	
        	return iRequestId;
        } // long SaveRequestLocal
        
        public Cursor GetRequest( ) {
        	return m_objDb.query( "ud_request", columnsRequest, null, null, null, null, null );
        } // Cursor GetRequest
        
        public Cursor GetRequest( String szDateFrom, String szDateTo, boolean bMain ) {
        	String where = null;
        	String[ ] whereArgs = null;
        	if ( ( szDateFrom.length( ) > 0 ) || ( szDateTo.length( ) > 0 ) ) {
        		ArrayList< String > tmp		= new ArrayList< String >( ),
        							tmp1	= new ArrayList< String >( );
        		
        		if ( szDateFrom.length( ) > 0 ) {
        			tmp.add( "r.request_receive_date >= ?" );
        			tmp1.add( Util.convertDateRUtoISO( szDateFrom ) );
        		}
        		if ( szDateTo.length( ) > 0 ) {
        			tmp.add( "r.request_receive_date <= ?" );
        			tmp1.add( Util.convertDateRUtoISO( szDateTo ) );
        		}
        		
        		where = TextUtils.join( " AND ", tmp.toArray( ) );
        		whereArgs = tmp1.toArray( new String[ tmp1.size( ) ] );
        	}
        	
        	SQLiteDatabase db = m_objDb;
        	String szSql = "SELECT r._id, r.request_receive_date, r.request_trade_point, c.client_name " +
        			"FROM ud_request r " +
        			"LEFT JOIN ud_client c ON c._id = r.request_client_id " +
        			"WHERE " + where + " " +
        			"ORDER BY r._id DESC";
        	
        	if ( !bMain ) {
        		db = m_objDbLocal;
        		szSql = "SELECT r._id, r.request_client_id, r.request_receive_date, r.request_trade_point, e.export_state " +
            			"FROM ud_request r " +
            			"LEFT JOIN ud_export e ON e.export_type = " + Integer.toString( EXPORT_TYPE_REQUEST ) + " AND e.export_object_id = r._id " +
            			"WHERE " + where + " " +
            			"ORDER BY r._id DESC";
        	}
        	
        	return db.rawQuery( szSql, whereArgs );
        } // Cursor GetRequest
        
        public Cursor GetRequestProduct( int iRequestId ) {
        	String where = "request_product_request_id = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iRequestId ) };
            
            return m_objDb.query( "ud_request_product", columnsRequestProduct, where, whereArgs, null, null, null, null );
        } // Cursor GetRequestProduct
        
        public Cursor GetRequestLocal( ) {
        	return m_objDbLocal.query( "ud_request", columnsRequest, null, null, null, null, null );
        } // Cursor GetRequestLocal
        
        public Cursor GetRequestProductLocal( int iRequestId ) {
        	String where = "request_product_request_id = ?";
            String[ ] whereArgs = new String[ ] { String.format( "%d", iRequestId ) };
            
            return m_objDbLocal.query( "ud_request_product", columnsRequestProduct, where, whereArgs, null, null, null, null );
        } // Cursor GetRequestProductLocal
        
        public void ListRequestLocal( ) {
        	Cursor cursor = GetRequestLocal( );
        	int iCount = cursor.getCount( );
        	Log.i( "Request", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Request", String.format(
    					 "request_client_id=%d, " +
    		        	 "request_client_code='%s', " +
    		        	 "request_code='%s', " +
    		        	 "request_type=%d, " +
    		        	 "request_creation_date='%s', " +
    		        	 "request_receive_date='%s', " +
    		        	 "request_trade_point='%s', " +
    		        	 "request_time1_from=%d, " +
    		        	 "request_time1_to=%d, " +
    		        	 "request_time2_from=%d, " +
    		        	 "request_time2_to=%d, " +
    		        	 "request_flag_money_must_be=%d, " +
    		        	 "request_flag_money_simple=%d, " +
    		        	 "request_flag_certificate=%d, " +
    		        	 "request_flag_sticker=%d",
    		        	 cursor.getInt( cursor.getColumnIndex( "request_client_id" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_client_code" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_code" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_type" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_creation_date" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_receive_date" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_trade_point" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time1_from" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time1_to" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time2_from" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time2_to" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_money_must_be" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_money_simple" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_certificate" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_sticker" ) )
        			) );
        			
        			ListRequestProductLocal( cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListRequestLocal
        
        public void ListRequestProductLocal( int iRequestId ) {
        	Cursor cursor = GetRequestProductLocal( iRequestId );
        	int iCount = cursor.getCount( );
        	Log.i( "RequestProduct", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "RequestProduct", String.format(
    					"request_product_request_id=%d, " +
    		            "request_product_product_id=%d, " +
    		            "request_product_code='%s', " +
    		            "request_product_amount=%.02f",
    		        	 cursor.getInt( cursor.getColumnIndex( "request_product_request_id" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_product_product_id" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_product_code" ) ),
    		        	 cursor.getFloat( cursor.getColumnIndex( "request_product_amount" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListRequestProductLocal
        
        public void ListRequest( ) {
        	Cursor cursor = GetRequest( );
        	int iCount = cursor.getCount( );
        	Log.i( "Request", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "Request", String.format(
    					 "request_client_id=%d, " +
    		        	 "request_client_code='%s', " +
    		        	 "request_code='%s', " +
    		        	 "request_type=%d, " +
    		        	 "request_creation_date='%s', " +
    		        	 "request_receive_date='%s', " +
    		        	 "request_trade_point='%s', " +
    		        	 "request_time1_from=%d, " +
    		        	 "request_time1_to=%d, " +
    		        	 "request_time2_from=%d, " +
    		        	 "request_time2_to=%d, " +
    		        	 "request_flag_money_must_be=%d, " +
    		        	 "request_flag_money_simple=%d, " +
    		        	 "request_flag_certificate=%d, " +
    		        	 "request_flag_sticker=%d",
    		        	 cursor.getInt( cursor.getColumnIndex( "request_client_id" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_client_code" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_code" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_type" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_creation_date" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_receive_date" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_trade_point" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time1_from" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time1_to" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time2_from" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_time2_to" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_money_must_be" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_money_simple" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_certificate" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_flag_sticker" ) )
        			) );
        			
        			ListRequestProductLocal( cursor.getInt( cursor.getColumnIndex( "_id" ) ) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListRequest
        
        public void ListRequestProduct( int iRequestId ) {
        	Cursor cursor = GetRequestProduct( iRequestId );
        	int iCount = cursor.getCount( );
        	Log.i( "RequestProduct", String.format( "count=%d", iCount ) );
        	
        	if ( iCount > 0 ) {
        		cursor.moveToFirst( );
        		
        		do {
        			Log.i( "RequestProduct", String.format(
    					"request_product_request_id=%d, " +
    		            "request_product_product_id=%d, " +
    		            "request_product_code='%s', " +
    		            "request_product_amount=%.02f",
    		        	 cursor.getInt( cursor.getColumnIndex( "request_product_request_id" ) ),
    		        	 cursor.getInt( cursor.getColumnIndex( "request_product_product_id" ) ),
    		        	 cursor.getString( cursor.getColumnIndex( "request_product_code" ) ),
    		        	 cursor.getFloat( cursor.getColumnIndex( "request_product_amount" ) )
        			) );
        		} while( cursor.moveToNext( ) );
        	}
        } // void ListRequestProduct
        
    } // class Helper
    
} // class Database
