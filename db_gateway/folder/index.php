<?php
	global $objCMS, $objCurrent, $iCurrentSysRank, $szCurrentMode, $mxdCurrentData, $arrErrors, $mxdLinks;

	header( 'Content-Type: text/html; charset=UTF-8' );
	
	$objPage = new CPage( );
	$objPage->SetTitle( 'ИСУ Торговля' );
	$objPage->AddMeta( array( 'http_equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8' ) );
	$objPage->AddStyle( $objCMS->GetPath( 'root_relative' ).'/main.css' );
	$objPage->AddScript( $objCMS->GetPath( 'root_relative' ).'/jquery.js' );
	
	$domDoc = new DOMDocument( );
	$domXsl = new DOMDocument( );
	$objXlst = new XSLTProcessor( );
	
	$objDoc = $domDoc->createElement( "Doc" );
	$domDoc->appendChild( $objDoc );
	
	$domXsl->load( $objCMS->GetPath( "root_application" )."/main.xsl" );
	
	$objMenu = new CMenu( );
	$szRoot = $objCMS->GetPath( 'root_http' );
	$arrMenu = array( // пока сделаем так, в дальнейшем будет автоматика с опредеоение current
		array( 'title' => 'Менеджеры',	'url' => $szRoot.'/manager/'	),
		array( 'title' => 'Заявки',		'url' => $szRoot.'/request/'	),
		array( 'title' => 'Обмены',		'url' => $szRoot.'/exchange/'	),
		array( 'title' => 'выход',		'url' => $szRoot.'/exit/'		),
	);
	$iCurrentSysRank = $this->objAccount->rank;
	if ( isset( $arrMenu[ $this->iCurWgi ] ) ) {
		$arrMenu[ $this->iCurWgi ][ 'flags' ] = $this->iCurWgiState;
	}
	$objMenu->Create( array( 'items' => $arrMenu ) );
	
	$tmp = $objMenu->GetXML( $domDoc );
	if ( $tmp->HasResult( ) ) {
		$objDoc->appendChild( $tmp->GetResult( "doc" ) );
	}
	
	if ( $objCurrent && $szCurrentMode ) {
		$doc = $domDoc->createElement( $objCurrent.$szCurrentMode );
		$objDoc->appendChild( $doc );
		
		if ( !empty( $arrErrors ) ) {
			foreach( $arrErrors as $i => $v ) {
				$tmp = $domDoc->createElement( "Error" );
				$tmp->setAttribute( "text", $v->text );
				$doc->appendChild( $tmp );
			}
		}
		
		if ( $objCurrent === "Install" ) {
			$domDoc = new DOMDocument( );
			$doc = $domDoc->createElement( $objCurrent.$szCurrentMode );
			$doc->setAttribute( "logo_url", $objCMS->GetPath( "root_relative" )."/" );
			$doc->setAttribute( "logo_src", $objCMS->GetPath( "root_relative" )."/skin/logo.gif" );
			$domDoc->appendChild( $doc );
			
			if ( !empty( $arrErrors ) ) {
				foreach( $arrErrors as $i => $v ) {
					$tmp = $domDoc->createElement( "Error" );
					$tmp->setAttribute( "text", $v->text );
					$doc->appendChild( $tmp );
				}
			}
			
			$doc->setAttribute( "post_url", $objCMS->GetPath( "root_relative" )."/$/" );
			
			$arrNeed = array( "db", "superadmin" );
			foreach( $arrNeed as $v ) {
				$tmp = $mxdCurrentData[ $v ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( "doc" ) );
				}
			}
			
			$objPage->StartBody( );
			
			$objXlst->importStylesheet( $domXsl );
			$szText = $objXlst->transformToXml( $domDoc );
			$szText = preg_replace( '/<textarea([^>]*)\/>/', '<textarea$1></textarea>', $szText );
			echo $szText;
			
			$objPage->EndBody( );
			echo $objPage->GetDoc( );
			return;
		}
		
		if ( $objCurrent === "Login" ) {
			$objPage->SetTitle( "ИСУ Торговля / Вход в систему" );
			$domDoc = new DOMDocument( );
			$doc = $domDoc->createElement( $objCurrent.$szCurrentMode );
			$doc->setAttribute( "post_url", $objCMS->GetPath( "root_relative" )."/" );
			$doc->setAttribute( "logo_url", $objCMS->GetPath( "root_relative" )."/" );
			$doc->setAttribute( "logo_src", $objCMS->GetPath( "root_relative" )."/skin/logo.gif" );
			$domDoc->appendChild( $doc );
			
			$objPage->StartBody( );
			
			$objXlst->importStylesheet( $domXsl );
			$szText = $objXlst->transformToXml( $domDoc );
			$szText = preg_replace( '/<textarea([^>]*)\/>/', '<textarea$1></textarea>', $szText );
			echo $szText;
			
			$objPage->EndBody( );
			echo $objPage->GetDoc( );
			return;
		}
		
		if ( $objCurrent === 'User' ) {
			if ( $szCurrentMode === 'List' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Пользователи' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/user' );
				if ( isset( $mxdCurrentData[ 'user_list' ] ) ) {
					foreach( $mxdCurrentData[ 'user_list' ] as $v ) {
						$tmp = $v->GetXML( $domDoc );
						if ( $tmp->HasResult( ) ) {
							$doc->appendChild( $tmp->GetResult( 'doc' ) );
						}
					}
				}
			}
			if ( $szCurrentMode === 'Edit' ) {
				if ( $mxdCurrentData[ 'current_user' ]->id ) {
					$objPage->SetTitle( 'ИСУ Торговля / Данные пользователя' );
					$doc->setAttribute( 'mode', 'edit' );
				} else {
					$objPage->SetTitle( 'ИСУ Торговля / Добавление пользователя' );
					$doc->setAttribute( 'mode', 'add' );
				}
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ) );
				$tmp = $mxdCurrentData[ 'current_user' ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( 'doc' ) );
				}
			}
		}
		
		if ( $objCurrent === 'Manager' ) {
			if ( $szCurrentMode === 'List' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Менеджеры' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/manager' );
				
				foreach( $mxdCurrentData[ 'manager_list' ] as $obj ) {
					$tmp = $obj->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
			} elseif ( $szCurrentMode === 'Edit' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Редактирование менеджера' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/manager' );
				$doc->setAttribute( 'post_url', $objCMS->GetPath( 'root_relative' ).'/manager/'.$mxdCurrentData[ 'current_manager' ]->id.'/' );
				
				$tmp = $mxdCurrentData[ 'current_manager' ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( 'doc' ) );
				}
			}
		}
		
		if ( $objCurrent === 'Request' ) {
			if ( $szCurrentMode === 'List' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Заявки' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/request' );
				
				foreach( $mxdCurrentData[ 'request_list' ] as $obj ) {
					$tmp = $obj->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
				
				$tmp = $mxdCurrentData[ 'pager' ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( 'doc' ) );
				}
			}
			if ( $szCurrentMode === 'Edit' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Просмотр заявки' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/request' );
				
				$tmp = $mxdCurrentData[ 'current_request' ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( 'doc' ) );
				}
			}
		}
		
		if ( $objCurrent === 'Exchange' ) {
			if ( $szCurrentMode === 'List' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Обмены' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/exchange' );
				
				foreach( $mxdCurrentData[ 'exchange_list' ] as $obj ) {
					$tmp = $obj->GetXML( $domDoc );
					if ( $tmp->HasResult( ) ) {
						$doc->appendChild( $tmp->GetResult( 'doc' ) );
					}
				}
			}
			if ( $szCurrentMode === 'View' ) {
				$objPage->SetTitle( 'ИСУ Торговля / Обмены' );
				$doc->setAttribute( 'base_url', $objCMS->GetPath( 'root_relative' ).'/exchange' );
				
				$tmp = $mxdCurrentData[ 'current_exchange' ]->GetXML( $domDoc );
				if ( $tmp->HasResult( ) ) {
					$doc->appendChild( $tmp->GetResult( 'doc' ) );
				}
				
				foreach( $mxdCurrentData[ 'header' ] as $name ) {
					$th = $domDoc->createElement( 'th', $name );
					$doc->appendChild( $th );
				}
				
				foreach( $mxdCurrentData[ 'rows' ] as $row ) {
					$tr = $domDoc->createElement( 'tr' );
					$doc->appendChild( $tr );
					
					foreach( $row as $cell ) {
						$td = $domDoc->createElement( 'td', $cell );
						$tr->appendChild( $td );
					}
				}
			}
		}
	}
	
	$objPage->StartBody( );
	
	$objXlst->importStylesheet( $domXsl );
	$szText = $objXlst->transformToXml( $domDoc );
	$szText = preg_replace( '/<textarea([^>]*)\/>/', '<textarea$1></textarea>', $szText );
	$szText = preg_replace( '/<script([^>]*)\/>/', '<script$1></script>', $szText );
	$szText = preg_replace( '/<a([^>]*)\/>/', '<a$1></a>', $szText );
	echo $szText;
	
	$objPage->EndBody( );
	echo $objPage->GetDoc( );
	echo '<!-- '._usr_time_work( ).'-->';
?>