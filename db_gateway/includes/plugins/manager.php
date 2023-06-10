<?php
	require_once( 'data.manager.php' );

	/**
	 * Модуль управления менеджерами
	 */
	class CHModManager extends CHandler {
		private $hCommon = NULL;
	
		/**
		 * 	Инициализация обработчиков
		 */
		public function Init( ) {
			global $objCMS;
			$arrIni = array( 'database' => $objCMS->database );
			$this->hCommon = new CFlexHandler( );
			$this->hCommon->Create( $arrIni );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager' ) );
		} // funciton InitObjectHandler
		
		public function Test( $szQuery ) {
			return ( bool ) preg_match( '/^\/manager\//', $szQuery );
		} // function Test
		
		public function Process( $szQuery ) {
			global $objCMS, $objCurrent, $iCurrentSysRank, $mxdCurrentData, $szCurrentMode, $arrErrors;
			
			$this->Init( );
			
			$objCurrent = 'Manager';
			$szCurrentMode = 'List';
			$arrErrors = array( );
			$mxdCurrentData = array(
				'current_manager' => NULL,
				'manager_list' => array( )
			);
			
			if ( preg_match( '/^\/manager\/[0-9]+\//', $szQuery ) ) {
				$szCurrentMode = 'Edit';
				
				$tmp = NULL;
				preg_match( '/^\/manager\/([0-9]+)\//', $szQuery, $tmp );
				$id = ( int ) $tmp[ 1 ];
				
				$tmp = $this->hCommon->GetObject( array(
					FHOV_WHERE		=> '`manager_id`='.$id,
					FHOV_TABLE		=> 'ud_manager',
					FHOV_OBJECT		=> 'CManager',
					FHOV_INDEXATTR	=> 'id'
				) );
				if ( $tmp->HasResult( ) ) {
					$objCurrentManager = $tmp->GetResult( $id );
					
					if ( count( $_POST ) ) {
						$arrData = $_POST;
						$this->SaveManager( $arrData, $objCurrentManager, $mxdCurrentData, $arrErrors );
					}
					
					$mxdCurrentData[ 'current_manager' ] = $objCurrentManager;
				}
			} else {
				$tmp = $this->hCommon->GetObject( array(
					FHOV_TABLE	=> 'ud_manager',
					FHOV_OBJECT	=> 'CManager',
					FHOV_ORDER	=> '`manager_name` ASC'
				) );
				if ( $tmp->HasResult( ) ) {
					$mxdCurrentData[ 'manager_list' ] = $tmp->GetResult( );
				}
			}
			
			$szFolder = $objCMS->GetPath( 'root_application' );
			if ( $szFolder !== false && file_exists( $szFolder.'/index.php' ) ) {
				include_once( $szFolder.'/index.php' );
			}
			
			return true;
		} // function Process
		
		private function SaveManager( $arrData, $objCurrentManager, &$mxdCurrentData, &$arrErrors ) {
			global $objCMS;
			
			$arrConfig = $objCurrentManager->GetConfig( );
			$fltArray = new CArrayFilter( );
			$fltArray->SetArray( array(
				'id'	=> $objCurrentManager->GetAttributeIndex( 'id', $arrConfig, FLEX_FILTER_FORM ),
				'code'	=> $objCurrentManager->GetAttributeIndex( 'code', $arrConfig, FLEX_FILTER_FORM ),
				'name'	=> $objCurrentManager->GetAttributeIndex( 'name', $arrConfig, FLEX_FILTER_FORM )
			) );
			$arrData = $fltArray->Apply( $arrData );
			$tmp = $objCurrentManager->Create( $arrData, FLEX_FILTER_FORM );
			if ( $tmp->HasError( ) ) {
				$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
			} else {
				$tmp = $this->hCommon->GetObject( array(
					FHOV_WHERE	=> '`manager_login`=\''.$objCurrentManager->FilterAttr( 'login', $arrConfig, FLEX_FILTER_DATABASE ).'\'',
					FHOV_TABLE	=> 'ud_manager',
					FHOV_OBJECT	=> 'CManager'
				) );
				if ( $tmp->HasResult( ) ) {
					$arrErrors[ ] = new CError( 1, 'Учетная запись с таким логином уже существует' );
				} else {
					if ( !$objCurrentManager->id ) {
						$arrErrors[ ] = new CError( 1, 'Добавление учетных записей невозможно' );
					} else {
						$objCurrentManager->Create( array(
							'manager_password' => hash( "sha1", $objCurrentManager->password )
						) );
						
						$tmp = $this->hCommon->UpdObject( array( $objCurrentManager ), array( FHOV_TABLE => 'ud_manager', FHOV_INDEXATTR => 'id' ) );
						if ( $tmp->HasError( ) ) {
							$arrErrors = array_merge( $arrErrors, $tmp->GetError( ) );
						} else {
							Redirect( $objCMS->GetPath( 'root_relative' ).'/manager/' );
						}
					}
				}
			}
		} // function SaveManager
		
	} // class CModManager
