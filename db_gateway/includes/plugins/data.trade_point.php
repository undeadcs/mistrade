<?php
	class CTradePoint extends CFlex {
		protected	$id					= 0,
					$client_id			= 0,
					$name				= '',
					$addr				= '',
					$contact_1_rank		= '',
					$contact_1_fio		= '',
					$contact_1_phone	= '',
					$contact_2_rank		= '',
					$contact_2_fio		= '',
					$contact_2_phone	= '',
					$contact_3_rank		= '',
					$contact_3_fio		= '',
					$contact_3_phone	= '';
		
		public function __get( $szName ) {
			$arrReadOnly = array(
				'id' => true
			);
			if ( isset( $arrReadOnly[ $szName ] ) && $arrReadOnly[ $szName ] ) {
				return $this->$szName;
			}
				
			return parent::__get( $szName );
		} // function __get
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
				
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_trade_point';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'trade_point_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'TradePoint';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
				
			return $arrConfig;
		} // function GetConfig
		
	} // class CTradePoint