<?php
	/**
	 * Товар
	 */
	class CProduct extends CFlex {
		const	UNIT_UNKNOWN	= 0,	// хз
				UNIT_KG			= 1,	// кг
				UNIT_PIECE		= 2;	// шт
		
		public	$id				= 0,	// ID
				$category_id	= 0,	// ID категории
				$code			= '',	// код
				$category		= '',	// категория
				$name			= '',	// имя
				$price			= 0.0,	// цена
				$saldo			= 0.0,	// сальдо
				$unit			= 0;	// единица измерения
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
		
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_product';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'product_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Product';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
		
			return $arrConfig;
		} // function GetConfig
		
	} // class CProduct
