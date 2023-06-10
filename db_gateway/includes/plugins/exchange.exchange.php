<?php
	/**
	 * Запись обмена
	 */
	class CExchange extends CFlex {
		public	$name	= '',
				$date	= '';
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
		
			// общие настройки
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'exchange_';
			
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Exchange';
		
			return $arrConfig;
		}
	}