package ru.undeadcs.mistrade;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Calendar;

import android.os.AsyncTask;
import android.os.Bundle;
import android.app.ActionBar;
import android.app.Activity;
import android.app.DatePickerDialog;
import android.app.FragmentTransaction;
import android.app.ActionBar.Tab;
import android.app.ProgressDialog;
import android.content.Intent;
import android.database.Cursor;
import android.support.v4.app.NavUtils;
import android.text.Editable;
import android.text.InputType;
import android.text.TextWatcher;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.DatePicker;
import android.widget.EditText;
import android.widget.FrameLayout;
import android.widget.LinearLayout;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;

public class Request extends Activity implements DatePickerDialog.OnDateSetListener, ActionBar.TabListener {
    static final int PICK_CLIENT  = 1;
    static final int PICK_PRODUCT = 2;
    
    private String			m_szDateReceive			= "";
    private Button			m_btnRecevieDate		= null,
    						m_btnDelete				= null;
    private View			m_viewRequest			= null,
    						m_viewProduct			= null;
    private TextView		m_txtClientName			= null,
    						m_txtTotal				= null,
    						m_txtNds				= null;
    private FrameLayout		m_viewFrame				= null;
    private String			m_szCurrentTab			= "";
    private LinearLayout	m_llProducts			= null;
    private EditText		m_inpTradePoint			= null,
    						m_inpDeliveryTime1From	= null,
    						m_inpDeliveryTime1To	= null,
    						m_inpDeliveryTime2From	= null,
    						m_inpDeliveryTime2To	= null;
    private RadioGroup		m_radDocType			= null;
    private RadioButton		m_rbInvoice				= null,
    						m_rbBill				= null;
    private CheckBox		m_cbMoneyMustBe			= null,
    						m_cbMoneySimple			= null,
    						m_cbCertificate			= null,
    						m_cbSticker				= null;
    private Tab				m_tabParam				= null,
    						m_tabProduct			= null;
    private ProgressDialog	m_dlgProgress			= null;
    
    private static ArrayList< CProductRow > m_arrRow   = new ArrayList< CProductRow >( );
    private CheckProduct                    m_objCheck = new CheckProduct( );
    
    public static CRequest m_objRequest = new CRequest( );
    public static String m_szLastTab = "";

    @Override
    protected void onCreate( Bundle savedInstanceState ) {
        super.onCreate( savedInstanceState );
        setContentView( R.layout.activity_request_frame );
        // Show the Up button in the action bar.
        ActionBar actionBar = getActionBar( );
        actionBar.setDisplayHomeAsUpEnabled( true );
        actionBar.setNavigationMode( ActionBar.NAVIGATION_MODE_TABS );

        LayoutInflater inflater = getLayoutInflater( );
        m_viewRequest	= inflater.inflate( R.layout.activity_request, null );
        m_viewProduct	= inflater.inflate( R.layout.activity_product, null );
        
        m_viewFrame = ( FrameLayout ) findViewById( R.id.mycontent );
        m_viewFrame.addView( m_viewRequest );
        m_viewFrame.addView( m_viewProduct );
        
        m_viewProduct.setVisibility( View.GONE );
        
        m_btnRecevieDate		= ( Button ) m_viewRequest.findViewById( R.id.dateReceive );
        m_txtClientName			= ( TextView ) m_viewRequest.findViewById( R.id.clientName );
        m_inpTradePoint			= ( EditText ) m_viewRequest.findViewById( R.id.tradePoint );
        m_radDocType			= ( RadioGroup ) m_viewRequest.findViewById( R.id.doc_type );
        m_inpDeliveryTime1From	= ( EditText ) m_viewRequest.findViewById( R.id.delivery_time_1_from );
        m_inpDeliveryTime1To	= ( EditText ) m_viewRequest.findViewById( R.id.delivery_time_1_to );
        m_inpDeliveryTime2From	= ( EditText ) m_viewRequest.findViewById( R.id.delivery_time_2_from );
        m_inpDeliveryTime2To	= ( EditText ) m_viewRequest.findViewById( R.id.delivery_time_2_to );
        m_cbMoneyMustBe			= ( CheckBox ) m_viewRequest.findViewById( R.id.cb_money_must_be );
        m_cbMoneySimple			= ( CheckBox ) m_viewRequest.findViewById( R.id.cb_money_simple );
        m_cbCertificate			= ( CheckBox ) m_viewRequest.findViewById( R.id.cb_certificate );
        m_cbSticker				= ( CheckBox ) m_viewRequest.findViewById( R.id.cb_sticker );
        m_rbInvoice				= ( RadioButton ) m_viewRequest.findViewById( R.id.invoice );
        m_rbBill				= ( RadioButton ) m_viewRequest.findViewById( R.id.waybill );
        m_txtTotal				= ( TextView ) m_viewProduct.findViewById( R.id.total );
        m_txtNds				= ( TextView ) m_viewProduct.findViewById( R.id.nds );

        m_szCurrentTab = "request";
        
        m_tabParam = actionBar.newTab( );
        m_tabParam.setText( "Параметры" );
        m_tabParam.setTabListener( this );
        m_tabParam.setTag( "request" );
        actionBar.addTab( m_tabParam );
        
        m_tabProduct = actionBar.newTab( );
        m_tabProduct.setText( "Товары" );
        m_tabProduct.setTabListener( this );
        m_tabProduct.setTag( "product" );
        actionBar.addTab( m_tabProduct );
        
        m_btnDelete		= ( Button ) m_viewProduct.findViewById( R.id.btnDelete );
        m_llProducts	= ( LinearLayout ) m_viewProduct.findViewById( R.id.products );
        
        Calendar calendar = Calendar.getInstance( );
        calendar.add( Calendar.DAY_OF_MONTH, 1 );
        m_szDateReceive = String.format( "%02d.%02d.%04d", calendar.get( Calendar.DAY_OF_MONTH ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.YEAR ) );
        m_btnRecevieDate.setText( m_szDateReceive );
        
        m_dlgProgress = new ProgressDialog( this );
        m_dlgProgress.setTitle( "Сохранение" );
        m_dlgProgress.setProgress( ProgressDialog.STYLE_HORIZONTAL );
        m_dlgProgress.setCancelable( false );
        
        Restore( );
    }
    
    private void Restore( ) {
    	RestoreFormFromObject( );
        
        if ( m_szLastTab == "product" ) {
            m_tabProduct.select( );
        }
    }
    
    @Override
    protected void onSaveInstanceState( Bundle outState ) {
        super.onSaveInstanceState( outState );
        m_szLastTab = ( String ) getActionBar( ).getSelectedTab( ).getTag( );
        SaveFormToObject( );
    }
    
    @Override
    protected void onRestoreInstanceState( Bundle savedInstanceState ) {
        super.onRestoreInstanceState( savedInstanceState );
        Restore( );
    }

    @Override
    public boolean onCreateOptionsMenu( Menu menu ) {
        // Inflate the menu; this adds items to the action bar if it is present.
        getMenuInflater( ).inflate( R.menu.activity_request, menu );
        
        return true;
    }

    @Override
    public boolean onOptionsItemSelected( MenuItem item ) {
        switch ( item.getItemId( ) ) {
        case android.R.id.home:
            NavUtils.navigateUpFromSameTask( this );
            return true;
            
        case R.id.menu_save:
            onSave( );
            return true;
            
        case R.id.menu_request_list:
        	StartRequestList( );
        	return true;
        	
        case R.id.menu_sync:
        	StartSync( );
        	return true;
        }
        return super.onOptionsItemSelected( item );
    }
    
    private void onSave( ) {
    	String error = ValidateForm( );
    	if ( error == null ) {
	    	m_dlgProgress.setIndeterminate( true );
	    	m_dlgProgress.setMessage( "Подготовка данных к отправке." );
	    	m_dlgProgress.show( );
	        ( new SaveTask( SaveRequestToLocalDatabase( ) ) ).execute( getFormParams( ) );
    	} else {
    		MessageBox.show( this, error, "Ошибка" );
    	}
    } // void onSave
    
    /**
     * Проверка данных формы заявки
     */
    private String ValidateForm( ) {
    	if ( m_objRequest.client_id <= 0 ) {
    		return "Укажите контрагента";
    	}
    	
    	if ( m_arrRow.size( ) <= 0 ) {
    		return "Заполните таблицу товаров";
    	}
    	
    	return null;
    }
    
    private void StartRequestList( ) {
    	startActivity( new Intent( this, RequestList.class ) );
    } // void StartRequestList
    
    private void StartSync( ) {
    	startActivity( new Intent( this, Sync.class ) );
    } // void StartSync
    
    public void onPickClient( View view ) {
        startActivityForResult( ( new Intent( this, ClientList.class ) ).putExtra( "modePick", true ), PICK_CLIENT );
    } // void onPickClient
    
    public void onPickDateReceive( View view ) {
        int year = 0, month = 0, day = 0;
        
        if ( ( m_szDateReceive.length( ) > 0 ) && m_szDateReceive.matches( "\\d{2}\\.\\d{2}\\.\\d{4}" ) ) {
        	String[ ] arrPart = m_szDateReceive.split( "\\." );
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
        
        DatePickerDialog dialog = new DatePickerDialog( this, this, year, month, day );
        dialog.show( );
    } // void onPickDateReceive
    
    @Override
    public void onDateSet( DatePicker view, int year, int monthOfYear, int dayOfMonth ) {
        m_szDateReceive = String.format( "%02d.%02d.%04d", dayOfMonth, monthOfYear + 1, year );
        m_btnRecevieDate.setText( m_szDateReceive );
    } // void onDateSet
    
    @Override
    public void onTabSelected( Tab tab, FragmentTransaction ft ) {
        String type = ( String ) tab.getTag( );
        if ( m_szCurrentTab != type ) {
            m_szCurrentTab = type;
            
            if ( type == "request" ) {
                m_viewRequest.setVisibility( View.VISIBLE );
                m_viewProduct.setVisibility( View.GONE );
            } else if ( type == "product" ) {
                m_viewRequest.setVisibility( View.GONE );
                m_viewProduct.setVisibility( View.VISIBLE );
            }
        }
    }
    
    @Override
    public void onTabReselected( Tab tab, FragmentTransaction ft ) {
    }
    
    @Override
    public void onTabUnselected( Tab tab, FragmentTransaction ft ) {
    }
    
    public void onProductAdd( View view ) {
        startActivityForResult( ( new Intent( this, ProductList.class ) ).putExtra( "modePick", true ), PICK_PRODUCT );
    } // void onProductAdd
    
    public void onProductDelete( View view ) {
        CProductRow row = null;
        int count = 0;
        for( int i = 0; i < m_arrRow.size( ); ++i ) {
            row = m_arrRow.get( i );
            if ( row.cb.isChecked( ) ) {
                ++count;
                m_llProducts.removeView( row.view );
                m_objRequest.products.remove( row.index );
                m_arrRow.remove( i );
            }
        }
        
        if ( count > 0 ) {
            for( int i = 0; i < m_arrRow.size( ); ++i ) {
                m_arrRow.get( i ).index = i;
            }
        }
        
        CalculateTotal( );
    } // void onProductDelete
    
    @Override
    protected void onActivityResult( int requestCode, int resultCode, Intent data ) {
        if ( resultCode != RESULT_OK ) {
            return;
        }
        
        if ( requestCode == PICK_CLIENT ) {
            Bundle extras = data.getExtras( );
            
            m_objRequest.client_id = extras.getInt( "clientId" );
            String clientName = extras.getString( "clientName" );
            m_txtClientName.setText( clientName );
            
            m_objRequest.client.id   = m_objRequest.client_id;
            m_objRequest.client.name = clientName;
            m_objRequest.client.price = extras.getInt( "clientPrice" );
            
            CalculateTotal( );
        } else if ( requestCode == PICK_PRODUCT ) {
            Bundle extras = data.getExtras( );
            
            addProductRow( extras.getInt( "productId" ), extras.getString( "productName" ), extras.getInt( "productUnit" ), extras.getFloat( "productPrice" ) );
            CalculateTotal( );
        }
    }
    
    private void CalculateTotal( ) {
    	if ( ( m_objRequest.client_id > 0 ) && ( m_arrRow.size( ) > 0 ) ) {
    		float		fTotal		= 0.0f,
    					fTotalNds	= 0.0f,
    					fPrice		= 0.0f,
    					fNds		= 0.0f;
    		CProductRow	row			= null;
    		Cursor		cursor		= null;
    		
    		for( int i = 0; i < m_arrRow.size( ); ++i ) {
    			row = m_arrRow.get( i );
    			fPrice = row.product.price;
    			fNds = 0.0f;
    			cursor = Database.m_objHelper.GetPrice( row.product.id, m_objRequest.client.price );
    			if ( cursor.getCount( ) > 0 ) {
            		cursor.moveToFirst( );
            		
            		fPrice	= cursor.getFloat( cursor.getColumnIndex( "product_price_price" ) );
            		fNds	= cursor.getFloat( cursor.getColumnIndex( "product_price_nds" ) );
            	}
    			
    			fPrice *= row.rproduct.amount;
    			
    			fTotal += fPrice;
    			fTotalNds += fPrice + ( fPrice * fNds ) / 100.0f;
    		}
    		
    		m_txtTotal.setText( String.format( "%.2f р.", fTotal ) );
    		m_txtNds.setText( String.format( "%.2f р.", fTotalNds ) );
        } else {
        	m_txtTotal.setText( "" );
    		m_txtNds.setText( "" );
        }
    } // void CalculateTotal
    
    private class CProductRow {
        public int      index  = 0;
        public CheckBox cb     = null;
        public TextView txt    = null;
        public TextView unit   = null;
        public EditText	inp    = null;
        public CNumberInput inputHandler = null;
        public CRequestProduct rproduct = null;
        public CProduct product = null;
        public View view = null;
    };
    
    private void addProductRow( int id, String name, int unit, float price ) {
    	LayoutInflater inflater = getLayoutInflater( );
    	CProductRow row = new CProductRow( );
        row.view = inflater.inflate( R.layout.request_product_item, null );
        
        row.cb = ( CheckBox ) row.view.findViewById( R.id.cb );
        row.cb.setOnClickListener( m_objCheck );
        
        row.txt = ( TextView ) row.view.findViewById( R.id.name );
        row.txt.setText( name );
        
        row.inp = ( EditText ) row.view.findViewById( R.id.np );
        row.inp.setText( "" );
        
        row.unit = ( TextView ) row.view.findViewById( R.id.unit );
        if ( unit == ProductUnit.KG.ordinal( ) ) {
        	row.unit.setText( "кг." );
        } else if ( unit == ProductUnit.PIECE.ordinal( ) ) {
        	row.unit.setText( "шт." );
        	row.inp.setInputType( InputType.TYPE_CLASS_NUMBER );
        }
        
        row.rproduct = new CRequestProduct( );
        row.rproduct.product_id = id;
        row.rproduct.amount     = 0;
        
        row.product = new CProduct( );
        row.product.id   = id;
        row.product.name = name;
        row.product.unit = unit;
        row.product.price = price;
        
        row.index = m_arrRow.size( );
        
        row.inputHandler = new CNumberInput( row );
        row.inp.addTextChangedListener( row.inputHandler );
        
        m_arrRow.add( row );
        m_llProducts.addView( row.view, 0 );
        m_objRequest.products.add( row.rproduct );
        
        row.inp.requestFocus( );
    } // void AddProductRow
    
    private void addProductRow( CProductRow row ) {
        LayoutInflater inflater = getLayoutInflater( );
        row.view = inflater.inflate( R.layout.request_product_item, null );
        
        row.cb = ( CheckBox ) row.view.findViewById( R.id.cb );
        row.cb.setOnClickListener( m_objCheck );
        
        row.txt = ( TextView ) row.view.findViewById( R.id.name );
        row.txt.setText( row.product.name );
        
        row.inp = ( EditText ) row.view.findViewById( R.id.np );
        row.inp.setText( String.format( "%.02f", row.rproduct.amount ) );
        
        row.unit = ( TextView ) row.view.findViewById( R.id.unit );
        if ( row.product.unit == ProductUnit.KG.ordinal( ) ) {
        	row.unit.setText( "кг." );
        } else if ( row.product.unit == ProductUnit.PIECE.ordinal( ) ) {
        	row.unit.setText( "шт." );
        	row.inp.setInputType( InputType.TYPE_CLASS_NUMBER );
        }
        
        row.inputHandler = new CNumberInput( row );
        row.inp.addTextChangedListener( row.inputHandler );
        
        m_llProducts.addView( row.view, 0 );
    } // void addProductRow
    
    private class CNumberInput implements TextWatcher {
    	private CProductRow row = null;
    	
    	public CNumberInput( CProductRow row ) {
    		this.row = row;
    	} // CNumberInput
    	
    	@Override
        public void afterTextChanged( Editable s ) {
    		String value = s.toString( );
    		if ( value.length( ) > 0 ) {
    			row.rproduct.amount = Float.parseFloat( value );
    		}
    		
    		Request.this.CalculateTotal( );
        } // void afterTextChanged
        
        @Override
        public void beforeTextChanged( CharSequence s, int start, int count, int after ) {
        } // void beforeTextChanged
        
        @Override
        public void onTextChanged( CharSequence s, int start, int before, int count ) {
        } // void onTextChanged
    };
    
    private class CheckProduct implements OnClickListener {
        @Override
        public void onClick( View v ) {
            int count = 0;
            for( int i = 0; i < m_arrRow.size( ); ++i ) {
                if ( m_arrRow.get( i ).cb.isChecked( ) ) {
                    ++count;
                }
            }
            
            m_btnDelete.setEnabled( count > 0 );
        }
    }; // class CheckProduct
    
    private void SaveFormToObject( ) {
        m_objRequest.type = ( m_radDocType.getCheckedRadioButtonId( ) == R.id.invoice ) ? RequestType.INVOICE.ordinal( ) : RequestType.BILL.ordinal( );
        
        String value = m_inpDeliveryTime1From.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            m_objRequest.time1_from = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime1To.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            m_objRequest.time1_to = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime2From.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            m_objRequest.time2_from = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime2To.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            m_objRequest.time2_to = Integer.parseInt( value );
        }
        
        m_objRequest.flag_money_must_be = m_cbMoneyMustBe.isChecked( ) ? 1 : 0;
        m_objRequest.flag_money_simple  = m_cbMoneySimple.isChecked( ) ? 1 : 0;
        m_objRequest.flag_certificate   = m_cbCertificate.isChecked( ) ? 1 : 0;
        m_objRequest.flag_sticker       = m_cbSticker.isChecked( ) ? 1 : 0;
        m_objRequest.receive_date       = m_szDateReceive;
        m_objRequest.trade_point        = m_inpTradePoint.getText( ).toString( );
    } // void SaveFormToObject
    
    private void RestoreFormFromObject( ) {
        if ( m_objRequest.type == RequestType.INVOICE.ordinal( ) ) {
            m_rbInvoice.setChecked( true );
        } else {
            m_rbBill.setChecked( true );
        }
        
        if ( m_objRequest.time1_from > 0 ) {
            m_inpDeliveryTime1From.setText( String.format( "%d", m_objRequest.time1_from ) );
        }
        if ( m_objRequest.time1_to > 0 ) {
            m_inpDeliveryTime1To.setText( String.format( "%d", m_objRequest.time1_to ) );
        }
        if ( m_objRequest.time2_from > 0 ) {
            m_inpDeliveryTime2From.setText( String.format( "%d", m_objRequest.time2_from ) );
        }
        if ( m_objRequest.time2_to > 0 ) {
            m_inpDeliveryTime2To.setText( String.format( "%d", m_objRequest.time2_to ) );
        }
        
        if ( m_objRequest.flag_money_must_be == 1 ) {
            m_cbMoneyMustBe.setChecked( true );
        }
        if ( m_objRequest.flag_money_simple == 1 ) {
            m_cbMoneySimple.setChecked( true );
        }
        if ( m_objRequest.flag_certificate == 1 ) {
            m_cbCertificate.setChecked( true );
        }
        if ( m_objRequest.flag_sticker == 1 ) {
            m_cbSticker.setChecked( true );
        }
        
        if ( m_objRequest.receive_date.length( ) == 0 ) {
            Calendar calendar = Calendar.getInstance( );
            m_objRequest.receive_date = String.format( "%02d.%02d.%04d", calendar.get( Calendar.DAY_OF_MONTH ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.YEAR ) );
        }
        
        m_szDateReceive = m_objRequest.receive_date;
        m_btnRecevieDate.setText( m_szDateReceive );
        m_inpTradePoint.setText( m_objRequest.trade_point );
        m_txtClientName.setText( m_objRequest.client.name );
        
        for( int i = 0; i < m_arrRow.size( ); ++i ) {
            addProductRow( m_arrRow.get( i ) );
        }
        
        CalculateTotal( );
    } // void RestoreFormFromObject
    
    private class SaveResult {
    	static final int ERROR_NOTHING	= 0;
        static final int ERROR_OK		= 1;
        static final int ERROR_IO		= 2;
        static final int ERROR_NETWORK	= 3;
        static final int ERROR_SERVER	= 4;
        
        public int		code	= ERROR_NOTHING;
        public String	message	= "";
        
        public SaveResult( int code, String message ) {
            this.code		= code;
            this.message	= message;
        }
    }; // class SaveResult
    
    private String getFormParams( ) {
        String ret = "";
        
        ret += "request_client_id=" + m_objRequest.client_id;
        ret += "&request_manager_id=" + MISTradeApplication.m_objManager.id;
        ret += "&request_type=" + String.format( "%d", ( ( m_radDocType.getCheckedRadioButtonId( ) == R.id.invoice ) ? RequestType.INVOICE.ordinal( ) : RequestType.BILL.ordinal( ) ) );
        ret += "&request_time1_from=" + m_inpDeliveryTime1From.getText( ).toString( );
        ret += "&request_time1_to=" + m_inpDeliveryTime1To.getText( ).toString( );
        ret += "&request_time2_from=" + m_inpDeliveryTime2From.getText( ).toString( );
        ret += "&request_time2_to=" + m_inpDeliveryTime2To.getText( ).toString( );
        ret += "&request_flag_money_must_be=" + ( m_cbMoneyMustBe.isChecked( ) ? "1" : "0" );
        ret += "&request_flag_money_simple=" + ( m_cbMoneySimple.isChecked( ) ? "1" : "0" );
        ret += "&request_flag_certificate=" + ( m_cbCertificate.isChecked( ) ? "1" : "0" );
        ret += "&request_flag_sticker=" + ( m_cbSticker.isChecked( ) ? "1" : "0" );
        ret += "&request_receive_date=" + Util.convertDateRUtoISO( m_szDateReceive );
        
        try {
            ret += "&request_trade_point=" + URLEncoder.encode( m_inpTradePoint.getText( ).toString( ), "UTF-8" );
        }
        catch ( UnsupportedEncodingException e ) {
            e.printStackTrace( );
        }
        
        if ( m_arrRow.size( ) > 0 ) {
        	CProductRow row = null;
        	for( int i = 0; i < m_arrRow.size( ); ++i ) {
        		row = m_arrRow.get( i );
        		ret += "&request_products[" + i + "][request_product_product_id]=" + row.rproduct.product_id + "&request_products[" + i + "][request_product_amount]=" + row.rproduct.amount;
        	}
        }
        
        return ret;
    } // String getFormParams
    
    private class SaveTask extends AsyncTask< String, Integer, Object > {
    	private long	m_iRequestId	= 0;
    	private String	m_szData		= "";
    	
    	public SaveTask( long iRequestId ) {
    		m_iRequestId = iRequestId;
    	}
    	
        @Override
        protected Object doInBackground( String... params ) {
        	m_szData = params[ 0 ];
        	
            if ( ( ( MISTradeApplication ) getApplication( ) ).isNetworkAvailable( ) == false ) {
                return new SaveResult( SaveResult.ERROR_NETWORK, "Отсутствует сетевое соединение" );
            } else {
            	URL url = null;
            	HttpURLConnection connection = null;
            	
                try {
                    String p = params[ 0 ];
                    
                    url = new URL( "http://localhost/mobile_system/data/save_request/" ); // @todo вынести в общий сервис взаимодействия со шлюзом
                    connection = ( HttpURLConnection ) url.openConnection( );
                    connection.setDoOutput( true );
                    connection.setDoInput( true );
                    connection.setRequestMethod( "POST" );
                    connection.setInstanceFollowRedirects( false );
                    connection.setUseCaches( false );
                    connection.setDefaultUseCaches( false );
                    connection.setRequestProperty( "Content-Type", "application/x-www-form-urlencoded" );
                    connection.setFixedLengthStreamingMode( p.getBytes( ).length );
                    connection.setConnectTimeout( 30000 );
                    connection.setReadTimeout( 30000 );
                    
                    OutputStreamWriter stream = new OutputStreamWriter( connection.getOutputStream( ), "UTF-8" );
                    
                    publishProgress( 0, 0 );
                    
                    char[ ] buff	= new char[ 32 ];
                    int iOffset		= 0,
                    	iBytes		= p.length( ),
                    	iBuffSize	= 32,
                    	iStart		= 0,
                    	iEnd		= 0,
                    	iWriteSize	= 0;
                    
                    publishProgress( 0, 1, iBytes );
                    
                    while( iOffset < iBytes ) {
                    	iStart		= iOffset;
                    	iEnd		= iOffset + iBuffSize;
                    	iWriteSize	= iBuffSize;
                    	
                    	if ( ( iOffset + iBuffSize ) > iBytes ) {
                    		iEnd = iBytes;
                    		iWriteSize = iBytes - iOffset;
                    	}
                    	
                    	p.getChars( iStart, iEnd, buff, 0 );
                    	stream.write( buff, 0, iWriteSize );
                    	publishProgress( 1, iOffset );
                    	iOffset += iBuffSize;
                    }
                    
                    stream.flush( );
                    stream.close( );
                    
                    int statusCode;
                    try {
                        statusCode = connection.getResponseCode( );
                    } catch( IOException ex ) {
                        statusCode = connection.getResponseCode( );
                    }
                    
                    BufferedReader reader = new BufferedReader( new InputStreamReader( connection.getInputStream( ) ) );
                    String line = reader.readLine( );
                    
                    if ( ( line == null ) || !line.equals( "0" ) ) {
                    	SaveResult result = new SaveResult( SaveResult.ERROR_SERVER, "unknown" );
                    	
                    	if ( line != null ) {
                    		int iLen = line.length( );
                    		
                    		if ( iLen > 2 ) {
                    			result.message = line.substring( 2 );
                    		}
                    	}
                    	
                    	return result;
                    }
                }
                catch ( IOException e ) {
                    e.printStackTrace( );
                    
                    return new SaveResult( SaveResult.ERROR_IO, "Ошибка сетевого соединения" );
                }
                finally {
                	if ( connection != null ) {
                		connection.disconnect( );
                	}
                }
            }
            
            return new SaveResult( SaveResult.ERROR_OK, "" );
        }
        
        @Override
        protected void onPostExecute( Object result ) {
            super.onPostExecute( result );
            
            if ( m_dlgProgress != null ) {
            	m_dlgProgress.dismiss( );
            }
            
            if ( result instanceof SaveResult ) {
                SaveResult error = ( SaveResult ) result;
                if ( ( error.code == SaveResult.ERROR_NETWORK ) || ( error.code == SaveResult.ERROR_IO ) || ( error.code == SaveResult.ERROR_SERVER ) ) {
                	MessageBox.show( Request.this, error.message + "\nЗаявка будет сохранена в локальной базе.", "Ошибка сети" );
                    SaveRequestToExport( m_iRequestId, m_szData );
                } else {
                	MessageBox.show( Request.this, "Заявка сохранена." );
                }
            }
            
            ResetForm( );
        }
        
        @Override
        protected void onProgressUpdate( Integer... values ) {
        	super.onProgressUpdate( values );
        	
        	if ( values[ 0 ] == 0 ) {
        		if ( values[ 1 ] == 0 ) {
        			m_dlgProgress.setMessage( "Отправка данных." );
                    m_dlgProgress.setIndeterminate( false );
        		} else if ( values[ 1 ] == 1 ) {
        			m_dlgProgress.setMax( values[ 2 ] );
                    m_dlgProgress.setProgress( 0 );
        		}
        	} else if ( values[ 0 ] == 1 ) {
        		m_dlgProgress.setProgress( values[ 1 ] );
        	}
        }
        
    }; // class SaveTask
    
    private long SaveRequestToLocalDatabase( ) {
    	CRequest objRequest = new CRequest( );
    	
    	objRequest.client_id = m_objRequest.client_id;
    	objRequest.manager_id = MISTradeApplication.m_objManager.id;
    	objRequest.type = ( m_radDocType.getCheckedRadioButtonId( ) == R.id.invoice ) ? RequestType.INVOICE.ordinal( ) : RequestType.BILL.ordinal( );
        
        String value = m_inpDeliveryTime1From.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            objRequest.time1_from = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime1To.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            objRequest.time1_to = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime2From.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            objRequest.time2_from = Integer.parseInt( value );
        }
        
        value = m_inpDeliveryTime2To.getText( ).toString( );
        if ( value.length( ) > 0 ) {
            objRequest.time2_to = Integer.parseInt( value );
        }
        
        objRequest.flag_money_must_be = m_cbMoneyMustBe.isChecked( ) ? 1 : 0;
        objRequest.flag_money_simple  = m_cbMoneySimple.isChecked( ) ? 1 : 0;
        objRequest.flag_certificate   = m_cbCertificate.isChecked( ) ? 1 : 0;
        objRequest.flag_sticker       = m_cbSticker.isChecked( ) ? 1 : 0;
        objRequest.receive_date       = m_szDateReceive;
        objRequest.trade_point        = m_inpTradePoint.getText( ).toString( );
        
        if ( m_arrRow.size( ) > 0 ) {
        	CProductRow row = null;
        	CRequestProduct objRequestProduct = null;
        	for( int i = 0; i < m_arrRow.size( ); ++i ) {
        		row = m_arrRow.get( i );
        		objRequestProduct = new CRequestProduct( );
        		objRequestProduct.id			= row.rproduct.id;
        		objRequestProduct.request_id	= row.rproduct.request_id;
        		objRequestProduct.product_id	= row.rproduct.product_id;
        		objRequestProduct.code			= row.rproduct.code;
        		objRequestProduct.amount		= row.rproduct.amount;
        		
        		objRequest.products.add( objRequestProduct );
        	}
        }
        
        return Database.m_objHelper.SaveRequestLocal( objRequest );
    } // long SaveRequestToLocalDatabase
    
    private void SaveRequestToExport( long iRequestId, String szData ) {
    	Database.m_objHelper.AddExport( "ud_request", iRequestId, Database.EXPORT_TYPE_REQUEST, szData );
    } // void SaveRequestToExport
    
    private void ResetForm( ) {
    	m_arrRow        = new ArrayList< CProductRow >( );
        m_objRequest    = new CRequest( );
    	
    	Calendar calendar = Calendar.getInstance( );
        calendar.add( Calendar.DAY_OF_MONTH, 1 );
        m_szDateReceive = String.format( "%02d.%02d.%04d", calendar.get( Calendar.DAY_OF_MONTH ), calendar.get( Calendar.MONTH ) + 1, calendar.get( Calendar.YEAR ) );
        m_btnRecevieDate.setText( m_szDateReceive );
        
        m_txtClientName.setText( "" );
        m_inpTradePoint.setText( "" );
        m_rbBill.setChecked( true );
        m_inpDeliveryTime1From.setText( "" );
        m_inpDeliveryTime1To.setText( "" );
        m_inpDeliveryTime2From.setText( "" );
        m_inpDeliveryTime2To.setText( "" );
        m_cbMoneyMustBe.setChecked( false );
        m_cbMoneySimple.setChecked( false );
        m_cbCertificate.setChecked( false );
        m_cbSticker.setChecked( false );
        m_llProducts.removeAllViews( );
        m_txtNds.setText( "" );
        m_txtTotal.setText( "" );
    } // void ResetForm
}
