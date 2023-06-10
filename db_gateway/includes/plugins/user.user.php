<?php
	/**
	 *	Учетная запись
	 *	@author UndeadCS
	 *	@package UndeadCS
	 *	@subpackage ModUser
	 */

	/**
	 *	Пользователь
	 */
	class CUser extends CFlex {
		protected	$id			= 0,
					$login		= '',
					$password	= '';
		
		public function __get( $szName ) {
			$arrReadOnly = array(
				'id' => true,
				'login' => true,
				'password' => true,
			);
			if ( isset( $arrReadOnly[ $szName ] ) && $arrReadOnly[ $szName ] ) {
				return $this->$szName;
			} else {
				return parent::__get( $szName );
			}
		} // function __get
		
		/**
		 *	Получение конфига
		 *	@return array
		 */
		public function GetConfig( ) {
			$arrConfig = parent::GetConfig( );
			// общие настройки
			$arrConfig[ FLEX_CONFIG_TABLE ] = 'ud_user';
			$arrConfig[ FLEX_CONFIG_PREFIX ] = 'user_';
			$arrConfig[ FLEX_CONFIG_SELECT ] = 'id';
			$arrConfig[ FLEX_CONFIG_UPDATE ] = 'id';
			$arrConfig[ FLEX_CONFIG_DELETE ] = 'id';
			// настройки режимов
			$arrConfig[ FLEX_CONFIG_XML	][ FLEX_CONFIG_XMLNODENAME ] = 'User';
			// настройки атрибутов
			$arrConfig[ 'id'		][ FLEX_CONFIG_TYPE	] = FLEX_TYPE_INT | FLEX_TYPE_UNSIGNED | FLEX_TYPE_NOTNULL | FLEX_TYPE_AUTOINCREMENT | FLEX_TYPE_PRIMARYKEY;
			$arrConfig[ 'id'		][ FLEX_CONFIG_DIGITS	] = 10;
			$arrConfig[ 'login'		][ FLEX_CONFIG_LENGHT	] = 20;
			$arrConfig[ 'login'		][ FLEX_CONFIG_TITLE	] = 'Логин';
			$arrConfig[ 'password'	][ FLEX_CONFIG_LENGHT	] = 128;
			$arrConfig[ 'password'	][ FLEX_CONFIG_TITLE	] = 'Пароль';
			
			return $arrConfig;
		} // function GetConfig
		
		public function IsPasswordEqual( $szPassword ) {
			$tmp = $szPassword;
			$tmp = hash( "sha1", $tmp );
			/*for( $i = 0; $i < 3; ++$i ) {
			    $tmp = hash( "sha1", $tmp );
			}*/
			
			return ( $tmp === $this->password );
		} // function IsPasswordEqual
		
		/**
		 *	Фильтрует значение для выбранного атрибута, используется для ввода данных в объект
		 *	@param $szName string имя атрибута
		 *	@param $arrInput mixed некое значение
		 *	@param $arrConfig array конфиг объекта
		 *	@param $iMode int режим фильтрации
		 *	@return CResult
		 */
		protected function InitAttr( $szName, &$arrInput, &$arrConfig, $iMode = FLEX_FILTER_PHP ) {
			$objRet = new CResult( );
			$szIndex = $this->GetAttributeIndex( $szName, $arrConfig, $iMode );
			$objRet = parent::InitAttr( $szName, $arrInput, $arrConfig, $iMode );
			// так делаем алерты на обязательные параметры
			$arrMust = array( "login", "password" );
			if ( in_array( $szName, $arrMust ) ) {
				$szTitle = ( isset( $arrConfig[ $szName ][ FLEX_CONFIG_TITLE ] ) ? @strval( $arrConfig[ $szName ][ FLEX_CONFIG_TITLE ] ) : $szName );
				if ( !isset( $arrInput[ $szIndex ] ) ) {
					$objRet->AddError( new CError( 1, "Отсутствует поле '".$szTitle."'" ), $szName );
				} elseif ( $arrInput[ $szIndex ] === "" ) {
					$objRet->AddError( new CError( 1, "Поле '".$szTitle."' пусто" ), $szName );
				} elseif ( $szName == "login" ) {
					$mxdValue = @strval( $arrInput[ $szIndex ] );
					if ( !CValidator::Login( $mxdValue ) ) {
						$objRet->AddError( new CError( 1, "Неверное значение поля '".$szTitle."'" ) );
					}
				}
			}
			return $objRet;
		} // function InitAttr
		
		/**
		 *	Фильтрует значение для выбранного атрибута, используется для вывода данных
		 *	@param $szName string имя атрибута
		 *	@param $arrConfig array конфиг объекта
		 *	@param $iMode int режим фильтрации
		 *	@return mixed
		 */
		protected function FilterAttr( $szName, &$arrConfig, $iMode = FLEX_FILTER_PHP ) {
			if ( $szName == "password" && $iMode == FLEX_FILTER_DATABASE ) {
				$tmp = $this->password;
				$tmp = hash( "sha1", $tmp );
				/*for( $i = 0; $i < 3; ++$i ) {
					$tmp = hash( "sha1", $tmp );
				}*/
				
				return "'".$tmp."'";
			}
			
			return parent::FilterAttr( $szName, $arrConfig, $iMode );
		} // function FilterAttr
		
	} // class CUser
	
