<?php
    class CDataImport1C {
        private	$handler		= NULL,
				$folder			= '',
				$folder_backup	= '',
				$prepare		= true; // производить ли подготовку (создание бэкап папки, копирование файлов в нее, удаление файлов из исходной папки)
                   
        private static $arrImport = array(
			'CAT' => array( 'title' => 'Категории', 'table' => 'ud_category', 'import' => 'ImportCategory' ),
			'MEN' => array( 'title' => 'Менеджеры', 'table' => 'ud_manager', 'import' => 'ImportManager' ),
			'KON' => array( 'title' => 'Контрагенты', 'table' => array( 'ud_client', 'ud_trade_point' ), 'import' => 'ImportClient' ),
			'NOM' => array( 'title' => 'Товары', 'table' => 'ud_product', 'import' => 'ImportProduct' ),
			'REQ' => array( 'title' => 'Заявки', 'table' => array( 'ud_request', 'ud_request_product' ), 'import' => 'ImportRequest' ),
        	'PRI' => array( 'title' => 'Цены', 'table' => 'ud_product_price', 'import' => 'ImportProductPrice' )
		);
                   
        public function __construct( $handler, $folder, $prepare = true, $folder_backup = null ) {
            $this->handler			= $handler;
            $this->folder			= $folder;
            $this->prepare			= $prepare;
            $this->folder_backup	= is_string( $folder_backup ) ? $folder_backup : $folder;
        } // function __construct
        
        public function Import( ) {
            set_time_limit( 0 );
			ignore_user_abort( true );
			ob_implicit_flush( true );
			
			$szDir = $this->Prepare( );
			$tmp = scandir( $szDir );
			
			echo "<div>Import</div>\n";
			
			$cat = $product = '';
			$updateVersion = false;
			
			foreach( $tmp as $file ) {
				$path = $szDir.'/'.$file;
				
				if ( is_file( $path ) && preg_match( '/\.dbf$/', $path ) ) {
					echo $file."\n";
					
					foreach( self::$arrImport as $prefix => $config ) {
						if ( preg_match( '/^'.$prefix.'/', $file ) ) {
							$updateVersion = true;
    						if ( $prefix == 'CAT' ) {
    					        $cat = $path;
    					    } else if ( $prefix == 'NOM' ) {
    					        $product = $path;
    					    }
    					    
						    $log = fopen( $szDir.'/'.preg_replace( '/\.dbf/', '.html', $file ), 'wb' );
						    fwrite( $log, "<html>
                				<head>
                				<style type=\"text/css\">
                					table { border: 0; border-collapse: collapse; width: 100%; }
                					table th, td { padding: 3px; border: 1px solid black; };
                					.new { background-color: #afa; }
                					.update { background-color: #ffa; }
                					.delete { background: #faa; }
                				</style>
                				</head>
                				<body>\n" );
							$this->ProcImport( $path, $config, $log );
							fwrite( $log, '</body></html>' );
							fclose( $log );
							break;
						}
					}
					
					echo "<div>&nbsp;</div>\n";
				}
			}
			
			if ( !empty( $cat ) && !empty( $product ) ) {
			    $this->ProcessCategoryProduct( $szDir, $cat, $product );
			}
			if ( $updateVersion ) {
				$this->UpdateVersion( );
			}
			$this->TableListing( );
        } // function Import
        
        private function UpdateVersion( ) {
            $arrInput = array( );
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
            
            $this->handler->AddObject( $arrInput, array( FHOV_TABLE => 'ud_version' ) );
        } // function UpdateVersion
        
        private function Prepare( ) {
            if ( !$this->prepare ) {
                return $this->folder_backup;
            }
                
            $dir = $this->folder_backup.'/'.date('YmdHis');
            mkdir( $dir, 0755 );
            
            $tmp = scandir( $this->folder );
            
            echo 'Prepare. dir="'.$dir."\"\n";
            
            foreach( $tmp as $file ) {
				$path = $this->folder.'/'.$file;
				
				if ( is_file( $path ) && preg_match( '/\.dbf$/', $path ) ) {
					echo $file."\n";
					
					copy( $path, $dir.'/'.$file );
					unlink( $path );
				}
			}
			
			clearstatcache();
            
            return $dir;
        } // function Prepare
        
        private function ProcImport( $path, $config, $log ) {
			global $objCMS;
			
			$db = dbase_open( $path, 0 );
			if ( $db ) {
				if ( isset( $config[ 'import' ] ) ) {
					$callback = $config[ 'import' ];
					$this->$callback( $db, $log );
					echo "\n";
				}
			
				dbase_close( $db );
			} else {
				echo "failed\n";
			}
		} // function ProcImport
		
		private function TableListing( ) {
			global $objCMS;
			$tmp = $objCMS->database->Query( 'SHOW TABLES' );
			if ( $tmp->HasResult( ) ) {
				$arrTable = $tmp->GetResult( );
			
				foreach( $arrTable as $row ) {
					$name = current( $row );
					$tmp = $objCMS->database->Query( 'SELECT COUNT(*) c FROM `'.$name.'`' );
					if ( $tmp->HasResult( ) ) {
						$tmp = current( $tmp->GetResult( ) );
						$tmp = $tmp[ 'c' ];
						echo '<div>'.$name.' '.$tmp.'</div>';
					}
				}
			}
		} // function TableListing
		
		private function ListRows( $db ) {
			$n = dbase_numrecords( $db );
			$header = true;
		
			echo '<style type="text/css">table { border: 1px solid black; border-collapse: collapse; } th, td { border: 1px solid black; }</style><table>';
		
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				
				if ( $header ) {
					echo '<tr>';
					foreach( $row as $name => $value ) {
						echo '<th>'.$name.'</th>';
					}
					echo '</tr>';
					$header = false;
				}
				
				echo '<tr>';
				foreach( $row as $name => $value ) {
					echo '<td>'.iconv('CP866', 'UTF-8', $value).'</td>';
				}
				echo '</tr>';
			}
		
			echo '</table>total count: '.$n.'<br/>';
		} // function ListRows
		
		public function ImportCategory( $db, $log ) {
			$arrCategory = array( );
			$n = dbase_numrecords( $db );
			
		    $info = dbase_get_header_info( $db );
			foreach( $info as $attr ) {
			    fwrite( $log, '<p>'.$attr[ 'name' ].' '.$attr[ 'type' ]."</p>\n" );
			}
			
			fwrite( $log, "<table><tr>
				<th>CATCODE</th>
				<th>SUBCODE</th>
				<th>CATNAME</th>
				<th>SUBNAME</th>
			</tr>" );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				$class = '';
				$objCategory = new CCategory( );
				
				if ( $row[ 'SUBCODE' ] == '' ) { // старшая категория
					if ( !isset( $arrCategory[ $row[ 'CATCODE' ] ] ) ) { // действительно новая в наборе
						$tmp = $this->handler->GetObject( array(
							FHOV_WHERE => '`category_code`=\''.mysql_escape_string( $row[ 'CATCODE' ] ).'\'',
							FHOV_TABLE => 'ud_category',
							FHOV_OBJECT => 'CCategory',
							FHOV_INDEXATTR => 'code'
						) );
						if ( $tmp->HasResult( ) ) {
							$objCategory = $tmp->GetResult( $row[ 'CATCODE' ] );
							$objCategory->Create( array(
								'category_name' => $row[ 'CATNAME' ]
							) );
							$this->handler->UpdObject( array( $objCategory ), array( FHOV_TABLE => 'ud_category', FHOV_INDEXATTR => 'id' ) );
							$class = 'update';
						} else {
							$objCategory->Create( array(
								'category_code' => $row[ 'CATCODE' ],
								'category_name' => $row[ 'CATNAME' ]
							) );
							$tmp = $this->handler->AddObject( array( $objCategory ), array( FHOV_TABLE => 'ud_category' ) );
							if ( $tmp->HasResult( ) ) {
								$objCategory->Create( array( 'category_id' => $tmp->GetResult( 'insert_id' ) ) );
							}
							$class = 'new';
						}
						
						$arrCategory[ $row[ 'CATCODE' ] ] = $objCategory;
					}
				} else { // подкатегория, кэшируем их, чтобы сохранить иерархию
					$arrCategory[ $row[ 'SUBCODE' ] ] = $row;
				}
				
				fwrite( $log, '<tr class="'.$class.'"><td>'.$row[ 'CATCODE' ].'</td><td>'.$row[ 'SUBCODE' ].'</td><td>'.$row[ 'CATNAME' ].'</td><td>'.$row[ 'SUBNAME' ]."</td></tr>\n" );
				echo '<span>.</span>';
			}
			
			fwrite( $log, '</table>' );
			
			// обработка подкатегорий
			foreach( $arrCategory as $row ) {
				if ( is_array( $row ) && isset( $arrCategory[ $row[ 'CATCODE' ] ] ) ) {
					$objParentCategory = $arrCategory[ $row[ 'CATCODE' ] ];
					
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`category_code`=\''.mysql_escape_string( $row[ 'SUBCODE' ] ).'\'',
						FHOV_TABLE => 'ud_category',
						FHOV_OBJECT => 'CCategory',
						FHOV_INDEXATTR => 'code'
					) );
					if ( $tmp->HasResult( ) ) {
						$objCategory = $tmp->GetResult( $row[ 'SUBCODE' ] );
						$objCategory->Create( array(
							'category_parent_id' => $objParentCategory->id,
							'category_code' => $row[ 'SUBCODE' ],
							'category_name' => $row[ 'SUBNAME' ]
						) );
						$this->handler->UpdObject( array( $objCategory ), array( FHOV_TABLE => 'ud_category', FHOV_INDEXATTR => 'id' ) );
					} else {
						$objCategory = new CCategory( );
						$objCategory->Create( array(
							'category_parent_id' => $objParentCategory->id,
							'category_code' => $row[ 'SUBCODE' ],
							'category_name' => $row[ 'SUBNAME' ]
						) );
						$this->handler->AddObject( array( $objCategory ), array( FHOV_TABLE => 'ud_category' ) );
					}
				}
			}
			
			$tmp = $this->handler->CountObject( array( FHOV_TABLE => 'ud_category' ) );
			$tmp = $tmp->GetResult( 'count' );
			
			echo '<div>'.$tmp.'</div>';
		} // function ImportCategory
		
		public function ImportClient( $db, $log ) {
			$n = dbase_numrecords( $db );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				$tmp = $this->handler->GetObject( array(
					FHOV_WHERE => '`client_code`=\''.mysql_real_escape_string( $row[ 'KONTRCODE' ] ).'\'',
					FHOV_TABLE => 'ud_client',
					FHOV_OBJECT => 'CClient',
					FHOV_INDEXATTR => 'code'
				) );
				if ( $tmp->HasResult( ) ) {
					$objClient = $tmp->GetResult( $row[ 'KONTRCODE' ] );
					$objClient->Create( array(
						'client_manager_code' => $row[ 'MENCODE' ],
						'client_name' => $row[ 'KONTRNAME' ],
						'client_limit' => $row[ 'KONTRLIM' ],
						'client_phone' => $row[ 'KONTRPHONE' ],
						'client_addr' => $row[ 'KONTRADDR' ],
						'client_price' => $row[ 'KONTRPRICE' ]
					) );
					$this->handler->UpdObject( array( $objClient ), array( FHOV_TABLE => 'ud_client', FHOV_INDEXATTR => 'id' ) );
				} else {
					$objClient = new CClient( );
					$objClient->Create( array(
						'client_manager_code' => $row[ 'MENCODE' ],
						'client_code' => $row[ 'KONTRCODE' ],
						'client_name' => $row[ 'KONTRNAME' ],
						'client_limit' => $row[ 'KONTRLIM' ],
						'client_phone' => $row[ 'KONTRPHONE' ],
						'client_addr' => $row[ 'KONTRADDR' ],
						'client_price' => $row[ 'KONTRPRICE' ]
					) );
					$this->handler->AddObject( array( $objClient ), array( FHOV_TABLE => 'ud_client' ) );
				}
				
				echo '<span>.</span>';
			}
			
			$tmp = $this->handler->CountObject( array( FHOV_TABLE => 'ud_client' ) );
			$tmp = $tmp->GetResult( 'count' );
			
			echo '<div>'.$tmp.'</div>';
		} // function ImportClient
		
		public function ImportManager( $db, $log ) {
			$n = dbase_numrecords( $db );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				$tmp = $this->handler->GetObject( array(
					FHOV_WHERE => '`manager_name`=\''.mysql_real_escape_string( $row[ 'MENNAME' ] ).'\'',
					FHOV_TABLE => 'ud_manager',
					FHOV_OBJECT => 'CManager',
					FHOV_INDEXATTR => 'name'
				) );
				if ( $tmp->HasResult( ) ) {
					$objManager = $tmp->GetResult( $row[ 'MENNAME' ] );
					$objManager->Create( array(
						'manager_code' => $row[ 'MENCODE' ]
					) );
					$this->handler->UpdObject( array( $objManager ), array( FHOV_TABLE => 'ud_manager', FHOV_INDEXATTR => 'id' ) );
				} else {
					$objManager = new CManager( );
					$objManager->Create( array(
						'manager_code' => $row[ 'MENCODE' ],
						'manager_name' => $row[ 'MENNAME' ]
					) );
					$this->handler->AddObject( array( $objManager ), array( FHOV_TABLE => 'ud_manager' ) );
				}
				
				echo '<span>.</span>';
			}
			
			$tmp = $this->handler->CountObject( array( FHOV_TABLE => 'ud_manager' ) );
			$tmp = $tmp->GetResult( 'count' );
				
			echo '<div>'.$tmp.'</div>';
		} // function ImportManager
		
		public function ImportProduct( $db, $log ) {
			$arrCategory = array( );
			$n = dbase_numrecords( $db );
			
			$info = dbase_get_header_info( $db );
			foreach( $info as $attr ) {
			    fwrite( $log, '<p>'.$attr[ 'name' ].' '.$attr[ 'type' ]."</p>\n" );
			}
			
			fwrite( $log, "<table><tr>
				<th>CODE</th>
				<th>SUBCODE</th>
				<th>NAME</th>
				<th>PRICE</th>
				<th>SALDO</th>
				<th>ED</th>
			</tr>" );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				$iCategoryId = 0;
				$class = '';
				
				$unit = CProduct::UNIT_UNKNOWN;
				$tmp = $row[ 'ED' ];
				if ( $tmp == 'штука' ) {
					$unit = CProduct::UNIT_PIECE;
				} else if ( $tmp == 'килограмм' ) {
					$unit = CProduct::UNIT_KG;
				}
				
				if ( isset( $arrCategory[ $row[ 'SUBCODE' ] ] ) ) {
					$iCategoryId = $arrCategory[ $row[ 'SUBCODE' ] ]->id;
				} else {
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`category_code`=\''.mysql_real_escape_string( $row[ 'SUBCODE' ] ).'\'',
						FHOV_TABLE => 'ud_category',
						FHOV_OBJECT => 'CCategory',
						FHOV_INDEXATTR => 'code'
					) );
					if ( $tmp->HasResult( ) ) {
						$objCategory = $tmp->GetResult( $row[ 'SUBCODE' ] );
						$arrCategory[ $row[ 'SUBCODE' ] ] = $objCategory;
						$iCategoryId = $objCategory->id;
					}
				}
				
				$tmp = $this->handler->GetObject( array(
					FHOV_WHERE => '`product_code`=\''.mysql_real_escape_string( $row[ 'CODE' ] ).'\'',
					FHOV_TABLE => 'ud_product',
					FHOV_OBJECT => 'CProduct',
					FHOV_INDEXATTR => 'code'
				) );
				if ( $tmp->HasResult( ) ) {
					$objProduct = $tmp->GetResult( $row[ 'CODE' ] );
					$objProduct->Create( array(
						'product_category_id' => $iCategoryId,
						'product_category' => $row[ 'SUBCODE' ],
						'product_name' => $row[ 'NAME' ],
						'product_price' => floatval( $row[ 'PRICE' ] ),
						'product_saldo' => floatval( $row[ 'SALDO' ] ),
						'product_unit' => $unit
					) );
					$this->handler->UpdObject( array( $objProduct ), array( FHOV_TABLE => 'ud_product', FHOV_INDEXATTR => 'id' ) );
				} else {
					$objProduct = new CProduct( );
					$objProduct->Create( array(
						'product_category_id' => $iCategoryId,
						'product_code' => $row[ 'CODE' ],
						'product_category' => $row[ 'SUBCODE' ],
						'product_name' => $row[ 'NAME' ],
						'product_price' => floatval( $row[ 'PRICE' ] ),
						'product_saldo' => floatval( $row[ 'SALDO' ] ),
						'product_unit' => $unit
					) );
					$this->handler->AddObject( array( $objProduct ), array( FHOV_TABLE => 'ud_product' ) );
				}
				
				fwrite( $log, '<tr><td>'.$row[ 'CODE' ].'</td><td>'.$row[ 'SUBCODE' ].'</td><td>'.$row[ 'NAME' ].'</td>'.
				    '<td>'.$row[ 'PRICE' ].'</td><td>'.$row[ 'SALDO' ].'</td><td>'.$row[ 'ED' ].'</td></tr>' );
				echo '<span>.</span>';
			}
			
			fwrite( $log, '</table>' );
			
			$tmp = $this->handler->CountObject( array( FHOV_TABLE => 'ud_product' ) );
			$tmp = $tmp->GetResult( 'count' );
			
			echo '<div>'.$tmp.'</div>';
		} // function ImportProduct
		
		public function ImportRequest( $db, $log ) {
			$arrRequest = array( );
			$n = dbase_numrecords( $db );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				if ( isset( $arrRequest[ $row[ 'REQCODE' ] ] ) ) { // это уже дополнительные товары к заявке
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`request_product_code`=\''.mysql_real_escape_string( $row[ 'CODE' ] ).'\' AND '.
							'`request_product_request_id`='.$arrRequest[ $row[ 'REQCODE' ] ],
						FHOV_TABLE => 'ud_request_product',
						FHOV_OBJECT => 'CRequestProduct',
						FHOV_INDEXATTR => 'code'
					) );
					if ( $tmp->HasResult( ) ) {
						$objRequestProduct = $tmp->GetResult( $row[ 'CODE' ] );
						$objRequestProduct->Create( array(
							'request_product_request_id' => $arrRequest[ $row[ 'REQCODE' ] ],
							'request_product_amount' => floatval( $row[ 'AMOUNT' ] )
						) );
						$this->handler->UpdObject( array( $objRequestProduct ), array( FHOV_TABLE => 'ud_request_product', FHOV_INDEXATTR => 'id' ) );
					} else {
						$objRequestProduct = new CRequestProduct( );
						$objRequestProduct->Create( array(
							'request_product_request_id' => $arrRequest[ $row[ 'REQCODE' ] ],
							'request_product_code' => $row[ 'CODE' ],
							'request_product_amount' => floatval( $row[ 'AMOUNT' ] )
						) );
						$this->handler->AddObject( array( $objRequestProduct ), array( FHOV_TABLE => 'ud_request_product' ) );
					}
				} else {
					$iClientId = 0;
					$code = $row[ 'KONTRCODE' ];
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`client_code`=\''.mysql_real_escape_string( $code ).'\'',
						FHOV_INDEXATTR => 'code',
						FHOV_TABLE => 'ud_client',
						FHOV_OBJECT => 'CClient'
					) );
					if ( $tmp->HasResult( ) ) {
						$iClientId = $tmp->GetResult( $row[ 'KONTRCODE' ] )->id;
					}
					
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`request_code`=\''.mysql_real_escape_string( $row[ 'REQCODE' ] ).'\'',
						FHOV_TABLE => 'ud_request',
						FHOV_OBJECT => 'CRequest',
						FHOV_INDEXATTR => 'code'
					) );
					if ( $tmp->HasResult( ) ) {
						$objRequest = $tmp->GetResult( $row[ 'REQCODE' ] );
						$objRequest->Create( array(
							'request_client_id' => $iClientId,
							'request_client_code' => $row[ 'KONTRCODE' ],
							'request_type' => ( int ) $row[ 'REQTYPE' ],
							'request_receive_date' => preg_replace( '/^(\d{2})\.(\d{2})\.(\d{2})$/', '20$3-$2-$1', $row[ 'DATA' ] )
						) );
						$this->handler->UpdObject( array( $objRequest ), array( FHOV_TABLE => 'ud_request', FHOV_INDEXATTR => 'id' ) );
						$iRequestId = $objRequest->id;
					} else {
						$objRequest = new CRequest( );
						$objRequest->Create( array(
							'request_client_id' => $iClientId,
							'request_client_code' => $row[ 'KONTRCODE' ],
							'request_code' => $row[ 'REQCODE' ],
							'request_type' => ( int ) $row[ 'REQTYPE' ],
							'request_receive_date' => preg_replace( '/^(\d{2})\.(\d{2})\.(\d{2})$/', '20$3-$2-$1', $row[ 'DATA' ] )
						) );
						$tmp = $this->handler->AddObject( array( $objRequest ), array( FHOV_TABLE => 'ud_request' ) );
						$iRequestId = ( int ) $tmp->GetResult( 'insert_id' );
					}
					
					$arrRequest[ $row[ 'REQCODE' ] ] = $iRequestId;
					
					$tmp = $this->handler->GetObject( array(
						FHOV_WHERE => '`request_product_code`=\''.mysql_real_escape_string( $row[ 'CODE' ] ).'\' AND '.
							'`request_product_request_id`='.$iRequestId,
						FHOV_TABLE => 'ud_request_product',
						FHOV_OBJECT => 'CRequestProduct',
						FHOV_INDEXATTR => 'code'
					) );
					if ( $tmp->HasResult( ) ) {
						$objRequestProduct = $tmp->GetResult( $row[ 'CODE' ] );
						$objRequestProduct->Create( array(
							'request_product_request_id' => $iRequestId,
							'request_product_amount' => floatval( $row[ 'AMOUNT' ] )
						) );
						$this->handler->UpdObject( array( $objRequestProduct ), array( FHOV_TABLE => 'ud_request_product', FHOV_INDEXATTR => 'id' ) );
					} else {
						$objRequestProduct = new CRequestProduct( );
						$objRequestProduct->Create( array(
							'request_product_request_id' => $iRequestId,
							'request_product_code' => $row[ 'CODE' ],
							'request_product_amount' => floatval( $row[ 'AMOUNT' ] )
						) );
						$this->handler->AddObject( array( $objRequestProduct ), array( FHOV_TABLE => 'ud_request_product' ) );
					}
				}
				
				echo '<span>.</span>';
			}
			
			$tmp = $this->handler->CountObject( array( FHOV_TABLE => 'ud_request' ) );
			$tmp = $tmp->GetResult( 'count' );
				
			echo '<div>'.$tmp.'</div>';
		} // function ImportRequest
		
		private function ProcessCategoryProduct( $szDir, $szCategoryFilePath, $szProductFilePath ) {
		    $hFileOut = fopen( $szDir.'/category_and_product.html', 'wb' );
		    
		    fwrite( $hFileOut,
		    	"<html>
				<head>
				<style type=\"text/css\">
					table { border: 0; border-collapse: collapse; width: 100%; }
					table th, td { padding: 3px; border: 1px solid black; };
				</style>
				</head>
				<body>\n"
		    );
		    
		    $dbCategory = dbase_open( $szCategoryFilePath, 0 );
		    $dbProduct = dbase_open( $szProductFilePath, 0 );
		    
		    $iCategoryNum = dbase_numrecords( $dbCategory );
		    $iProductNum = dbase_numrecords( $dbProduct );
		    
		    for( $i = 1; $i <= $iCategoryNum; ++$i ) {
		        $rowCategory = dbase_get_record_with_names( $dbCategory, $i );
		        
		        $bIsSubcategory = true;
		        $szCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBCODE' ] ) );
		        $szName = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBNAME' ] ) );
		        
		        if ( empty( $szCode ) ) {
		            $bIsSubcategory = false;
		            $szCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'CATCODE' ] ) );
		            $szName = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'CATNAME' ] ) );
		        }
		        
		        fwrite( $hFileOut, '<h4>'.$szName.' ('.$szCode.')'.( $bIsSubcategory ? 'подкатегория' : '' ).'</h4><table><tr><th>Категория</th><th>Код</th><th>Наименование</th></tr>' );
		        
		        for( $j = 1; $j <= $iProductNum; ++$j ) {
		            $rowProduct = dbase_get_record_with_names( $dbProduct, $j );
		            $szCatCode = trim( iconv( 'CP866', 'UTF-8', $rowProduct[ 'SUBCODE' ] ) );
		            
		            if ( $szCode == $szCatCode ) {
		                $szProductCode = trim( iconv( 'CP866', 'UTF-8', $rowProduct[ 'CODE' ] ) );
		                $szProductName = trim( iconv( 'CP866', 'UTF-8', $rowProduct[ 'NAME' ] ) );
		                fwrite( $hFileOut, '<tr><td>'.$szCatCode.'</td><td>'.$szProductCode.'</td><td>'.$szProductName.'</td></tr>' );
		            }
		        }
		        
		        fwrite( $hFileOut, '</table>' );
		    }
		    
		    dbase_close( $dbCategory );
		    dbase_close( $dbProduct );
		    
		    fwrite( $hFileOut, '</body></html>' );
		    fclose( $hFileOut );
		    
		    $hFileOut = fopen( $szDir.'/category_and_product_db.html', 'wb' );
		    
		    fwrite( $hFileOut,
		    	"<html>
				<head>
				<style type=\"text/css\">
					table { border: 0; border-collapse: collapse; width: 100%; }
					table th, td { padding: 3px; border: 1px solid black; };
				</style>
				</head>
				<body>\n"
		    );
		    
		    $tmp = $this->handler->GetObject( array(
		        FHOV_TABLE => 'ud_category',
		        FHOV_OBJECT => 'CCategory'
		    ) );
		    if ( $tmp->HasResult( ) ) {
		        $arrCategory = $tmp->GetResult( );
		        
		        foreach( $arrCategory as $objCategory ) {
		            $bIsSubcategory = ( $objCategory->parent_id != 0 );
		            fwrite( $hFileOut, '<h4>'.$objCategory->name.' ('.$objCategory->id.' '.$objCategory->code.')'.( $bIsSubcategory ? 'подкатегория' : '' ).'</h4><table><tr><th>Категория</th><th>Код</th><th>Наименование</th></tr>' );
		            
		            $tmp = $this->handler->GetObject( array(
		                FHOV_WHERE  => '`product_category_id`='.$objCategory->id,
		                FHOV_TABLE  => 'ud_product',
		                FHOV_OBJECT => 'CProduct'
		            ) );
		            if ( $tmp->HasResult( ) ) {
		                $arrProduct = $tmp->GetResult( );
		                
		                foreach( $arrProduct as $objProduct ) {
		                    fwrite( $hFileOut, '<tr><td>'.$objProduct->category.'</td><td>'.$objProduct->code.'</td><td>'.$objProduct->name.'</td></tr>' );
		                }
		            }
    		        
    		        fwrite( $hFileOut, '</table>' );
		        }
		    }
		    
		    fwrite( $hFileOut, '</body></html>' );
		    fclose( $hFileOut );
		    
		    $hFileOut = fopen( $szDir.'/category_tree.html', 'wb' );
		    $dbCategory = dbase_open( $szCategoryFilePath, 0 );
		    
		    fwrite( $hFileOut,
		    	"<html>
				<head>
				<style type=\"text/css\">
					table { border: 0; border-collapse: collapse; width: 100%; }
					table th, td { padding: 3px; border: 1px solid black; };
				</style>
				</head>
				<body>\n"
		    );
		    
		    $arrCategory = array( );
		    
		    $iCategoryNum = dbase_numrecords( $dbCategory );
		    
		    for( $i = 1; $i <= $iCategoryNum; ++$i ) {
		        $rowCategory = dbase_get_record_with_names( $dbCategory, $i );
		        
		        $szCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBCODE' ] ) );
		        $szName = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBNAME' ] ) );
		        
		        if ( empty( $szCode ) ) {
		            $szCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'CATCODE' ] ) );
		            $szName = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'CATNAME' ] ) );
		            
		            $arrCategory[ $szCode ] = array( 'code' => $szCode, 'name' => $szName );
		        }
		    }
		    
		    foreach( $arrCategory as $code => $category ) {
    		    for( $i = 1; $i <= $iCategoryNum; ++$i ) {
    		        $rowCategory = dbase_get_record_with_names( $dbCategory, $i );
    		        
    		        $szCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBCODE' ] ) );
    		        $szName = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'SUBNAME' ] ) );
    		        $szCatCode = trim( iconv( 'CP866', 'UTF-8', $rowCategory[ 'CATCODE' ] ) );
    		        
        		    if ( empty( $szCode ) ) {
    		        } else if ( $szCatCode == $category[ 'code' ] ) {
    		            $arrCategory[ $code ][ 'subcategories' ][ ] = array( 'code' => $szCode, 'name' => $szName );
    		        }
    		    }
		    }
		    
		    ShowVar( $arrCategory );
		    
		    fwrite( $hFileOut, '<ul>' );
		    foreach( $arrCategory as $category ) {
		        fwrite( $hFileOut, '<li><p><b>'.$category[ 'code' ].' '.$category[ 'name' ].'</b></p>' );
		        
		        if ( isset( $category[ 'subcategories' ] ) ) {
		            fwrite( $hFileOut, '<ul>' );
		            foreach( $category[ 'subcategories' ] as $subcategory ) {
		                fwrite( $hFileOut, '<li>'.$subcategory[ 'code' ].' '.$subcategory[ 'name' ].'</li>' );
		            }
		            fwrite( $hFileOut, '</ul>' );
		        }
		        
		        fwrite( $hFileOut, '</li>' );
		    }
		    fwrite( $hFileOut, '</ul>' );
		    
		    fwrite( $hFileOut, '</body></html>' );
		    dbase_close( $dbCategory );
		    fclose( $hFileOut );
		    
		    $hFileOut = fopen( $szDir.'/category_tree_db.html', 'wb' );
		    
		    fwrite( $hFileOut,
		    	"<html>
				<head>
				<style type=\"text/css\">
					table { border: 0; border-collapse: collapse; width: 100%; }
					table th, td { padding: 3px; border: 1px solid black; };
				</style>
				</head>
				<body>\n"
		    );
		    
		    fwrite( $hFileOut, '<ul>' );
		    
		    $tmp = $this->handler->GetObject( array(
		        FHOV_WHERE  => '`category_parent_id`=0',
		        FHOV_TABLE  => 'ud_category',
		        FHOV_OBJECT => 'CCategory'
		    ) );
		    if ( $tmp->HasResult( ) ) {
		        $arrCategory = $tmp->GetResult( );
		        
    		    foreach( $arrCategory as $objCategory ) {
    		        fwrite( $hFileOut, '<li><p><b>'.$objCategory->id.' '.$objCategory->code.' '.$objCategory->name.'</b></p>' );
    		        
    		        $tmp = $this->handler->GetObject( array(
    		            FHOV_WHERE  => '`category_parent_id`='.$objCategory->id,
    		            FHOV_TABLE  => 'ud_category',
    		            FHOV_OBJECT => 'CCategory'
    		        ) );
    		        if ( $tmp->HasResult( ) ) {
    		            $arrSubcategory = $tmp->GetResult( );
    		            fwrite( $hFileOut, '<ul>' );
    		            foreach( $arrSubcategory as $subcategory ) {
    		                fwrite( $hFileOut, '<li>'.$subcategory->id.' '.$subcategory->code.' '.$subcategory->name.'</li>' );
    		            }
    		            fwrite( $hFileOut, '</ul>' );
    		        }
    		        
    		        fwrite( $hFileOut, '</li>' );
    		    }
		    }
		    
		    fwrite( $hFileOut, '</ul>' );
		    
		    fwrite( $hFileOut, '</body></html>' );
		    fclose( $hFileOut );
		} // function ProcessCategoryProduct
		
		public function ImportProductPrice( $db, $log ) {
			$n = dbase_numrecords( $db );
			
			for( $i = 1; $i <= $n; ++$i ) {
				$row = dbase_get_record_with_names( $db, $i );
				$this->PrepareRow( $row );
				
				$iProductId = 0;
				$tmp = $this->handler->GetObject( array(
					FHOV_WHERE => '`product_code`=\''.$row[ 'NOMCODE' ].'\'',
					FHOV_TABLE => 'ud_product',
					FHOV_OBJECT => 'CProduct'
				) );
				if ( $tmp->HasResult( ) ) {
					$objProduct = current( $tmp->GetResult( ) );
					$iProductId = $objProduct->id;
				}
				
				$tmp = $this->handler->GetObject( array(
					FHOV_WHERE => '`product_price_product_code`=\''.$row[ 'NOMCODE' ].'\' AND `product_price_category_code`=\''.mysql_real_escape_string( $row[ 'CATCODE' ] ).'\'',
					FHOV_TABLE => 'ud_product_price',
					FHOV_OBJECT => 'CProductPrice',
					FHOV_INDEXATTR => 'category_code',
					FHOV_LIMIT => '1'
				) );
				if ( $tmp->HasResult( ) ) {
					$objProductPrice = $tmp->GetResult( $row[ 'CATCODE' ] );
					$objProductPrice->Create( array(
						'product_price_product_id' => $iProductId,
						'product_price_product_code' => $row[ 'NOMCODE' ],
						'product_price_price' => $row[ 'PRICE' ],
						'product_price_nds' => $row[ 'NDS' ]
					) );
					$this->handler->UpdObject( array( $objProductPrice ), array( FHOV_TABLE => 'ud_product_price', FHOV_INDEXATTR => 'id' ) );
				} else {
					$objProductPrice = new CProductPrice( );
					$objProductPrice->Create( array(
						'product_price_category_code' => $row[ 'CATCODE' ],
						'product_price_product_id' => $iProductId,
						'product_price_product_code' => $row[ 'NOMCODE' ],
						'product_price_price' => $row[ 'PRICE' ],
						'product_price_nds' => $row[ 'NDS' ]
					) );
					$this->handler->AddObject( array( $objProductPrice ), array( FHOV_TABLE => 'ud_product_price' ) );
				}
			}
		} // function ImportProductPrice
		
		private function PrepareRow( &$row ) {
			foreach( $row as $i => $v ) {
				if ( is_string( $v ) ) {
					$row[ $i ] = trim( iconv( 'CP866', 'UTF-8', $v ) );
				}
			}
		} // function PrepareRow
        
    } // class CDataIMport1C
    
    