<?php
	/**
	 * Заявка
	 */
	class CRequestProduct extends CFlex {
		public	$id			= 0,	// ID
				$request_id	= 0,	// ID заявки
				$product_id	= 0,	// ID товара
				$code		= '',	// код из 1С
				$amount		= 0.0,	// количество
				$product	= NULL;	// товар
	
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
	
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_request_product';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'request_product_';
			
			$arrConfig[ FLEX_CONFIG_UPDATE ][ FLEX_CONFIG_INDEXATTR ] =
			$arrConfig[ FLEX_CONFIG_DELETE ][ FLEX_CONFIG_INDEXATTR ] = 'id';
			
			$arrConfig[ FLEX_CONFIG_CREATE	][ FLEX_CONFIG_IGNOREATTR ] =
			$arrConfig[ FLEX_CONFIG_INSERT	][ FLEX_CONFIG_IGNOREATTR ] =
			$arrConfig[ FLEX_CONFIG_SELECT	][ FLEX_CONFIG_IGNOREATTR ] =
			$arrConfig[ FLEX_CONFIG_UPDATE	][ FLEX_CONFIG_IGNOREATTR ] =
			$arrConfig[ FLEX_CONFIG_DELETE	][ FLEX_CONFIG_IGNOREATTR ] = array( 'product' );
			
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'RequestProduct';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			$arrConfig[ 'product'	][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_OBJECT;
	
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
		
				if ( $this->product instanceof CProduct ) {
					$tmp = $this->product->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
			}
		
			return $objRet;
		} // function GetXML
			
	} // class CRequest

