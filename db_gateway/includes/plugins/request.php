<?php
	require_once( 'data.request.php' );
	
	/**
	 * Модуль заявок
	 */
	class CHModRequest extends CHandler {
		private $hCommon = NULL;
	
		/**
		 * 	Инициализация обработчиков
		 */
		public function Init( ) {
			global $objCMS;
			$arrIni = array( 'database' => $objCMS->database );
			$this->hCommon = new CFlexHandler( );
			$this->hCommon->Create( $arrIni );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_request', FHOV_OBJECT => 'CRequest' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_request_product', FHOV_OBJECT => 'CRequestProduct' ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_new_request', FHOV_OBJECT => 'CNewRequest', FHOV_FORCETABLE => true ) );
			$this->hCommon->CheckTable( array( FHOV_TABLE => 'ud_new_request_product', FHOV_OBJECT => 'CRequestProduct', FHOV_FORCETABLE => true ) );
		} // funciton InitObjectHandler
		
		public function Test( $szQuery ) {
			return ( bool ) preg_match( '/^\/request\//', $szQuery );
		} // function Test
		
		public function Process( $szQuery ) {
			global $objCMS, $objCurrent, $iCurrentSysRank, $mxdCurrentData, $szCurrentMode, $arrErrors;
			
			$this->Init( );
			
			$objCurrent = 'Request';
			$szCurrentMode = 'List';
			$arrErrors = array( );
			$mxdCurrentData = array( 'request_list' => array( ), 'pager' => NULL, 'current_request' => NULL );
			
			if ( preg_match( '/^\/request\/[0-9]+\//', $szQuery ) ) {
				$szCurrentMode = 'Edit';
				
				$tmp = NULL;
				preg_match( '/^\/request\/([0-9]+)\//', $szQuery, $tmp );
				$id = ( int ) $tmp[ 1 ];
				
				$tmp = $this->hCommon->GetObject( array(
					FHOV_WHERE		=> '`request_id`='.$id,
					FHOV_TABLE		=> 'ud_new_request',
					FHOV_OBJECT		=> 'CNewRequest',
					FHOV_INDEXATTR	=> 'id'
				) );
				if ( $tmp->HasResult( ) ) {
					$mxdCurrentData[ 'current_request' ] = $tmp->GetResult( $id );
					
					$tmp = $this->hCommon->GetObject( array(
						FHOV_WHERE => '`client_id`='.$mxdCurrentData[ 'current_request' ]->client_id,
						FHOV_TABLE => 'ud_client',
						FHOV_OBJECT => 'CClient',
						FHOV_INDEXATTR => 'id'
					) );
					if ( $tmp->HasResult( ) ) {
						$mxdCurrentData[ 'current_request' ]->client = $tmp->GetResult( $mxdCurrentData[ 'current_request' ]->client_id );
					}
					
					$tmp = $this->hCommon->GetObject( array(
						FHOV_WHERE => '`manager_id`='.$mxdCurrentData[ 'current_request' ]->manager_id,
						FHOV_TABLE => 'ud_manager',
						FHOV_OBJECT => 'CManager',
						FHOV_INDEXATTR => 'id'
					) );
					if ( $tmp->HasResult( ) ) {
						$mxdCurrentData[ 'current_request' ]->manager = $tmp->GetResult( $mxdCurrentData[ 'current_request' ]->manager_id );
					}
					
					$tmp = $this->hCommon->GetObject( array(
						FHOV_WHERE => '`request_product_request_id`='.$id,
						FHOV_TABLE => 'ud_new_request_product',
						FHOV_OBJECT => 'CRequestProduct',
						FHOV_JOIN	=> array(
							array( FHOV_TABLE => 'ud_product', FHOV_OBJECT => 'CProduct', FHOV_WHERE => 'ud_product.product_id=ud_new_request_product.request_product_product_id' ),
						)
					) );
					if ( $tmp->HasResult( ) ) {
						$mxdCurrentData[ 'current_request' ]->products = array( );
						$tmp = $tmp->GetResult( );
						foreach( $tmp as $row ) {
							$link = $row[ 'CRequestProduct' ];
							$product = $row[ 'CProduct' ];
							$link->product = $product;
							$mxdCurrentData[ 'current_request' ]->products[ ] = $link;
						}
					}
				}
			} else {
				$szUrl = $objCMS->GetPath( 'root_relative' ).'/request/?';
				$arrOption = array(
					FHOV_TABLE	=> 'ud_new_request',
					FHOV_OBJECT	=> 'CNewRequest',
					FHOV_ORDER	=> '`request_creation_date` DESC',
					FHOV_JOIN	=> array(
						array( FHOV_TABLE => 'ud_manager', FHOV_OBJECT => 'CManager', FHOV_WHERE => 'ud_manager.manager_id=ud_new_request.request_manager_id' ),
						array( FHOV_TABLE => 'ud_client', FHOV_OBJECT => 'CClient', FHOV_WHERE => 'ud_client.client_id=ud_new_request.request_client_id' )
					)
				);
				
				$iCount = $this->hCommon->CountObject( $arrOption );
				$iCount = $iCount->GetResult( "count" );
				$objPager = new CPager( );
				$mxdCurrentData[ 'pager' ] = $objPager;
				$arrData = array(
					'url' => $szUrl,
					'page' => isset( $_GET[ 'page' ] ) ? ( int ) $_GET[ 'page' ] : 0,
					'page_size' => 15,
					'total' => $iCount
				);
				$objPager->Create( $arrData, FLEX_FILTER_FORM );
				$szLimit = $objPager->GetSQLLimit( );
				if ( $szLimit !== '' ) {
					$arrOption[ FHOV_LIMIT ] = $szLimit;
				}
				
				$tmp = $this->hCommon->GetObject( $arrOption );
				if ( $tmp->HasResult( ) ) {
					$tmp = $tmp->GetResult( );
					$arrRequest = array( );
					foreach( $tmp as $row ) {
						$objRequest = $row[ 'CNewRequest' ];
						$objRequest->client = $row[ 'CClient' ];
						$objRequest->manager = $row[ 'CManager' ];
						$arrRequest[ ] = $objRequest;
					}
					
					$mxdCurrentData[ 'request_list' ] = $arrRequest;
				}
			}
			
			$szFolder = $objCMS->GetPath( 'root_application' );
			if ( $szFolder !== false && file_exists( $szFolder.'/index.php' ) ) {
				include_once( $szFolder.'/index.php' );
			}
			
			return true;
		} // function Process
		
	} // class CHModRequest
