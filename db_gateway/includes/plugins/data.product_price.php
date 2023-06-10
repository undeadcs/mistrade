<?php
	/**
	 * Цена товара относительно категории и клиента
	 */
    class CProductPrice extends CFlex {
        public 	$id				= 0,	// ID
                $product_id		= 0,	// ID товара
                $product_code	= '',	// Код номенклатуры ( товара )
                $category_code	= 0,	// Код категории
                $price			= 0.0,	// Цена
                $nds			= 0.0;	// НДС
                
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
			
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_product_price';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'product_price_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'ProductPrice';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			
			return $arrConfig;
		} // function GetConfig
		
    } // class CProductPrice
    