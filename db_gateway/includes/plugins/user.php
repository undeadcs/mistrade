<?php
	/**
	 *	Модуль пользователей
	 *	@author UndeadCS
	 *	@package UndeadCS
	 *	@subpackage ModUser
	 */

	require( "user.user.php" );
	require( "user.admin.php" );
	
	/**
	 *	Перехватчик для модуля User
	 */
	class CHModUser extends CHandler {
		private $hCommon = NULL;
		
		/**
		 * 	Инициализация обработчиков
		 */
		public function InitObjectHandler( ) {
			global $objCMS;
			$arrIni = array( 'database' => $objCMS->database );
			$this->hCommon = new CFlexHandler( );
			$this->hCommon->Create( $arrIni );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_admin', FHOV_OBJECT => 'CAdmin' ) );
		} // funciton InitObjectHandler
		
		/**
		 *	Проверка на срабатывание (перехват)
		 *	@param $szQuery string строка тестирования
		 *	@return bool
		 */
		public function Test( $szQuery ) {
			return ( bool ) preg_match( '/^\/user\//', $szQuery );
		} // function Test
		
		/**
		 *	Обработка
		 *	@param $szQuery string строка, на которой произошел перехват
		 *	@return bool
		 */
		public function Process( $szQuery ) {
			global $objCMS, $objCurrent, $iCurrentSysRank, $mxdCurrentData, $szCurrentMode, $arrErrors, $mxdLinks;
			// выставляем текущий модуль
			$objCurrent = 'User';
			$szCurrentMode = 'List';
			$this->InitObjectHandler( );
			$arrErrors = array( );
			$iCurrentSysRank = $objCMS->GetUserRank( );
			
			if ( preg_match( '/^\/user\/\+\//', $szQuery ) ) {
				$mxdCurrentData[ 'current_user' ] = new CAdmin( );
				
				if ( count( $_POST ) ) {
					$arrData = $_POST;
					$this->AddClient( $arrData, $mxdCurrentData, $arrErrors );
				}
				$szCurrentMode = 'Edit';
				//
			} elseif ( preg_match( '/^\/user\/[0-9a-zA-Z]{1,20}\//', $szQuery ) ) {
				$tmp = NULL;
				// выборка текущего юзверя
				preg_match( '/^\/user\/([0-9a-zA-Z]{1,20})\//', $szQuery, $tmp );
				$szClientLogin = $tmp[ 1 ];
				$tmp = new CAdmin( );
				$szLoginIndex = $tmp->GetAttributeIndex( 'login', NULL, FLEX_FILTER_FORM );
				$tmp1 = $tmp->Create( array( $szLoginIndex => $szClientLogin ), FLEX_FILTER_FORM );
				$szLoginValue = $tmp->GetAttributeValue( 'login', FLEX_FILTER_DATABASE );
				$tmp1 = $this->hCommon->GetObject( array( FHOV_WHERE => '`'.$szLoginIndex.'`='.$szLoginValue, FHOV_TABLE => 'ud_admin', FHOV_OBJECT => 'CAdmin', FHOV_INDEXATTR => 'id' ) );
				if ( $tmp1->HasResult( ) ) {
					$szCurrentMode = 'Edit';
					$objCMS->SetWGIState( MF_CURRENT | MF_THIS );
					$tmp2 = $tmp1->GetResult( );
					$mxdCurrentData = array( 'current_user' => current( $tmp2 ) );
				}
				//
			} else if ( preg_match( '/^\/user\/auth\//', $szQuery ) ) {
				$this->AuthorizeClient( $szQuery );
			} elseif ( count( $_POST ) ) {
				// сначала сносим юзверей
				$bRedir = true;
				$arrToUpd = array( );
				
				if ( isset( $_POST[ "del" ] ) && is_array( $_POST[ "del" ] ) ) {
					$arrOptions[ "ids" ] = $_POST[ "del" ];
					$this->DelClient( $arrOptions, $arrErrors );
				}
				
				Redirect( $objCMS->GetPath( 'root_relative' ).'/user/' );
			} else {
				$mxdCurrentData = array( 'user_list' => array( ) );
				$arrOptions = array( FHOV_WHERE => 'admin_rank > '.UR_SUPERADMIN, FHOV_TABLE => 'ud_admin', FHOV_OBJECT => 'CAdmin' );
				//
				$szUrl = $objCMS->GetPath( 'root_relative' ).'/user/?';
				//
				$iCount = $this->hCommon->CountObject( $arrOptions );
				$iCount = $iCount->GetResult( 'count' );
				$objPager = new CPager( );
				$arrData = array(
					'url' => $szUrl,
					'page' => isset( $_GET[ 'page' ] ) ? ( int ) $_GET[ 'page' ] : 0,
					'page_size' => 15,
					'total' => $iCount
				);
				$objPager->Create( $arrData, FLEX_FILTER_FORM );
				$szLimit = $objPager->GetSQLLimit( );
				if ( $szLimit !== '' ) {
					$arrOptions[ FHOV_LIMIT ] = $szLimit;
				}
				//
				$tmp = $this->hCommon->GetObject( $arrOptions );
				if ( $tmp->HasResult( ) ) {
					$mxdCurrentData[ 'user_list' ] = $tmp->GetResult( );
					$mxdCurrentData[ 'pager' ] = $objPager;
				}
			}
			// передаем управление приложению
			$szFolder = $objCMS->GetPath( 'root_application' );
			if ( $szFolder !== false && file_exists( $szFolder.'/index.php' ) ) {
				include_once( $szFolder.'/index.php' );
			}
			return true;
		} // function Process
		
		/**
		 * Вход пользователя с КПК
		 */
		protected function AuthorizeClient( $szQuery ) {
			$ret = array(
				'success' => true,
				'message' => ''
			);
			$obj = new CAdmin( );
			$arrAttrIndex = $obj->GetAttributeIndexList( FLEX_FILTER_DATABASE );
			$tmp = $obj->Create( $_POST, FLEX_FILTER_FORM );
			if ( $tmp->HasError( ) ) {
				$arrError = $tmp->GetError( );
				$tmp = array( );
				foreach( $arrError as $v ) {
					$tmp[ ] = $v;
				}
				$ret[ 'success' ] = false;
				$ret[ 'message' ] = join( "\n", $tmp );
			} else {
				$tmp = $this->hCommon->GetObject( array(
					FHOV_WHERE => '`'.$arrAttrIndex[ 'login' ].'`=\''.$obj->login.'\' AND `'.
									$arrAttrIndex[ 'password' ].'`=\''.hash( 'sha1', $obj->password ).'\'',
					FHOV_TABLE => 'ud_admin',
					FHOV_OBJECT => 'CAdmin',
					FHOV_INDEXATTR => 'id'
				) );
				if ( $tmp->HasResult( ) ) {
					$ret[ 'data' ] = $obj->GetArray( );
				} else {
					$ret[ 'success' ] = false;
					$ret[ 'message' ] = 'account not found';
				}
			}
			
			echo json_encode( $ret );
			exit;
		} // function AuthorizeClient
		
		/**
		 * 	Добавление клиентской учетки
		 * 	@param $arrData array набор данных
		 * 	@param $mxdCurrentData CAdmin текущие данные клиента
	 	 *	@param $arrErrors array массив для заполнения ошибок
	 	 *	@return void
		 */
		public function AddClient( $arrData, &$mxdCurrentData, &$arrErrors, $bSaveLog = true ) {
			global $objCMS;
			$iWgi = $objCMS->wgi;
			$objCMS->SetWGIState( WGI_USER );
			$objNewUser = new CAdmin( );
			$fltArray = new CArrayFilter( );
			$arrFilter = array(
				'id' => $objNewUser->GetAttributeIndex( 'id', NULL, FLEX_FILTER_FORM ),
			);
			$fltArray->SetArray( $arrFilter );
			$arrData = $fltArray->Apply( $arrData );
			$tmp = $objNewUser->Create( $arrData, FLEX_FILTER_FORM );
			if ( $tmp->HasError( ) ) {
				$mxdCurrentData[ 'current_user' ] = $objNewUser;
				$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
				$objCMS->SetWGIState( $iWgi );
			} else {
				$tmp = $this->GetUser( $objNewUser->login, FLEX_FILTER_FORM );
				$tmp = $this->hCommon->GetObject( array( FHOV_WHERE => $szLoginIndex.'='.$szLoginValue, FHOV_TABLE => 'ud_admin', FHOV_OBJECT => 'CAdmin' ) );
				if ( $tmp->HasResult( ) ) {
					$mxdCurrentData[ 'current_user' ] = $objNewUser;
					$arrErrors[ ] = new CError( 1, 'Учетная запись с таким логином уже существует' );
					$objCMS->SetWGIState( $iWgi );
				} else {
					$tmp = $this->hCommon->AddObject( array( $objNewUser ), array( FHOV_TABLE => 'ud_admin' ) );
					if ( $tmp->HasError( ) ) {
						// подчищаем, если возникли ошибки
						$mxdCurrentData[ 'current_user' ] = $objNewUser;
						$objCMS->DelFromWorld( array( $iClientVId ) );
						$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
					} else {
						Redirect( $objCMS->GetPath( 'root_relative' ).'/user/' );
					}
				}
			}
		} // function AddClient
		
		/**
		 * 	Добавление админской учетки
		 * 	@param $arrData array набор данных
		 * 	@param $mxdCurrentData mixed текущие данные админа
	 	 *	@param $arrErrors array массив для заполнения ошибок
	 	 *	@return void
		 */
		public function AddAdmin( $arrData, &$mxdCurrentData, &$arrErrors, $bSaveLog = true ) {
			global $objCMS;
			$arrFilter = array(
				"id" => $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "id", NULL, FLEX_FILTER_FORM ),
				"graph_vertex_id" => $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "graph_vertex_id", NULL, FLEX_FILTER_FORM ),
				"reg_date" => $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "reg_date", NULL, FLEX_FILTER_FORM ),
				"last_edit" => $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "last_edit", NULL, FLEX_FILTER_FORM ),
				"last_login" => $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "last_login", NULL, FLEX_FILTER_FORM ),
			);
			$fltArray = new CArrayFilter( );
			$arrData = $fltArray->Apply( $arrData );
			$arrData[ $arrFilter[ "reg_date" ] ] = date( "Y-m-d" );
			$arrData[ $arrFilter[ "last_edit" ] ] = date( "Y-m-d H:i:s" );
			$tmp = $mxdCurrentData[ "current_admin" ]->Create( $arrData );
			if ( $tmp->HasError( ) ) {
				$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
			} else {
				$tmp = $this->GetUser( $mxdCurrentData[ "current_admin" ]->login, FLEX_FILTER_FORM );
				if ( $tmp->HasResult( ) ) {
					$arrErrors[ ] = new CError( 1, "Учетная запись с таким логином уже существует" );
				} else {
					$tmp = $objCMS->AddToWorld( WGI_USER, "ModUser/Admin" );
					if ( $tmp->HasResult( ) ) {
						$iAdminVId = $tmp->GetResult( "graph_vertex_id" );
						$tmp = $mxdCurrentData[ "current_admin" ]->GetAttributeIndex( "graph_vertex_id", NULL, FLEX_FILTER_DATABASE );
						$tmp = $mxdCurrentData[ "current_admin" ]->Create( array( $tmp => $iAdminVId ) );
						$tmp = $this->hCommon->AddObject( array( $mxdCurrentData[ "current_admin" ] ), array( FHOV_TABLE => "ud_admin" ) );
						if ( $tmp->HasError( ) ) {
							// подчищаем, если возникли ошибки
							$objCMS->DelFromWorld( array( $iAdminVId ) );
							$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
						} else {
							if ( $bSaveLog ) {
								$modLogger = new CHModLogger( );
								$modLogger->AddLog(
									$objCMS->GetUserLogin( ),
									"ModUser",
									"ModUser::AddAdmin",
									"added new admin, login: ".$mxdCurrentData[ "current_admin" ]->login
								);
							}
							Redirect( $objCMS->GetPath( "root_relative" )."/admins/" );
						}
					}
				}
			}
		} // function AddAdmin
		
		/**
		 * 	Удаление клиентской учетки
		 * 	@return bool
		 */
		public function DelClient( $arrOptions, &$arrErros, $bSaveLog = true ) {
			global $objCMS;
			$bRedir = true;
			if ( isset( $arrOptions[ 'ids' ] ) && is_array( $arrOptions[ 'ids' ] ) && !empty( $arrOptions[ 'ids' ] ) ) {
				$iIds = array( );
				foreach( $arrOptions[ 'ids' ] as $i => $v ) {
					if ( is_int( $i ) ) {
						// индексы только целочисленные
						$iIds[ $i ] = $i;
					}
				}
				$objClient = new CAdmin( );
				$szIndex = $objClient->GetAttributeIndex( 'id', NULL, FLEX_FILTER_DATABASE );
				$arrOptions = array( FHOV_WHERE => $szIndex.' IN('.join( ',', $iIds ).')', FHOV_TABLE => 'ud_admin', FHOV_OBJECT => 'CAdmin' );
				$tmp = $this->hCommon->GetObject( $arrOptions );
				if ( $tmp->HasResult( ) ) {
					$arrClient = $tmp->GetResult( );
					$tmp = $this->hCommon->DelObject( $arrClient, array( FHOV_TABLE => 'ud_admin' ) );
					if ( $tmp->HasError( ) ) {
						$bRedir = false;
						$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
					}
				}
			}
			return $bRedir;
		} // function DelClient
		
		/**
		 * 	Проверяет существование пользователя системы
		 * 	@param $szLogin string логин пользователя
		 * 	@param $iMode int режим работы
		 * 	@param $arrOptions array набор настроек
		 * 	@return CResult
		 */
		public function GetUser( $szLogin, $iMode = FLEX_FILTER_DATABASE, $arrOptions = array( ) ) {
			if ( $this->hCommon === NULL || $this->hCommon === NULL ) {
				$this->InitObjectHandler( );
			}
			
			$objRet = new CResult( );
			$tmp = new CAdmin( );
			$szLoginIndex = $tmp->GetAttributeIndex( "login", NULL, $iMode );
			$tmp->Create( array( $szLoginIndex => $szLogin ), $iMode );
			$szLoginIndex = $tmp->GetAttributeIndex( "login", NULL, FLEX_FILTER_DATABASE );
			$szLoginValue = $tmp->GetAttributeValue( "login", FLEX_FILTER_DATABASE );
			$tmp1 = $this->hCommon->GetObject( array(
				FHOV_WHERE => "`".$szLoginIndex."`=".$szLoginValue,
				FHOV_TABLE => "ud_admin",
				FHOV_INDEXATTR => "id",
				FHOV_OBJECT => "CAdmin"
			) );
			if ( $tmp1->HasResult( ) ) {
				$tmp1 = $tmp1->GetResult( );
				$tmp1 = current( $tmp1 );
				$objRet->AddResult( $tmp1, $tmp1->id );
				$objRet->AddResult( "client", "type" );
			} else {
				$tmp = new CAdmin( );
				$szLoginIndex = $tmp->GetAttributeIndex( "login", NULL, $iMode );
				$tmp->Create( array( $szLoginIndex => $szLogin ), $iMode );
				$szLoginIndex = $tmp->GetAttributeIndex( "login", NULL, FLEX_FILTER_DATABASE );
				$szLoginValue = $tmp->GetAttributeValue( "login", FLEX_FILTER_DATABASE );
				$tmp1 = $this->hCommon->GetObject( array(
					FHOV_WHERE => "`".$szLoginIndex."`=".$szLoginValue,
					FHOV_TABLE => "ud_admin",
					FHOV_INDEXATTR => "id",
					FHOV_OBJECT => "CAdmin"
				) );
				if ( $tmp1->HasResult( ) ) {
					$tmp1 = $tmp1->GetResult( );
					$tmp1 = current( $tmp1 );
					$objRet->AddResult( $tmp1, $tmp1->id );
					$objRet->AddResult( "admin", "type" );
				}
			}
			return $objRet;
		} // function GetUser
		
		/**
		 * 	Получение зон клиента
		 */
		public function GetClientZones( &$mxdCurrentData, &$arrErrors ) {
			$tmp = $objCMS->GetLinkObjects( $mxdCurrentData[ "current_user" ]->graph_vertex_id );
			if ( $tmp->HasResult( ) ) {
				$tmp = $tmp->GetResult( );
				$tmpZones = array( );
				foreach( $tmp as $i => $v ) {
					if ( intval( $v->label ) == WGI_ZONE ) {
						$tmpZones[ ] = $v->id;
					}
				}
				$hZone = new CFlexHandler( );
				$hZone->Create( array( "database" => $objCMS->database ) );
				$tmpZone = new CFileZone( );
				$szIdIndex = $tmpZone->GetAttributeIndex( "graph_vertex_id", NULL, FLEX_FILTER_DATABASE );
				$szTypeIndex = $tmpZone->GetAttributeIndex( "type", NULL, FLEX_FILTER_DATABASE );
				$arrOptions = array(
					FHOV_WHERE => "`".$szIdIndex."` IN(".join( ",", $tmpZones ).")",
					//FHOV_WHERE => "`".$szTypeIndex."`=".FZT_DIRECT." AND `".$szIdIndex."` IN(".join( ",", $tmpZones ).")",
					FHOV_TABLE => "ud_zone", FHOV_OBJECT => "CFileZone"
				);
				$tmp = $hZone->GetObject( $arrOptions );
				if ( $tmp->HasResult( ) ) {
					$tmp = $tmp->GetResult( );
					$szZonesIndex = $mxdCurrentData[ "current_user" ]->GetAttributeIndex( "zones" );
					$mxdCurrentData[ "current_user" ]->Create( array( $szZonesIndex => $tmp ) );
				}
			}
		} // function GetClientZones
		
		/**
		 * 	Получение аккаунта суперадмина
		 * 	@return CResult
		 */
		public function GetSuperAdmin( ) {
			if ( $this->hCommon === NULL ) {
				$this->InitObjectHandler( );
			}
			
			$objRet = new CResult( );
			$objAdmin = new CAdmin( );
			$szRankIndex = $objAdmin->GetAttributeIndex( 'rank', NULL, FLEX_FILTER_DATABASE );
			$tmp = $this->hCommon->GetObject( array( FHOV_WHERE => '`'.$szRankIndex.'`='.UR_SUPERADMIN, FHOV_LIMIT => '1', FHOV_TABLE => 'ud_admin', FHOV_INDEXATTR => 'id', FHOV_OBJECT => 'CAdmin' ) );
			if ( $tmp->HasResult( ) ) {
				$tmp = $tmp->GetResult( );
				$tmp = current( $tmp );
				$objRet->AddResult( $tmp, 'superadmin' );
			}
			return $objRet;
		} // function GetSuperAdmin
		
		/**
		 * 	Проверяет существование суперадмина
		 * 	@return bool
		 */
		public function CheckSuperAdmin( ) {
			$bRet = false;
			$objAdmin = new CAdmin( );
			$szRankIndex = $objAdmin->GetAttributeIndex( 'rank', NULL, FLEX_FILTER_DATABASE );
			$tmp = $this->hCommon->CountObject( array( FHOV_WHERE => '`'.$szRankIndex.'`='.UR_SUPERADMIN, FHOV_TABLE => 'ud_admin' ) );
			if ( $tmp->HasResult( ) ) {
				$tmp = intval( $tmp->GetResult( 'count' ) );
				if ( $tmp ) {
					$bRet = true;
				}
			}
			return $bRet;
		} // function CheckSuperAdmin
		
	} // class CHModUser
	
