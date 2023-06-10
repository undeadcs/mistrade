<?php
	/**
	 * Заявка
	 */
	class CRequest extends CFlex {
	    const   TYPE_BILL    = 0,
	            TYPE_INVOICE = 1;
	    
		public	$id				    = 0,  // ID
				$client_id		    = 0,  // ID клиента (контрагента)
				$client_code	    = '', // код из 1С
				$code			    = '', // код из 1С
				$type			    = 0,  // тип
				$creation_date	    = '', // дата создания
				$receive_date       = '', // дата доставки
				$trade_point        = '', // торговая точка
				$time1_from         = 0,  // время доставки (1) С
				$time1_to           = 0,  // время доставки (1) ПО
				$time2_from         = 0,  // время доставки (2) С
				$time2_to           = 0,  // время доставки (2) ПО
				$flag_money_must_be = 0,  // деньги обязательно
				$flag_money_simple  = 0,  // просто деньги
				$flag_certificate   = 0,  // сертификат
				$flag_sticker       = 0,  // наклейки
				$manager_id			= 0,	// ID менеджера
				$manager			= NULL,	// менеджер
				$client				= NULL,	// клиент (контрагент)
				$products           = array( ); // товары
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
		
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_request';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'request_';
			//$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			//$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			//$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			$arrConfig[ FLEX_CONFIG_CREATE	][ FLEX_CONFIG_IGNOREATTR ] = array( 'products', 'manager', 'client' );
			$arrConfig[ FLEX_CONFIG_INSERT	][ FLEX_CONFIG_IGNOREATTR ] = array( 'products', 'manager', 'client' );
			$arrConfig[ FLEX_CONFIG_SELECT	][ FLEX_CONFIG_IGNOREATTR ] = array( 'products', 'manager', 'client' );
			$arrConfig[ FLEX_CONFIG_UPDATE	][ FLEX_CONFIG_IGNOREATTR ] = array( 'products', 'manager', 'client' );
			$arrConfig[ FLEX_CONFIG_DELETE	][ FLEX_CONFIG_IGNOREATTR ] = array( 'products', 'manager', 'client' );
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Request';
			// настройки атрибутов
			$arrConfig[ 'id'	        ][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'	        ][ FLEX_CONFIG_DIGITS   ] = 10;
			$arrConfig[ 'creation_date'	][ FLEX_CONFIG_TYPE	    ] = FLEX_TYPE_DATE | FLEX_TYPE_TIME;
			$arrConfig[ 'receive_date'	][ FLEX_CONFIG_TYPE	    ] = FLEX_TYPE_DATE;
			$arrConfig[ 'manager'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_OBJECT;
			$arrConfig[ 'client'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_OBJECT;
			$arrConfig[ 'products'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_ARRAY;
		
			return $arrConfig;
		} // function GetConfig
		
		/**
		 *	Получение XML экземпляра
		 *	@param $domDoc DOMDocument экземпляр данного класса
		 *	@return CResult
		 */
		public function GetXML( $domDoc ) {
			$objRet = parent::GetXML( $domDoc );
			if ( $objRet->HasResult( ) ) {
				$doc = $objRet->GetResult( 'doc' );
				
				if ( $this->manager instanceof CManager ) {
					$tmp = $this->manager->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
				
				if ( $this->client instanceof CClient ) {
					$tmp = $this->client->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
				
				if ( !empty( $this->products ) ) {
					foreach( $this->products as $obj ) {
						$tmp = $obj->GetXML( $domDoc );
						if ( $tmp->HasResult( ) ) {
							$doc->appendChild( $tmp->GetResult( 'doc' ) );
						}
					}
				}
			}
			
			return $objRet;
		} // function GetXML
		
		protected function InitAttr( $szName, &$arrInput, &$arrConfig, $iMode = FLEX_FILTER_PHP ) {
		    if ( $szName == 'products' ) {
		        $objResult = new CResult( );
		        $szIndex = $this->GetAttributeIndex( $szName, $arrConfig, $iMode );
		        
		        if ( isset( $arrInput[ $szIndex ] ) && is_array( $arrInput[ $szIndex ] ) ) {
		            $this->products = array( );
		            foreach( $arrInput[ $szIndex ] as $row ) {
		                $objRequestProduct = new CRequestProduct( );
		                $objRequestProduct->Create( $row, $iMode );
		                $this->products[ ] = $objRequestProduct;
		            }
		        }
		        
		        return $objResult;
		    }
		    
		    return parent::InitAttr( $szName, $arrInput, $arrConfig, $iMode );
		} // function InitAttr
					
	} // class CRequest
	