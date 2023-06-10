<?php
	class CManager extends CFlex {
		const	STATE_ENABLED	= 0, // активен
				STATE_DISABLED	= 1; // заблокирован
		
		public	$id			= 0,	// ID
				$code		= '',	// код
				$name		= '',	// имя
				$login		= '',	// логин
				$password	= '',	// пароль
				$state		= 0;	// состояние
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
				
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_manager';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'manager_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Manager';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
				
			return $arrConfig;
		} // function GetConfig
		
	} // class CManager