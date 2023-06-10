<?php
    class CDataVersion extends CFlex {
        const TYPE_FULL    = 0,
              TYPE_PRODUCT = 1;
        
        protected    $id        = 0,
                     $number    = '',
                     $datetime  = '',
                     $type      = TYPE_FULL;
                     
        public function __get( $szName ) {
			$arrReadOnly = array(
				'id' => true, 'number' => true, 'datetime' => true, 'type' => true
			);
			if ( isset( $arrReadOnly[ $szName ] ) && $arrReadOnly[ $szName ] ) {
				return $this->$szName;
			}
			
			return parent::__get( $szName );
		} // function __get
		
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
				
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_version';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'version_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'Version';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE		] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			$arrConfig[ 'datetime'	][ FLEX_CONFIG_TYPE	    ] = FLEX_TYPE_DATE | FLEX_TYPE_TIME;
				
			return $arrConfig;
		} // function GetConfig
        
    } // CDataVersion