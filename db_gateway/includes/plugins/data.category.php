<?php
	/**
	 * Категория
	 */
	class CCategory extends CFlex {
		public	$id			= 0,	// ID
				$parent_id	= 0,	// ID родительской категории
				$code		= '',	// код
				$name		= '';	// наименование
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
			
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_category';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'category_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Category';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			
			return $arrConfig;
		} // function GetConfig
		
	} // class CCategory