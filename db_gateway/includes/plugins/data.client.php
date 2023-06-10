<?php
	/**
	 * Клиент ( контрагент )
	 */
	class CClient extends CFlex {
		public	$id				= 0,	// ID
				$manager_id		= 0,	// ID менеджера
				$manager_code	= '',	// код менеджера
				$code			= '',	// код 1С
				$name			= '',	// наименование
				$limit			= 0.0,	// лимит - баланс клиента
				$phone			= '',	// телефон
				$addr			= '',	// адрес
				$price			= 0;	// категория цены
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
			
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_client';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'client_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Client';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			
			return $arrConfig;
		} // function GetConfig
		
	} // class CClient
	