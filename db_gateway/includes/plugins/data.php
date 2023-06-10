<?php
	/**
	 * Модуль данных
	 */

	require_once( 'data.client.php'				);	// клиенты (контрагенты)
	require_once( 'data.category.php'			);	// категории
	require_once( 'data.manager.php'			);	// менеджеры
	require_once( 'data.product.php'			);	// товары
	require_once( 'data.request.php'			);	// заявки
	require_once( 'data.request_product.php'	);	// товары в заявке
	require_once( 'data.trade_point.php'		);	// торговая точка
	require_once( 'data.version.php'			);  // версия
	require_once( 'data.export.php'				);  // экспорт
	require_once( 'data.import.php'				);  // импорт
	require_once( 'data.new_request.php'		);  // новые заявки
	require_once( 'data.product_price.php'		);	// цены товаров по прайсам
	
	function logError( $errno, $errstr, $errfile, $errline, $errcontext ) {
		/*ob_start( );
		var_dump( $errno, $errstr, $errfile, $errline, $errcontext );
		$r = ob_get_clean( );*/
		
		file_put_contents( '../../exchange/error.log', $errfile.' '.$errline."\n", FILE_APPEND );
		return true;
	}
	
	/**
	 *	Перехватчик для модуля Data
	 */
	class CHModData extends CHandler {
		private $hCommon = NULL;
	
		/**
		 * 	Инициализация обработчиков
		 */
		public function Init( ) {
			global $objCMS;
			$arrIni = array( 'database' => $objCMS->database );
			$this->hCommon = new CFlexHandler( );
			$this->hCommon->Create( $arrIni );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_client', FHOV_OBJECT => 'CClient' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_category', FHOV_OBJECT => 'CCategory' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_product', FHOV_OBJECT => 'CProduct' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_request', FHOV_OBJECT => 'CRequest' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_request_product', FHOV_OBJECT => 'CRequestProduct' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_trade_point', FHOV_OBJECT => 'CTradePoint' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_version', FHOV_OBJECT => 'CDataVersion' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_new_request', FHOV_OBJECT => 'CNewRequest', FHOV_FORCETABLE => true ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_new_request_product', FHOV_OBJECT => 'CRequestProduct', FHOV_FORCETABLE => true ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_product_price', FHOV_OBJECT => 'CProductPrice' ) );
		} // funciton InitObjectHandler
		
		public function Test( $szQuery ) {
			return ( bool ) preg_match( '/^\/data\//', $szQuery );
		} // function Test
		
		public function Process( $szQuery ) {
			$this->Init( );
			
			ini_set( 'date.timezone', 'Asia/Vladivostok' );
			
			if ( preg_match( '/^\/data\/list-tables\//', $szQuery ) ) {
				header( 'Content-Type: text/plain; charset=UTF-8' );
				$this->ListTables( );
			} elseif ( preg_match( '/^\/data\/save_request\//', $szQuery ) ) {
			    $this->SaveRequest( );
			} elseif ( preg_match( '/^\/data\/export-android\//', $szQuery ) ) {
			    $this->ExportAndroid( );
			} elseif ( preg_match( '/^\/data\/import-1c\//', $szQuery ) ) {
			    $this->Import1C( );
			} elseif ( preg_match( '/^\/data\/get-full-database\//', $szQuery ) ) {
			    $this->GetFullDatabase( );
			} elseif ( preg_match( '/^\/data\/export-android-product\//', $szQuery ) ) {
			    $this->ExportAndroidProduct( );
			} elseif ( preg_match( '/^\/data\/get-product-database\//', $szQuery ) ) {
			    $this->GetProductDatabase( );
			} elseif ( preg_match( '/^\/data\/export-1c\//', $szQuery ) ) {
			    $this->Export1C( );
			} elseif ( preg_match( '/^\/data\/list-dbf\//', $szQuery ) ) {
				$this->ListDbf( );
			} elseif ( preg_match( '/^\/data\/get-version\//', $szQuery ) ) {
				$this->GetVersion( );
			} elseif ( preg_match( '/^\/data\/list-new-request\//', $szQuery ) ) {
			    $this->ListNewRequest( );
			} elseif ( preg_match( '/^\/data\/sync\//', $szQuery ) ) {
			    $this->Sync( );
			} elseif ( preg_match( '/^\/data\/dev\//', $szQuery ) ) {
				$this->Dev( );
			} else {
			    $tmp = $this->hCommon->GetObject( array(
                    FHOV_WHERE     => '`request_creation_date` >= \'2013-11-13 00:00:00\'',//AND `request_state`='.( CNewRequest::STATE_NEW ),
    		        FHOV_TABLE     => 'ud_new_request',
    		        FHOV_OBJECT    => 'CNewRequest',
    		        FHOV_INDEXATTR => 'id',
    		        FHOV_ORDER	=> '`request_creation_date` DESC',
    				FHOV_JOIN	=> array(
    					array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager', FHOV_WHERE => 'ud_manager.manager_id=ud_new_request.request_manager_id' ),
    					array( FHOV_TABLE => 'ud_client', FHOV_OBJECT => 'CClient', FHOV_WHERE => 'ud_client.client_id=ud_new_request.request_client_id' )
    				)
    		    ) );
    		    
			    $arrRequest = $arrId = array( );
		        
		        $tmp = $tmp->GetResult( );
		        foreach( $tmp as $row ) {
					$objRequest = $row[ 'CNewRequest' ];
					$objRequest->client = $row[ 'CClient' ];
					$objRequest->manager = $row[ 'CManager' ];
					$arrId[ $objRequest->id ] = $objRequest->id;
					$arrRequest[ $objRequest->id ] = $objRequest;
				}
				
		        $tmp = $this->hCommon->GetObject( array(
		            FHOV_WHERE  => '`request_product_request_id` IN('.join( ',', $arrId ).')',
		            FHOV_TABLE  => 'ud_new_request_product',
		            FHOV_OBJECT => 'CRequestProduct'
		        ) );
		        
    		    if ( $tmp->HasError( ) ) {
    		        ShowVarD( $tmp->GetError( ) );
    		    }
		        if ( $tmp->HasResult( ) ) {
		            $arrRequestProduct = $tmp->GetResult( );
		            
		            foreach( $arrRequestProduct as $objRequestProduct ) {
		                if ( isset( $arrRequest[ $objRequestProduct->request_id ] ) ) {
		                    $arrRequest[ $objRequestProduct->request_id ]->products[ ] = $objRequestProduct;
		                }
		            }
		        }
		        
		        echo '<table border="1" cellpadding="5" cellspacing="0">';
		        foreach( $arrRequest as $objRequest ) {
                    foreach( $objRequest->products as $objProduct ) {
                        echo '<tr><td>'.$objRequest->manager->code.'</td>'.
                            '<td>'.$objRequest->client->code.'</td>'.
                            '<td>'.sprintf( '%012d', $objRequest->id ).'</td>'.
                            '<td>'.$objProduct->code.'</td>'.
                            '<td>'.$objProduct->amount.'</td>'.
                            '<td>'.preg_replace( '/^\d{2}(\d{2})-(\d{2})-(\d{2})$/', '$3.$2.$1', $objRequest->receive_date ).'</td>'.
                            '<td>&nbsp;'.$objRequest->trade_point.'</td>'.
                            '<td>'.$objRequest->time1_from.'</td>'.
                            '<td>'.$objRequest->time1_to.'</td>'.
                            '<td>'.$objRequest->time2_from.'</td>'.
                            '<td>'.$objRequest->time2_to.'</td>'.
                            '<td>'.$objRequest->flag_money_must_be.'</td>'.
                            '<td>'.$objRequest->flag_money_simple.'</td>'.
                            '<td>'.$objRequest->flag_certificate.'</td>'.
                            '<td>'.$objRequest->flag_sticker.'</td></tr>';
                    }
		        }
		        echo '</table>';
    		    
    		    ShowVarD( $arrRequest );
				
				/*$tmp = $this->hCommon->GetObject( array(
					FHOV_TABLE => 'ud_admin',
					FHOV_OBJECT => 'CAdmin'
				) );
				
				$obj = new CAdmin( );
				$obj->Create( array(
					'admin_login' => 'admin',
					'admin_password' => 'slfc932094x',
					'admin_rank' => UR_ADMIN
				) );
				
				//$this->hCommon->AddObject( array( $obj ), array( FHOV_TABLE => 'ud_admin' ) );
				
				ShowVar( $tmp->GetResult( ), $obj, hash( 'sha1', 'slfc932094x' ) );
				exit;*/
				
    			global $objCMS, $objCurrent, $iCurrentSysRank, $mxdCurrentData, $szCurrentMode, $arrErrors, $mxdLinks;
    			// выставляем текущий модуль
    			$objCurrent = 'Data';
    			$szCurrentMode = 'List';
    			$arrErrors = array( );
    			
    		    $szFolder = $objCMS->GetPath( 'root_application' );
    			if ( $szFolder !== false && file_exists( $szFolder.'/index.php' ) ) {
    				include_once( $szFolder.'/index.php' );
    			}
			}
			
			return true;
		} // function Process
		
		private function ListNewRequest( ) {
		    $tmp = $this->hCommon->GetObject( array( FHOV_TABLE => 'ud_new_request', FHOV_OBJECT => 'CNewRequest', FHOV_ORDER => 'request_id DESC' ) );
		    $arrRequest = $tmp->GetResult( );
		    $arrNew = $arrOld = array( );
		    
		    foreach( $arrRequest as $objRequest ) {
		        if ( $objRequest->state == CNewRequest::STATE_NEW ) {
		            $arrNew[] = $objRequest;
		        } else {
		            $arrOld[] = $objRequest;
		        }
		    }
		    
		    /*$szPath = '../../exchange/import/20131008135119/REQ280913-231026.dbf';
		    $db = dbase_open( $szPath, 0 );
		    if ( $db ) {
		        $n = dbase_numrecords( $db );
		        
		        ShowVar( dbase_get_header_info( $db ), $n );
			
    			for( $i = 1; $i <= $n; ++$i ) {
    				$row = dbase_get_record_with_names( $db, $i );
    				
        			foreach( $row as $j => $v ) {
        				if ( is_string( $v ) ) {
        					$row[ $j ] = trim( iconv( 'CP866', 'UTF-8', $v ) );
        				}
        			}
        			
        			ShowVar( $row );
    			}
				
		        dbase_close( $db );
		    }*/
		    
		    ShowVarD( $arrNew, $arrOld );
		} // function ListNewRequest
		
		private function ExportAndroid( ) {
    		$szName = 'full_'.date( 'YmdHis' ).'.db';
        	$szFileName = '../../exchange/export/android/'.$szName;
        	
        	if ( file_exists( $szFileName ) ) {
        		unlink( $szFileName );
        		clearstatcache( );
        	}
        	
		    ini_set( 'memory_limit', '24M' );
		    
		    $szAndroidLastVersion = $this->GetAndroidLastVersion( 'full' );
		    $szCurrentVersion = $this->GetDatabaseVersion( CDataVersion::TYPE_FULL );
		    
		    if ( $szAndroidLastVersion != $szCurrentVersion ) {
		    	$export = new CDataExportAndroid( $this->hCommon, $szFileName, true );
            	$export->Export( );
		    }
		} // function ExportAndroid
		
		private function ExportAndroidProduct( ) {
		    $szName = 'product_'.date( 'YmdHis' ).'.db';
        	$szFileName = '../../exchange/export/android/'.$szName;
        	
        	if ( file_exists( $szFileName ) ) {
        		unlink( $szFileName );
        		clearstatcache( );
        	}
        	
		    ini_set( 'memory_limit', '24M' );
		    
		    $szAndroidLastVersion = $this->GetAndroidLastVersion( 'product' );
		    $szCurrentVersion = $this->GetDatabaseVersion( CDataVersion::TYPE_PRODUCT );
		    
		    if ( $szAndroidLastVersion != $szCurrentVersion ) {
		    	$export = new CDataExportAndroid( $this->hCommon, $szFileName, true );
            	$export->ExportProduct( );
		    }
		} // function ExportAndroidProduct
		
		private function ListTables( ) {
		    global $objCMS;
		    
		    /*$szPath = '../../exchange/import/20131008135119/REQ280913-231026.dbf';
		    $db = dbase_open( $szPath, 0 );
		    if ( $db ) {
		        $n = dbase_numrecords( $db );
		        
		        ShowVar( dbase_get_header_info( $db ) );
			
    			for( $i = 1; $i <= $n; ++$i ) {
    				$row = dbase_get_record_with_names( $db, $i );
    				
        			foreach( $row as $j => $v ) {
        				if ( is_string( $v ) ) {
        					$row[ $j ] = trim( iconv( 'CP866', 'UTF-8', $v ) );
        				}
        			}
        			
        			ShowVar( $row );
    			}
				
		        dbase_close( $db );
		    }
		    
		    exit;*/
		    
		    /*$arrTable = array(
		    	'ud_client', 'ud_category', 'ud_manager', 'ud_product', 'ud_request', 'ud_request_product',
		    	'ud_trade_point', 'ud_version', 'ud_new_request', 'ud_new_request_product'
		    );
		    foreach( $arrTable as $szTable ) {
		        $objCMS->database->Query( 'TRUNCATE TABLE `'.$szTable.'`' );
		    }*/
		    
		    //$objCMS->database->Query( 'TRUNCATE TABLE `ud_new_request`' );
		    //$objCMS->database->Query( 'TRUNCATE TABLE `ud_new_request_product`' );
		    
			$arrTable = array(
				'CAdmin', 'CClient', 'CCategory', 'CManager', 'CProduct', 'CRequest', 'CRequestProduct', 'CTradePoint',
			    'CDataVersion', 'CProductPrice'
			);
			
			foreach( $arrTable as $szName ) {
				$obj = new $szName( );
				$tmp = $obj->GetSQLCreate( );
				echo $tmp->GetResult( 'query' ).";\n";
				$szTable = $tmp->GetResult( 'table' );
				$tmp = $this->hCommon->CountObject( array( FHOV_TABLE => $tmp->GetResult( 'table' ) ) );
				echo $tmp->GetResult( 'count' )."\n";
				$tmp = $objCMS->database->Query( "SHOW CREATE TABLE `".$szTable."`" );
				$tmp = current( $tmp->GetResult( ) );
				echo $tmp[ "Create Table" ]."\n\n";
			}
			
			$tmp = $objCMS->database->Query( "SHOW CREATE TABLE `ud_new_request`" );
			$tmp = current( $tmp->GetResult( ) );
			echo $tmp[ "Create Table" ]."\n\n";
			
			$tmp = $objCMS->database->Query( "SHOW CREATE TABLE `ud_new_request_product`" );
			$tmp = current( $tmp->GetResult( ) );
			echo $tmp[ "Create Table" ]."\n\n";
		} // function ListTables
		
		private function SaveRequest( ) {
		    $arrData = $_POST;
		    $objRequest = new CNewRequest( );
		    $arrData[ 'request_creation_date' ] = date( 'Y-m-d H:i:s' );
		    $arrData[ 'request_state' ] = CNewRequest::STATE_NEW;
		    $objRequest->Create( $arrData, FLEX_FILTER_FORM );
		    
		    //$objRequest->manager_id = 16;
		    
		    $client = '';
		    
		    $tmp = $this->hCommon->GetObject( array(
		        FHOV_WHERE  => 'client_id='.$objRequest->client_id,
		        FHOV_TABLE	=> 'ud_client',
				FHOV_OBJECT	=> 'CClient',
				FHOV_LIMIT  => '1'
			) );
			if ( $tmp->HasResult( ) ) {
			    $tmp = current( $tmp->getResult( ) );
			    $client = $tmp->name;
			    $objRequest->client_code = $tmp->code;
			}
			
			$products = '<table><tr><th>Наименование</th><th>Цена</th><th>Количество</th></tr>';
			
			foreach( $objRequest->products as $objProduct ) {
			    $tmp = $this->hCommon->GetObject( array(
			        FHOV_WHERE  => 'product_id='.$objProduct->product_id,
			        FHOV_TABLE  => 'ud_product',
			        FHOV_OBJECT => 'CProduct',
			        FHOV_LIMIT  => '1'
			    ) );
			    if ( $tmp->HasResult( ) ) {
			        $tmp = current( $tmp->GetResult( ) );
			        $products .= '<tr><td>'.$tmp->name.'</td><td>'.$tmp->price.'</td><td>'.( ( int ) $v->amount ).'</td></tr>';
			        
			        $objProduct->code = $tmp->code;
			    }
			}
			
			$products .= '</table>';
		    
		    $addition = '';
		    $tmp = array( );
		    
		    if ( $objRequest->flag_money_must_be == 1 ) {
		        $tmp[ ] = 'Деньги обязательно';
		    }
		    if ( $objRequest->flag_money_simple == 1 ) {
		        $tmp[ ] = 'Просто деньги';
		    }
		    if ( $objRequest->flag_certificate == 1 ) {
		        $tmp[ ] = 'Сертификат';
		    }
		    if ( $objRequest->flag_sticker == 1 ) {
		        $tmp[ ] = 'Наклейки';
		    }
		    if ( !empty( $tmp ) ) {
		        $addition = join( ', ', $tmp );
		    }
		    
		    $szReceiveDate = '';
		    $tmp = preg_split( '/-/', $objRequest->receive_date );
		    if ( count( $tmp ) == 3 ) {
		        $szReceiveDate = join( '.', array( $tmp[ 2 ], $tmp[ 1 ], $tmp[ 0 ] ) );
		    }
		    
		    $szFromTo = '';
		    $tmp = array( );
		    
		    if ( $objRequest->time1_from || $objRequest->time1_to ) {
		        $tmp[ ] = 'c '.$objRequest->time1_from.' по '.$objRequest->time1_to;
		    }
		    
		    if ( $objRequest->time2_from || $objRequest->time2_to ) {
		        $tmp[ ] = 'c '.$objRequest->time2_from.' по '.$objRequest->time2_to;
		    }
		    
		    $szFromTo = join( ' и ', $tmp );
		    
		    $html = '<h1>Заявка</h1>'.
		        '<h2>Параметры</h2>'.
		        '<p><b>Контрагент:</b> '.$client.'</p>'.
		        '<p><b>Тип:</b> '.( $objRequest->type == CRequest::TYPE_INVOICE ? 'Счет-фактура' : 'Накладная' ).'</p>'.
		        '<p><b>Торговая точка:</b> '.$objRequest->trade_point.'</p>'.
		        '<p><b>Дата доставки:</b> '.$szReceiveDate.'</p>'.
		        '<p><b>Время доставки:</b> '.$szFromTo.'</p>'.
		        '<p><b>Дополнительно:</b> '.$addition.'</p>'.
		        '<h2>Номенклатура</h2>'.$products;
		    
		    $tmp = $this->hCommon->AddObject( array( $objRequest ), array( FHOV_TABLE => 'ud_new_request' ) );
		    if ( $tmp->HasResult( ) ) {
		        $objRequest->id = $tmp->GetResult( 'insert_id' );
		        foreach( $objRequest->products as $obj ) {
		            $obj->request_id = $objRequest->id;
		        }
		        
		        $this->hCommon->AddObject( $objRequest->products, array( FHOV_TABLE => 'ud_new_request_product' ) );
		    }
		    
		    if ( $tmp->HasError( ) ) {
		        $objError = current( $tmp->GetError( ) );
		        echo '1 '.$objError->GetCode( ).' '.$objError->GetText( );
		    } else {
		        echo '0';
		    }
		} // function SaveRequest
		
		private function Import1C( ) {
		    ini_set( 'memory_limit', '24M' );
		    
		    $prepare = true;
		    $folder  = '../../import/';
		    $folder_backup = '../../exchange/import/';
		    
		    if ( isset( $_GET[ 'from_backup' ] ) && is_string( $_GET[ 'from_backup' ] ) ) {
		    	if ( !file_exists( $folder_backup.'/'.$_GET[ 'from_backup' ] ) ) {
		    		echo 'Not Found';
		    		exit;
		    	}
		    	
		        $prepare = false;
		        $folder_backup .= '/'.$_GET[ 'from_backup' ];
		        $folder = $folder_backup;
		    }
	
		    $import = new CDataImport1C( $this->hCommon, $folder, $prepare, $folder_backup );
            $import->Import( );
		} // function Import1C
		
		private function GetFullDatabase( ) {
		    $szFolderExport = '../../exchange/export/android/';
		    $files = scandir( $szFolderExport );
		    $dbs = array( );
		    
		    foreach( $files as $file ) {
		        if ( preg_match( '/^full.*\.db$/', $file ) ) {
		            $dbs[ ] = $file;
		        }
		    }
		    
		    if ( !empty( $dbs ) ) {
		        $szName = end( $dbs );
		        $szFileName = $szFolderExport.'/'.$szName;
    			$iSize = filesize( $szFileName );
    			
    			header( 'Content-Description: File Transfer' );
    			header( 'Content-Type: application/octet-stream' );
    			header( 'Content-Disposition: attachment; filename="'.$szName.'"' );
    			header( 'Content-Transfer-Encoding: binary' );
    			header( 'Expires: 0' );
    			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    			header( 'Pragma: public' );
    			header( 'Content-Length: '.$iSize );
    			@ob_clean( );
    			@flush( );
    			@readfile( $szFileName );
		    }
		    
		    exit;
		} // function GetFullDatabase
		
		private function GetProductDatabase( ) {
		    $szFolderExport = '../../exchange/export/android/';
		    $files = scandir( $szFolderExport );
		    $dbs = array( );
		    
		    foreach( $files as $file ) {
		        if ( preg_match( '/^product.*\.db$/', $file ) ) {
		            $dbs[ ] = $file;
		        }
		    }
		    
		    if ( !empty( $dbs ) ) {
		        $szName = end( $dbs );
		        $szFileName = $szFolderExport.'/'.$szName;
    			$iSize = filesize( $szFileName );
    			
    			header( 'Content-Description: File Transfer' );
    			header( 'Content-Type: application/octet-stream' );
    			header( 'Content-Disposition: attachment; filename="'.$szName.'"' );
    			header( 'Content-Transfer-Encoding: binary' );
    			header( 'Expires: 0' );
    			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    			header( 'Pragma: public' );
    			header( 'Content-Length: '.$iSize );
    			@ob_clean( );
    			@flush( );
    			@readfile( $szFileName );
		    }
		    
		    exit;
		} // function GetProductDatabase
		
		private function Export1C( ) {
		    $export = new CDataExport1C( $this->hCommon, '../../export/' );
		    $export->Export( );
		} // function Export1C
		
		private function ListDbf( ) {
			$szDir = '../../import';
			
			$isExport =  isset( $_GET[ 'is_export' ] );
			if ( $isExport ) {
			    $szDir = '../../export';
			} elseif ( isset( $_GET[ 'backup' ] ) ) {
				$szDir = '../../exchange/import/'.$_GET[ 'backup' ];
			}
			
			$tmp = array( );
			
			if ( $isExport ) {
				$tmp[ ] = 'is_export=1';
			}
			
			if ( isset( $_GET[ 'backup' ] ) ) {
				$tmp[ ] = 'backup='.urlencode( $_GET[ 'backup' ] );
			}
			
			$url = join( '&amp;', $tmp );
			
			$tmp = scandir( $szDir );
			foreach( $tmp as $name ) {
				if ( is_file( $szDir.'/'.$name ) && preg_match( '/\.dbf$/', $name ) ) {
					echo '<div><a href="?'.$url.'&amp;name='.urldecode( $name ).'">'.$name.'</a></div>';
				}
			}
			
			$szName = isset( $_GET[ 'name' ] ) && is_string( $_GET[ 'name' ] ) ? $_GET[ 'name' ] : '';
			$szPath = $szDir.'/'.$szName;
			
			if ( file_exists( $szPath ) && is_file( $szPath ) ) {
				$db = dbase_open( $szPath, 0 );
				if ( $db ) {
					$n = dbase_numrecords( $db );
					echo '<h4>'.$szName.'</h4><div>row count: <b>'.$n.'</b></div>';
					
					$tmp = dbase_get_header_info( $db );
					echo '<table border="1" cellpadding="3" cellspacing="1">';
					foreach( $tmp as $field ) {
						echo '<tr><td>'.$field[ 'name' ].'</td><td>'.$field[ 'type' ].'</td><td>'.$field[ 'length' ].( $field[ 'precision' ] > 0 ? ', '.$field[ 'precision' ] : '' ).'</td></tr>';
					}
					echo '</table><table border="1" cellpadding="3" cellspacing="1"><tr>';
					foreach( $tmp as $field ) {
						echo '<th>'.$field[ 'name' ].'</th>';
					}
					echo '</tr>';
					
					for( $i = 1; $i <= $n; ++$i ) {
						echo '<tr>';
						$row = dbase_get_record_with_names( $db, $i );
						foreach( $row as $index => $v ) {
							if ( $index != 'deleted' ) {
								echo '<td>';
								if ( is_string( $v ) ) {
									$v = trim( iconv( 'CP866', 'UTF-8', $v ) );
									if ( empty( $v ) ) {
										$v = '&nbsp;';
									}
									echo $v;
								} else {
									echo $v;
								}
								echo '</td>';
							}
						}
						echo '</tr>';
					}
					
					echo '</table>'; 
					
					dbase_close( $db );
				}
			}
		} // function ListDbf
		
		private function GetVersion( ) {
			$szFullVersion = $szProductVersion = '0';
			
			$tmp = $this->hCommon->GetObject( array(
                FHOV_WHERE  => '`version_type`='.CDataVersion::TYPE_FULL,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
				$szFullVersion = current( $tmp->GetResult( ) )->number;
			}
			
			$tmp = $this->hCommon->GetObject( array(
                FHOV_WHERE  => '`version_type`='.CDataVersion::TYPE_PRODUCT,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
				$szProductVersion = current( $tmp->GetResult( ) )->number;
			}
			
			echo $szFullVersion."\n".$szProductVersion."\n";
		} // function GetVersion
		
		private function GetDatabaseVersion( $type ) {
			$tmp = $this->hCommon->GetObject( array(
                FHOV_WHERE  => '`version_type`='.$type,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
				return current( $tmp->GetResult( ) )->number;
			}
			
			return NULL;
		}
		
		private function Sync( ) {
		    set_time_limit( 0 );
			ignore_user_abort( true );
			ob_implicit_flush( true );
		    ini_set( 'memory_limit', '24M' );
		    
		    $this->Import1C( );
		    $this->Export1C( );
		    $this->ExportAndroid( );
		    $this->ExportAndroidProduct( );
		} // function Sync
		
		private function Dev( ) {
			// фикс заявки после выгрузки
			/*$tmp = $this->hCommon->GetObject( array(
				FHOV_WHERE => 'request_id=101',
				FHOV_TABLE => 'ud_new_request',
				FHOV_OBJECT => 'CNewRequest',
				FHOV_INDEXATTR => 'id'
			) )->GetResult( 101 );
			
			$tmp->state = CNewRequest::STATE_NEW;
			$this->hCommon->UpdObject( array( $tmp ), array( FHOV_TABLE => 'ud_new_request', FHOV_INDEXATTR => 'id' ) );
			
			ShowVar( $tmp );*/
			
			// создание логинов и паролей менеджеров
			/*$tmp = $this->hCommon->GetObject( array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager' ) )->GetResult( );
			
			foreach( $tmp as $manager ) {
				$manager->login = $manager->code;
				$manager->password = hash( 'sha1', $manager->login.$manager->login );
			}
			
			$this->hCommon->UpdObject( $tmp, array( FHOV_TABLE => 'ud_manager', FHOV_INDEXATTR => 'id' ) );
			
			ShowVar( $tmp );*/
			
			/*$tmp = $this->hCommon->GetObject( array(
				FHOV_WHERE => 'request_type='.( CRequest::TYPE_INVOICE ),
				FHOV_TABLE => 'ud_new_request',
				FHOV_OBJECT => 'CNewRequest'
			) );
			
			ShowVar( $tmp->GetResult( ) );*/
			
			// принудительный экспорт и обновление версии
			/*$arrInput = array( );
            $objVersion = new CDataVersion( );
            $objVersion->Create( array(
                'version_datetime' => date( 'Y-m-d H:i:s' ),
                'version_number'   => date( 'YmdHis' ),
                'version_type'     => CDataVersion::TYPE_FULL
            ) );
            $arrInput[ ] = $objVersion;
            
            $objVersion = new CDataVersion( );
            $objVersion->Create( array(
                'version_datetime' => date( 'Y-m-d H:i:s' ),
                'version_number'   => date( 'YmdHis' ),
                'version_type'     => CDataVersion::TYPE_PRODUCT
            ) );
            $arrInput[ ] = $objVersion;
            
            $this->hCommon->AddObject( $arrInput, array( FHOV_TABLE => 'ud_version' ) );
            
			$szName = 'full_'.date( 'YmdHis' ).'.db';
        	$szFileName = '../../exchange/export/android/'.$szName;
        	
        	if ( file_exists( $szFileName ) ) {
        		unlink( $szFileName );
        		clearstatcache( );
        	}
        	
		    ini_set( 'memory_limit', '24M' );
		    
		    $export = new CDataExportAndroid( $this->hCommon, $szFileName, true );
            $export->Export( );
            
		 	$szName = 'product_'.date( 'YmdHis' ).'.db';
        	$szFileName = '../../exchange/export/android/'.$szName;
        	
        	if ( file_exists( $szFileName ) ) {
        		unlink( $szFileName );
        		clearstatcache( );
        	}
        	
		    $export = new CDataExportAndroid( $this->hCommon, $szFileName, true );
            $export->ExportProduct( );*/
			
			// проверка версий и новых функций
			/*$szLastVersion = $this->GetAndroidLastVersion( 'full' );
			
			ShowVar( $szLastVersion );
			
			$szFullVersion = $szProductVersion = '0';
			
			$tmp = $this->hCommon->GetObject( array(
                FHOV_WHERE  => '`version_type`='.CDataVersion::TYPE_FULL,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
				$szFullVersion = current( $tmp->GetResult( ) )->number;
			}
			
			$tmp = $this->hCommon->GetObject( array(
                FHOV_WHERE  => '`version_type`='.CDataVersion::TYPE_PRODUCT,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
				$szProductVersion = current( $tmp->GetResult( ) )->number;
			}
			
			ShowVar( $szFullVersion, $szProductVersion );*/
			
			exit;
		} // function Dev
		
		private function GetAndroidLastFile( $type ) {
			$szFolderExport = '../../exchange/export/android/';
		    $files = scandir( $szFolderExport );
		    $dbs = array( );
		    
		    foreach( $files as $file ) {
		        if ( preg_match( '/^'.$type.'.*\.db$/', $file ) ) {
		            $dbs[ ] = $file;
		        }
		    }
		    
		    if ( !empty( $dbs ) ) {
		        $szName = end( $dbs );
		        
		        return $szFolderExport.$szName;
		    }
		    
		    return '';
		}
		
		private function GetAndroidLastVersion( $type ) {
			$szFilename = $this->GetAndroidLastFile( $type );
			
			$db = new PDO( 'sqlite:'.$szFilename );
			
			return $db->query( 'SELECT version_number FROM ud_version ORDER BY version_number DESC LIMIT 1' )->fetchColumn( );
		}
		
	} // class CHModData
	
