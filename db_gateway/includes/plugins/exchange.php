<?php

	require_once( 'exchange.exchange.php' );

	/**
	 * Обмены
	 */
	class CHModExchange extends CHandler {
		
		public function Test( $szQuery ) {
			return ( bool ) preg_match( '/^\/exchange\//', $szQuery );
		}
		
		public function Process( $szQuery ) {
			global $objCMS, $objCurrent, $iCurrentSysRank, $mxdCurrentData, $szCurrentMode, $arrErrors, $mxdLinks;
			// выставляем текущий модуль
			$objCurrent = 'Exchange';
			$szCurrentMode = 'List';
			$arrErrors = array( );
			$mxdCurrentData = array( 'exchange_list' => array( ) );
			
			$szDir = '../../export';
			$tmp = scandir( $szDir );
			foreach( $tmp as $name ) {
				if ( is_file( $szDir.'/'.$name ) && preg_match( '/\.dbf$/', $name ) ) {
					$obj = new CExchange( );
					$obj->Create( array(
						'exchange_date'	=> preg_replace( '/.*(\d{2})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})\.dbf$/', '$1.$2.20$3 $4:$5:$6', $name ),
						'exchange_name'	=> $name
					) );
					$mxdCurrentData[ 'exchange_list' ][ $name ] = $obj;
				}
			}
			
			if ( isset( $_GET[ 'name' ] ) && is_string( $_GET[ 'name' ] ) && isset( $mxdCurrentData[ 'exchange_list' ][ $_GET[ 'name' ] ] ) ) {
				$szCurrentMode = 'View';
				$mxdCurrentData[ 'current_exchange' ] = $mxdCurrentData[ 'exchange_list' ][ $_GET[ 'name' ] ];
				$szPath = $szDir.'/'.$_GET[ 'name' ];
				
				if ( is_file( $szPath ) ) {
					$db = dbase_open( $szPath, 0 );
					if ( $db ) {
						$n = dbase_numrecords( $db );
						$mxdCurrentData[ 'row_count' ] = $n;
						$mxdCurrentData[ 'header' ] = array( );
						$tmp = dbase_get_header_info( $db );
						foreach( $tmp as $field ) {
							$mxdCurrentData[ 'header' ][ ] = $field[ 'name' ];
						}
						
						$mxdCurrentData[ 'header' ][ ] = 'DELETED';
						
						$mxdCurrentData[ 'rows' ] = array( );
						for( $i = 1; $i <= $n; ++$i ) {
							$row = dbase_get_record_with_names( $db, $i );
							$tmp = array( );
							foreach( $row as $index => $v ) {
								if ( is_string( $v ) ) {
									$v = trim( iconv( 'CP866', 'UTF-8', $v ) );
									if ( empty( $v ) ) {
										$v = '&#160;';
									}
								}
								
								$tmp[ ] =$v;
							}
							
							$mxdCurrentData[ 'rows' ][ ] = $tmp;
						}
						
						dbase_close( $db );
					}
				}
			}
			
			$szFolder = $objCMS->GetPath( 'root_application' );
			if ( $szFolder !== false && file_exists( $szFolder.'/index.php' ) ) {
				include_once( $szFolder.'/index.php' );
			}
		}
	}
