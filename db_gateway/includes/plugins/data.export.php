<?php
    class CDataExport {
        protected  $handler   = null,
                   $progress  = false,
                   $frameSize = 5000;
                   
        public function __construct( $handler, $progress = false, $frameSize = 5000 ) {
            $this->handler   = $handler;
            $this->progress  = $progress;
            $this->frameSize = $frameSize;
        } // function __construct
        
        protected function ShowMessage( $text ) {
            if ( $this->progress ) {
                echo $text;
            }
        } // function ShowMessage
        
    } // class CDataExport

    class CDataExportAndroid extends CDataExport {
        private    $filename  = ''; // путь файла SQLite
        
        public function __construct( $handler, $filename, $progress = false, $frameSize = 5000 ) {
            parent::__construct( $handler, $progress, $frameSize );
            
            $this->filename = $filename;
        } // function __construct
        
        public function Export( ) {
            $db = null;
            $arrQuery = array(
				/*'ud_admin' => "CREATE TABLE ud_admin (".
					"_id INTEGER PRIMARY KEY,".
					"admin_login VARCHAR(20),".
					"admin_password VARCHAR(128),".
					"admin_rank INTEGER".
				");",*/
	
				'ud_client' => "CREATE TABLE ud_client (".
					"_id INTEGER PRIMARY KEY,".
					"client_manager_id INTEGER,".
					"client_manager_code VARCHAR(254),".
					"client_code VARCHAR(254),".
					"client_name VARCHAR(254),".
            		"client_name_lower VARCHAR(254),".
					"client_limit FLOAT,".
					"client_phone VARCHAR(254),".
					"client_addr VARCHAR(254),".
            		"client_price INTEGER".
				");",
	
				'ud_category' => "CREATE TABLE ud_category (".
					"_id INTEGER PRIMARY KEY,".
					"category_parent_id INTEGER,".
					"category_code VARCHAR(254),".
					"category_name VARCHAR(254)".
				");",
	
				'ud_manager' => "CREATE TABLE ud_manager (".
					"_id INTEGER PRIMARY KEY,".
					"manager_code VARCHAR(254),".
					"manager_name VARCHAR(254),".
            		"manager_login VARCHAR(254),".
            		"manager_password VARCHAR(254)".
				");",
	
				'ud_request' => "CREATE TABLE ud_request (".
					"_id INTEGER PRIMARY KEY,".
					"request_client_id INTEGER,".
					"request_client_code VARCHAR(254),".
					"request_code VARCHAR(254),".
					"request_type INTEGER,".
					"request_creation_date DATETIME,".
					"request_receive_date DATE,".
					"request_trade_point VARCHAR(254),".
					"request_time1_from INTEGER,".
					"request_time1_to INTEGER,".
					"request_time2_from INTEGER,".
					"request_time2_to INTEGER,".
					"request_flag_money_must_be INTEGER,".
					"request_flag_money_simple INTEGER,".
					"request_flag_certificate INTEGER,".
					"request_flag_sticker INTEGER".
				");",
	
				'ud_request_product' => "CREATE TABLE ud_request_product (".
					"_id INTEGER PRIMARY KEY,".
					"request_product_request_id INTEGER,".
					"request_product_product_id INTEGER,".
					"request_product_code VARCHAR(254),".
					"request_product_amount FLOAT(10,2)".
				");",
            
                'ud_version' => "CREATE TABLE ud_version (".
                    "_id INTEGER PRIMARY KEY,".
                    "version_number VARCHAR(254),".
                    "version_datetime DATETIME".
                ")"
			);
			
			try {
				$db = new PDO( 'sqlite:'.$this->filename );
				
				if ( $db->beginTransaction( ) ) {
					foreach( $arrQuery as $szQuery ) {
						$db->exec( $szQuery );
					}
					
					$db->commit( );
				} else {
					$this->ShowMessage( 'Begin Transaction failed<br/>' );
				}
			}
			catch( PDOException $e ) {
			    $this->ShowMessage( 'Exception: '.$e->getMessage( ) );
				return;
			}
			
			$calls = array(
			    //array( 'ud_admin',				'CAdmin',			'admin',			'admin',			null ),//'`admin_rank`>'.UR_SUPERADMIN ),
			    array( 'ud_client',				'CClient',			'client',			'client',	        null ),
			    array( 'ud_category',			'CCategory',		'category',			'category',	        null ),
			    array( 'ud_manager',			'CManager',			'manager',			'manager',	        null ),
			    array( 'ud_request',			'CRequest',			'request',			'request',	        null ),
			    array( 'ud_request_product',	'CRequestProduct',	'requestProduct',	'requestProduct',	null )
			);
			
			foreach( $calls as $call ) {
			    $this->processTable( $db, $call[ 0 ], $call[ 1 ], $call[ 2 ], $call[ 3 ], $call[ 4 ] );
			}
			
			$this->ExportVersion( $db, CDataVersion::TYPE_FULL );
			$this->ShowMessage( 'sqlite final filesize='.filesize( $this->filename ) );
        } // function Export
        
        public function ExportProduct( ) {
            $db = null;
            $arrQuery = array(
				'ud_product' => "CREATE TABLE ud_product (".
					"_id INTEGER PRIMARY KEY,".
					"product_category_id INT(10),".
					"product_code VARCHAR(254),".
					"product_category VARCHAR(254),".
					"product_name VARCHAR(254),".
            		"product_name_lower VARCHAR(254),".
					"product_price FLOAT(10,2),".
					"product_saldo FLOAT(10,2),".
            		"product_unit INTEGER".
				");",
            
            	'ud_product_price' => "CREATE TABLE ud_product_price (".
					"_id INTEGER PRIMARY KEY,".
					"product_price_product_id INTEGER,".
					"product_price_product_code VARCHAR(254),".
					"product_price_category_code INTEGER,".
            		"product_price_price FLOAT(10,2),".
					"product_price_nds FLOAT(10,2)".
				")",
            
                'ud_version' => "CREATE TABLE ud_version (".
                    "_id INTEGER PRIMARY KEY,".
                    "version_number VARCHAR(254),".
                    "version_datetime DATETIME".
                ")"
			);
			
			try {
				$db = new PDO( 'sqlite:'.$this->filename );
				
				if ( $db->beginTransaction( ) ) {
					foreach( $arrQuery as $szQuery ) {
						$db->exec( $szQuery );
					}
					
					$db->commit( );
				} else {
					$this->ShowMessage( 'Begin Transaction failed<br/>' );
				}
			}
			catch( PDOException $e ) {
			    $this->ShowMessage( 'Exception: '.$e->getMessage( ) );
				return;
			}
			
			$this->processTable( $db, 'ud_product',			'CProduct',			'product',		'product',		null );
			$this->processTable( $db, 'ud_product_price',	'CProductPrice',	'productPrice',	'productPrice',	null );
			$this->ExportVersion( $db, CDataVersion::TYPE_PRODUCT );
			$this->ShowMessage( 'sqlite final filesize='.filesize( $this->filename ) );
        } // function ExportProduct
        
        private function ExportVersion( $db, $type ) {
            $tmp = $this->handler->GetObject( array(
                FHOV_WHERE  => '`version_type`='.$type,
			    FHOV_TABLE  => 'ud_version',
			    FHOV_OBJECT => 'CDataVersion',
			    FHOV_LIMIT  => '1',
			    FHOV_ORDER  => '`version_number` DESC'
			) );
			if ( $tmp->HasResult( ) ) {
			    $obj = current( $tmp->GetResult( ) );
			    
			    $this->ShowMessage( 'current version: '.$obj->number.'<br/>' );
			    
				if ( $db->beginTransaction( ) ) {
				    $stmt = $db->prepare(
        				'INSERT INTO ud_version(_id, version_number, version_datetime) VALUES ('.
        				':id, :number, :datetime)'
        			);
        			$stmt->bindValue( ':id',		$obj->id,		PDO::PARAM_INT	);
        			$stmt->bindValue( ':number',	$obj->number,	PDO::PARAM_STR	);
        			$stmt->bindValue( ':datetime',	$obj->datetime,	PDO::PARAM_STR	);
			
				    if ( !$stmt->execute( ) ) {
					    $this->ShowMessage( 'error: '.$stmt->errorCode( ).'<br/>' );
					}
					
					$db->commit( );
				} else {
					$this->ShowMessage( 'Begin Transaction failed<br/>' );
				}
				
				unset( $tmp );
			}
        } // function ExportVersion
        
        private function processTable( $db, $szTable, $szObject, $szTitle, $callbackPrepare, $szWhere ) {
            $this->ShowMessage( $szTitle.'<br/>' );
            
            $arrOption = array(
                FHOV_TABLE  => $szTable,
				FHOV_OBJECT => $szObject
            );
            if ( !is_null( $szWhere ) ) {
                $arrOption[ FHOV_WHERE ] = $szWhere;
            }
            
            $totalCount = 0;
            $tmp = $this->handler->CountObject( $arrOption );
			if ( $tmp->HasResult( ) ) {
				$totalCount = $tmp->GetResult( 'count' );
			}
			
			if ( $totalCount > 0 ) {
			    $this->ShowMessage( $szTitle.' count='.$totalCount.'<br/>' );
			}
			
			if ( $totalCount > $this->frameSize ) {
			    $current = 0;
			    
				while( ( ( $current + 1 ) * $this->frameSize ) < $totalCount ) {
				    $this->exportFrame( $db, $szTable, $szObject, $szTitle, $callbackPrepare, $szWhere, ( $current * $this->frameSize ).', '.$this->frameSize );
					++$current;
				}
				
				if ( $current < $totalCount ) {
				    $this->exportFrame( $db, $szTable, $szObject, $szTitle, $callbackPrepare, $szWhere, ( $current * $this->frameSize ).', '.$this->frameSize );
				}
			} else {
    			$this->exportFrame( $db, $szTable, $szObject, $szTitle, $callbackPrepare, $szWhere );
			}
        } // function processTable
        
        private function exportFrame( $db, $szTable, $szObject, $szTitle, $callbackPrepare, $szWhere = null, $szLimit = null ) {
            $arrOption = array(
                FHOV_TABLE  => $szTable,
				FHOV_OBJECT => $szObject
            );
            if ( !is_null( $szWhere ) ) {
                $arrOption[ FHOV_WHERE ] = $szWhere;
            }
            if ( !is_null( $szLimit ) ) {
                $arrOption[ FHOV_LIMIT ] = $szLimit;
            }
            
            $tmp = $this->handler->GetObject( $arrOption );
			if ( $tmp->HasResult( ) ) {
				$tmp = $tmp->GetResult( );
				
				if ( $db->beginTransaction( ) ) {
					foreach( $tmp as $obj ) {
					    $stmt = $this->$callbackPrepare( $db, $obj );
						if ( !$stmt->execute( ) ) {
						    $this->ShowMessage( 'error: '.$stmt->errorCode( ).'<br/>' );
							break;
						}
					}
					
					$db->commit( );
				} else {
					$this->ShowMessage( 'Begin Transaction failed<br/>' );
				}
				
				unset( $tmp );
			} else {
			    $this->ShowMessage( $szTitle.' table is empty<br/>' );
			}
        } // function exportFrame
        
        /*private function admin( $db, $obj ) {
            $stmt = $db->prepare(
				'INSERT INTO ud_admin(_id, admin_login, admin_password, admin_rank) VALUES ('.
				':id, :login, :password, :rank)'
			);
			$stmt->bindValue( ':id',		$obj->id,		PDO::PARAM_INT	);
			$stmt->bindValue( ':login',		$obj->login,	PDO::PARAM_STR	);
			$stmt->bindValue( ':password',	$obj->password,	PDO::PARAM_STR	);
			$stmt->bindValue( ':rank',		$obj->rank,		PDO::PARAM_INT	);
			
			return $stmt;
		} // function admin*/
        
		private function client( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_client'.
				'(_id, client_manager_id, client_manager_code, client_code, client_name, client_name_lower, client_limit, client_phone, client_addr, client_price) VALUES ('.
				':id, :manager_id, :manager_code, :code, :name, :name_lower, :limit, :phone, :addr, :price)'
			);
			$stmt->bindValue( ':id',			$obj->id,			PDO::PARAM_INT	);
			$stmt->bindValue( ':manager_id',	$obj->manager_id,	PDO::PARAM_INT	);
			$stmt->bindValue( ':manager_code',	$obj->manager_code,	PDO::PARAM_STR	);
			$stmt->bindValue( ':code',			$obj->code,			PDO::PARAM_STR	);
			$stmt->bindValue( ':name',			$obj->name,			PDO::PARAM_STR	);
			$stmt->bindValue( ':name_lower',	mb_strtolower( $obj->name, 'UTF-8' ), PDO::PARAM_STR );
			$stmt->bindValue( ':limit',			$obj->limit,		PDO::PARAM_STR	);
			$stmt->bindValue( ':phone',			$obj->phone,		PDO::PARAM_STR	);
			$stmt->bindValue( ':addr',			$obj->addr,			PDO::PARAM_STR	);
			$stmt->bindValue( ':price',			$obj->price,		PDO::PARAM_INT	);
			
			return $stmt;
		} // function client
        
		private function category( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_category(_id, category_parent_id, category_code, category_name) VALUES ('.
				':id, :parent_id, :code, :name)'
			);
			$stmt->bindValue( ':id',		$obj->id,			PDO::PARAM_INT	);
			$stmt->bindValue( ':parent_id',	$obj->parent_id,	PDO::PARAM_INT	);
			$stmt->bindValue( ':code',		$obj->code,			PDO::PARAM_STR	);
			$stmt->bindValue( ':name',		$obj->name,			PDO::PARAM_STR	);
			
			return $stmt;
		} // function category
        
		private function manager( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_manager(_id, manager_code, manager_name, manager_login, manager_password) VALUES ('.
				':id, :code, :name, :login, :password)'
			);
			$stmt->bindValue( ':id',		$obj->id,		PDO::PARAM_INT	);
			$stmt->bindValue( ':code',		$obj->code,		PDO::PARAM_STR	);
			$stmt->bindValue( ':name',		$obj->name, 	PDO::PARAM_STR	);
			$stmt->bindValue( ':login',		$obj->login,	PDO::PARAM_STR	);
			$stmt->bindValue( ':password',	$obj->password,	PDO::PARAM_STR	);
			
			return $stmt;
        } // function manager
        
        protected function product( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_product(_id, product_category_id, product_code, product_category, product_name, product_name_lower, product_price, product_saldo, product_unit) VALUES ('.
				':id, :category_id, :code, :category, :name, :name_lower, :price, :saldo, :unit)'
			);
			$stmt->bindValue( ':id',			$obj->id,			PDO::PARAM_INT	);
			$stmt->bindValue( ':category_id',	$obj->category_id,	PDO::PARAM_INT	);
			$stmt->bindValue( ':code',			$obj->code,			PDO::PARAM_STR	);
			$stmt->bindValue( ':category',		$obj->category,		PDO::PARAM_STR	);
			$stmt->bindValue( ':name',			$obj->name,			PDO::PARAM_STR	);
			$stmt->bindValue( ':name_lower',	mb_strtolower( $obj->name, 'UTF-8' ), PDO::PARAM_STR );
			$stmt->bindValue( ':price',			$obj->price,		PDO::PARAM_STR	);
			$stmt->bindValue( ':saldo',			$obj->saldo,		PDO::PARAM_STR	);
			$stmt->bindValue( ':unit',			$obj->unit,			PDO::PARAM_INT	);
			
			return $stmt;
		} // function product
		
		protected function request( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_request(_id, request_client_id, request_client_code, request_code, request_type, request_creation_date,'.
                'request_receive_date, request_trade_point, request_time1_from, request_time1_to, request_time2_from, request_time2_to,'.
                'request_flag_money_must_be, request_flag_money_simple, request_flag_certificate, request_flag_sticker) VALUES ('.
				':id, :client_id, :client_code, :code, :type, :creation_date,'.
				':receive_date, :trade_point, :time1_from, :time1_to, :time2_from, :time2_to,'.
			    ':flag_money_must_be, :flag_money_simple, :flag_certificate, :flag_sticker)'
			);
			$stmt->bindValue( ':id',			        $obj->id,			        PDO::PARAM_INT	);
			$stmt->bindValue( ':client_id',		        $obj->client_id,	        PDO::PARAM_INT	);
			$stmt->bindValue( ':client_code',	        $obj->client_code,	        PDO::PARAM_STR	);
			$stmt->bindValue( ':code',			        $obj->code,			        PDO::PARAM_STR	);
			$stmt->bindValue( ':type',			        $obj->type,			        PDO::PARAM_INT	);
			$stmt->bindValue( ':creation_date',	        $obj->creation_date,		PDO::PARAM_STR	);
			$stmt->bindValue( ':receive_date',	        $obj->receive_date,			PDO::PARAM_STR	);
			$stmt->bindValue( ':trade_point',	        $obj->trade_point,			PDO::PARAM_STR	);
			$stmt->bindValue( ':time1_from',	        $obj->time1_from,			PDO::PARAM_INT	);
			$stmt->bindValue( ':time1_to',		        $obj->time1_to,			    PDO::PARAM_INT	);
			$stmt->bindValue( ':time2_from',	        $obj->time2_from,			PDO::PARAM_INT	);
			$stmt->bindValue( ':time2_to',		        $obj->time2_to,			    PDO::PARAM_INT	);
			$stmt->bindValue( ':flag_money_must_be',	$obj->flag_money_must_be,   PDO::PARAM_INT	);
			$stmt->bindValue( ':flag_money_simple',		$obj->flag_money_simple,	PDO::PARAM_INT	);
			$stmt->bindValue( ':flag_certificate',		$obj->flag_certificate,		PDO::PARAM_INT	);
			$stmt->bindValue( ':flag_sticker',		    $obj->flag_sticker,			PDO::PARAM_INT	);
			
			return $stmt;
		} // function request
		
		private function requestProduct( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_request_product(_id, request_product_request_id, request_product_product_id, request_product_code, request_product_amount) VALUES ('.
				':id, :request_id, :product_id, :product_code, :amount)'
			);
			$stmt->bindValue( ':id',			$obj->id,			PDO::PARAM_INT	);
			$stmt->bindValue( ':request_id',	$obj->request_id,	PDO::PARAM_INT	);
			$stmt->bindValue( ':product_id',	$obj->product_id,	PDO::PARAM_INT	);
			$stmt->bindValue( ':product_code',	$obj->product_code,	PDO::PARAM_STR	);
			$stmt->bindValue( ':amount',		$obj->amount,		PDO::PARAM_STR	);
			
			return $stmt;
		} // function requestProduct
		
		private function productPrice( $db, $obj ) {
			$stmt = $db->prepare(
				'INSERT INTO ud_product_price(_id, product_price_product_id, product_price_product_code, product_price_category_code, product_price_price, product_price_nds) VALUES ('.
				':id, :product_id, :product_code, :category_code, :price, :nds)'
			);
			$stmt->bindValue( ':id',			$obj->id,				PDO::PARAM_INT	);
			$stmt->bindValue( ':product_id',	$obj->product_id,		PDO::PARAM_INT	);
			$stmt->bindValue( ':product_code',	$obj->product_code,		PDO::PARAM_STR	);
			$stmt->bindValue( ':category_code',	$obj->category_code,	PDO::PARAM_STR	);
			$stmt->bindValue( ':price',			$obj->price.'',			PDO::PARAM_STR	);
			$stmt->bindValue( ':nds',			$obj->nds.'',			PDO::PARAM_STR	);
			
			return $stmt;
		} // function productPrice
		
    } // CDataExportAndroid
    
    class CDataExport1C extends CDataExport {
        private $folder = '';
        
        public function __construct( $handler, $folder, $progress = false, $frameSize = 5000 ) {
            parent::__construct( $handler, $progress, $frameSize );
            
            $this->folder = $folder;
        } // function __construct
        
        public function Export( ) {
        	echo "\n\n<div>Export 1C -- begin</div>\n";
            $tmp = $this->handler->GetObject( array(
                FHOV_WHERE     => '`request_state`='.( CNewRequest::STATE_NEW ),
		        FHOV_TABLE     => 'ud_new_request',
		        FHOV_OBJECT    => 'CNewRequest',
		        FHOV_INDEXATTR => 'id',
		        FHOV_ORDER	=> '`request_creation_date` DESC',
				FHOV_JOIN	=> array(
					array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager', FHOV_WHERE => 'ud_manager.manager_id=ud_new_request.request_manager_id' ),
					array( FHOV_TABLE => 'ud_client', FHOV_OBJECT => 'CClient', FHOV_WHERE => 'ud_client.client_id=ud_new_request.request_client_id' )
				)
		    ) );
		    
		    echo "<div>HasError=".( $tmp->HasError( ) ? "true" : "false" ).", HasResult=".( $tmp->HasResult( ) ? "true" : "false" )."</div>\n";
		    
		    if ( $tmp->HasError( ) ) {
		        ShowVarD( $tmp->GetError( ) );
		    }
		    
		    if ( $tmp->HasResult( ) ) {
		        $arrRequest = $arrId = array( );
		        $cnt = 0;
		        
		        $tmp = $tmp->GetResult( );
		        foreach( $tmp as $row ) {
					$objRequest = $row[ 'CNewRequest' ];
					$objRequest->client = $row[ 'CClient' ];
					$objRequest->manager = $row[ 'CManager' ];
					$arrId[ $objRequest->id ] = $objRequest->id;
					$arrRequest[ $objRequest->id ] = $objRequest;
					++$cnt;
				}
				
				echo "<div>count=".$cnt."</div>\n";
		        
		        $tmp = $this->handler->GetObject( array(
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
		        
		        $config = array(
		            array( 'MENCODE',    'C', 10 ),
                    array( 'KONTRCODE',  'C', 8 ),
                    array( 'REQCODE',    'C', 12 ),
                    array( 'REQTYPE',    'N', 1, 0 ),
                    array( 'CODE',       'C', 9 ),
                    array( 'AMOUNT',     'N', 14, 3 ),
                    array( 'DATA',       'C', 8 ),
                    array( 'ADDRESS',    'C', 100 ),
                    array( 'TRADEP',     'C', 100 ),
                    array( 'TIME1FROM',  'N', 2, 0 ),
                    array( 'TIME1TO',    'N', 2, 0 ),
                    array( 'TIME2FROM',  'N', 2, 0 ),
                    array( 'TIME2TO',    'N', 2, 0 ),
                    array( 'MONMUSTBE',  'N', 1, 0 ),
                    array( 'MONSIMPLE',  'N', 1, 0 ),
                    array( 'CERT',       'N', 1, 0 ),
                    array( 'STICKER',    'N', 1, 0 ),
                    //array( 'DELETED',    'N', 1, 0 ),
                );
                
                $filename = $this->folder.'/REQ'.date( 'Ymd-His' ).'.dbf';
    		    $db = dbase_create( $filename, $config );
                if ( $db !== false ) {
                    foreach( $arrRequest as $objRequest ) {
                        foreach( $objRequest->products as $objProduct ) {
                            $row = array(
                                /*'MENCODE'    =>*/ $objRequest->manager->code,
                                /*'KONTRCODE'  =>*/ $objRequest->client->code,
                                /*'REQCODE'    =>*/ sprintf( '%012d', $objRequest->id ),
                                /*'REQTYPE'    =>*/ $objRequest->type,
                                /*'CODE'       =>*/ $objProduct->code,
                                /*'AMOUNT'     =>*/ $objProduct->amount,
                                /*'DATA'       =>*/ preg_replace( '/^\d{2}(\d{2})-(\d{2})-(\d{2})$/', '$3.$2.$1', $objRequest->receive_date ),
                                /*'ADDRESS'    =>*/ '',
                                /*'TRADEP'     =>*/ $objRequest->trade_point,
                                /*'TIME1FROM'  =>*/ $objRequest->time1_from,
                                /*'TIME1TO'    =>*/ $objRequest->time1_to,
                                /*'TIME2FROM'  =>*/ $objRequest->time2_from,
                                /*'TIME2TO'    =>*/ $objRequest->time2_to,
                                /*'MONMUSTBE'  =>*/ $objRequest->flag_money_must_be,
                                /*'MONSIMPLE'  =>*/ $objRequest->flag_money_simple,
                                /*'CERT'       =>*/ $objRequest->flag_certificate,
                                /*'STICKER'    =>*/ $objRequest->flag_sticker
                                //'DELETED'    => 0
                            );
                            ShowVar( $row );
                            /*foreach( $row as &$v ) {
                                if ( is_string( $v ) ) {
                                    $v = iconv( 'UTF-8', 'CP866', $v );
                                }
                            }*/
                            //$db2 = dbase_open( $filename, 2 );
                            $result = dbase_add_record( $db, $row );
                            $n = dbase_numrecords( $db );
                            ShowVar( $result, $n );
                            //dbase_close( $db2 );
                            //clearstatcache( );
                        }
                    }
                    
                    dbase_close( $db );
                }
                
                $db = dbase_open( $filename, 2 );
                if ( $db ) {
                    ShowVar( dbase_get_header_info( $db ) );
                    
		            $n = dbase_numrecords( $db );
                    ShowVar( $n );
                    for( $i = 1; $i <= $n; ++$i ) {
                        $row = dbase_get_record_with_names( $db, $i );
                        foreach( $row as &$v ) {
                            $v = trim( iconv( 'CP866', 'UTF-8', $v ) );
                        }
                        ShowVar( $row );
                    }
                    
                    dbase_close( $db );
                }
                
                foreach( $arrRequest as $objRequest ) {
                    $objRequest->state = CNewRequest::STATE_OLD;
                }
                
                $this->handler->UpdObject( $arrRequest, array( FHOV_TABLE => 'ud_new_request', FHOV_INDEXATTR => 'id' ) );
		    }
		    
		    echo "\n\n<div>Export 1C -- finished</div>\n";
        } // function Export
        
    } // class CDataExport1C
    