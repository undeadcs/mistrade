<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="yes"/>
	
	<xsl:template match="@*">
		<xsl:value-of select="name()"/>(<xsl:value-of select="."/>)
	</xsl:template>
	
	<xsl:template match="Error">
		<p><b><xsl:value-of select="@text"/></b></p>
	</xsl:template>
	
	<xsl:template match="Pager">
		<div class="pager">
			<div class="clear">&#160;</div>
			<xsl:for-each select="PagerPrev">
				<xsl:choose>
					<xsl:when test="@page = 0">
						<span class="page_prev">Предыдущая</span>
					</xsl:when>
					<xsl:otherwise>
						<a class="page_prev" href="{../@url}page={@page}">Предыдущая</a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<xsl:for-each select="PagerOption">
				<xsl:choose>
					<xsl:when test="@cur = 1">
						<span><xsl:value-of select="@page"/></span>
					</xsl:when>
					<xsl:otherwise>
						<a href="{../@url}page={@page}"><xsl:value-of select="@page"/></a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<xsl:for-each select="PagerNext">
				<xsl:choose>
					<xsl:when test="@page = 0">
						<span class="page_next">Следующая</span>
					</xsl:when>
					<xsl:otherwise>
						<a class="page_next" href="{../@url}page={@page}">Следующая</a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<div class="clear">&#160;</div>
		</div>
	</xsl:template>

	<xsl:template match="CMenu">
		<div class="menu_wrap"><table><tr>
			<td class="menu_lcol"><div class="menu"><!--table><tr-->
			<div class="clear">&#160;</div>
			<xsl:for-each select="*[ position( ) != last( ) ]">
				<!--td class="c1"-->
				<xsl:choose>
					<xsl:when test="@flags = 2"><!-- текущий элемент выбран -->
						<span>
						<xsl:attribute name="id">menu_item<xsl:value-of select="position( )"/></xsl:attribute>
						<xsl:value-of select="@title"/></span>
					</xsl:when>
					<xsl:when test="@flags = 3"><!-- выбран дочерний элемент текущего -->
						<a href="{@url}" class="cur">
						<xsl:attribute name="id">menu_item<xsl:value-of select="position( )"/></xsl:attribute>
						<xsl:value-of select="@title"/></a>
					</xsl:when>
					<xsl:otherwise>
						<a href="{@url}">
						<xsl:attribute name="id">menu_item<xsl:value-of select="position( )"/></xsl:attribute>
						<xsl:value-of select="@title"/></a>
					</xsl:otherwise>
				</xsl:choose>
				<!--/td-->
			</xsl:for-each>
			<div class="clear">&#160;</div>
			<!--/tr></table--></div></td>
			<td class="menu_rcol">
			<xsl:for-each select="*[ position( ) = last( ) ]">
				<a href="{@url}"><xsl:attribute name="id">menu_item_last</xsl:attribute><xsl:value-of select="@title"/></a>
				&#160;
			</xsl:for-each>
			</td>
			</tr>
		</table></div>
	</xsl:template>
	
	<xsl:template match="Doc">
		<div class="bodyWrap"><div class="wrap">
			<xsl:apply-templates select="CMenu"/>
			<xsl:apply-templates select="*[name()!='CMenu']"/>
		</div></div>
	</xsl:template>
	
	<!-- Модуль входа в систему -->
	<xsl:template match="LoginForm">
		<div class="wrap">
			<xsl:apply-templates select="Error"/>
			<div class="login_form"><form action="{@post_url}" method="post"><div class="x9"><table>
				<tr class="top"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
				<tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont">
					<table>
						<tr>
							<td class="lbl"><div>Логин:</div></td>
							<td class="inp"><div><input type="text" class="text" name="login" value=""/></div></td>
						</tr>
						<tr>
							<td class="lbl"><div>Пароль:</div></td>
							<td class="inp"><div><input type="password" class="text" name="password" value=""/></div></td>
						</tr>
						<tr>
							<td class="lbl">&#160;</td>
							<td class="sbm2"><div><input type="submit" class="sendquery" value="Войти"/></div></td>
						</tr>
					</table>
				</div></td><td class="r">&#160;</td></tr>
				<tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
			</table></div>
			</form></div>
		</div>
	</xsl:template>
	
	<!-- Модуль инсталляции -->
	<xsl:template match="Install1">
		<div class="conf">
		<h1>Системные настройки</h1>
		<xsl:apply-templates select="Error"/>
		<form action="{@post_url}" method="post">
		
		<div class="x9"><table>
			<tr class="top"><td class="l">&#160;</td>
				<td class="c"><span>Database account</span></td>
			<td class="r">&#160;</td></tr>
			<tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont">
			<xsl:for-each select="CDbAccount[1]">
				<table>
					<tr>
						<td class="lbl"><div>Сервер:</div></td>
						<td class="inp"><div><input type="text" class="text" name="db[server]" value="{@server}"/></div></td>
					</tr>
					<tr>
						<td class="lbl"><div>Имя пользователя:</div></td>
						<td class="inp"><div><input type="text" class="text" name="db[username]" value="{@username}" autocomplete="off"/></div></td>
					</tr>
					<tr>
						<td class="lbl"><div>Пароль:</div></td>
						<td class="inp"><div><input type="text" class="text" name="db[password]" value="{@password}" autocomplete="off"/></div></td>
					</tr>
					<tr>
						<td class="lbl"><div>Имя базы данных:</div></td>
						<td class="inp"><div><input type="text" class="text" name="db[database]" value="{@database}"/></div></td>
					</tr>
				</table>
			</xsl:for-each>
			</div></td><td class="r">&#160;</td></tr>
			<tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
		</table></div>
		
		<div class="x9"><table>
			<tr class="top"><td class="l">&#160;</td>
				<td class="c"><span>Superadmin account</span></td>
			<td class="r">&#160;</td></tr>
			<tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont">
			<xsl:for-each select="Admin[1]">
				<table>
					<tr>
						<td class="lbl"><div>Логин:</div></td>
						<td class="inp"><div><input type="text" class="text" name="superadmin[admin_login]" value="{@admin_login}" autocomplete="off"/></div></td>
					</tr>
					<tr>
						<td class="lbl"><div>Пароль:</div></td>
						<td class="inp"><div><input type="text" class="text" name="superadmin[admin_password]" value="" autocomplete="off"/></div></td>
					</tr>
				</table>
			</xsl:for-each>
			</div></td><td class="r">&#160;</td></tr>
			<tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
		</table></div>
		
		<div class="client_end"><table>
			<tr>
				<td class="lbl">&#160;</td>
				<td class="sbm2"><div><input type="submit" class="sendquery" value="Ok"/></div></td>
			</tr>
		</table></div>
		
		</form>
		</div>
	</xsl:template>
	
	<!-- Модуль учетных записей User ModUser -->
	<xsl:template match="UserList">
		<div class="container"><table><tr>
			<td class="lcol"><div class="lcont">
				<h1>Управление пользователями</h1>
			</div></td>
			<td class="ccol"><div class="content">
		
		<div class="add_client"><a href="{@base_url}/+/">Добавить пользователя</a></div>
		
		<xsl:apply-templates select="Error"/>
		<form action="{@base_url}/" method="post">
		<div class="list"><table>
			<tr>
				<th class="col_name"><div>Логин</div></th>
				<th class="col_del"><div>Удалить</div></th>
			</tr>
			<xsl:for-each select="Admin">
				<tr>
				<td class="col_name"><div><a href="{../@base_url}/{@admin_login}/"><xsl:value-of select="@admin_login"/></a></div></td>
				<td class="col_del"><div><input name="del[{@admin_id}]" type="checkbox"/></div></td>
				</tr>
			</xsl:for-each>
		</table></div>
		<div class="client_list_save"><table><tr>
			<td class="col1">&#160;</td>
			<td class="col2"><div><input type="submit" class="sendquery" value="Сохранить"/></div></td>
		</tr></table></div>
		</form>
		<xsl:apply-templates select="Pager"/>
			</div></td>
		</tr></table></div>
	</xsl:template>
	
	<xsl:template match="UserEdit">
		<div class="container"><table><tr>
			<td class="lcol"><div class="lcont">
				<h1>Управление пользователями</h1>
			</div></td>
			<td class="ccol"><div class="content">
		<h2><xsl:choose>
			<xsl:when test="@mode = 'add'">Добавление нового пользователя</xsl:when>
			<xsl:otherwise>Настройки пользователя <xsl:choose>
				<xsl:when test="count( Admin ) = 2">
				<xsl:value-of select="Admin[not(@main)][1]/@admin_login"/>
				</xsl:when>
				<xsl:otherwise>
				<xsl:value-of select="Admin[1]/@admin_login"/>
				</xsl:otherwise>
			</xsl:choose>
			</xsl:otherwise>
		</xsl:choose></h2>
			
		<xsl:apply-templates select="Error"/>
		<xsl:for-each select="Admin[1]">
		<div class="client_form"><form action="{../@post_url}" method="post">
			
		<div class="x9"><table>
			<tr class="top"><td class="l">&#160;</td>
				<td class="c"><span>Настройки доступа</span></td>
			<td class="r">&#160;</td></tr>
			<tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont"><table>
				<tr>
					<td class="lbl"><div>Логин:</div></td>
					<td class="inp"><div><input type="text" class="text" name="admin_login" value="{@admin_login}" autocomplete="off"/></div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Пароль:</div></td>
					<td class="inp"><div><input type="password" class="text" name="admin_password" value="" autocomplete="off"/></div></td>
				</tr>
				<tr>
					<td class="lbl">&#160;</td>
					<td class="inp"><div><input type="submit" value="Сохранить"/></div></td>
				</tr>
			</table></div></td><td class="r">&#160;</td></tr>
			<tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
		</table></div></form></div>
		</xsl:for-each>
		</div></td>
	</tr></table></div>
	</xsl:template>
    
    <xsl:template match="ManagerList">
        <div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Управление менеджерами</h1>
            </div></td>
            <td class="ccol"><div class="content">
        
        <div class="list"><table>
            <tr>
                <th class="col_code"><div>Код</div></th>
                <th class="col_name"><div>Имя</div></th>
                <th class="col_state"><div>Статус</div></th>
            </tr>
            <xsl:for-each select="Manager">
                <tr>
                <td class="col_code"><div><xsl:value-of select="@manager_code"/></div></td>
                <td class="col_name"><div><a href="{../@base_url}/{@manager_id}/"><xsl:value-of select="@manager_name"/></a></div></td>
                <td class="col_state"><div>
                	<xsl:choose>
                		<xsl:when test="@manager_state = 0"><span class="state_enabled">активирован</span></xsl:when>
                		<xsl:when test="@manager_state = 1"><span class="state_disabled">заблокирован</span></xsl:when>
                		<xsl:otherwise>&#160;</xsl:otherwise>
                	</xsl:choose>
				</div></td>
                </tr>
            </xsl:for-each>
        </table></div>
        
            </div></td>
        </tr></table></div>
    </xsl:template>
    
    <xsl:template match="ManagerEdit">
        <div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Управление пользователями</h1>
            </div></td>
            <td class="ccol"><div class="content">
            <h2>Настройки менеджера</h2>
                
            <xsl:apply-templates select="Error"/>
            <xsl:for-each select="Manager[1]">
            <div class="client_form"><form action="{../@post_url}" method="post">
                
            <div class="x9"><table>
                <tr class="top"><td class="l">&#160;</td>
                    <td class="c"><span>Менеджер</span></td>
                <td class="r">&#160;</td></tr>
                <tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont"><table>
                    <tr>
                        <td class="lbl"><div>Код:</div></td>
                        <td class="inp"><div><xsl:value-of select="@manager_code"/></div></td>
                    </tr>
                    <tr>
                        <td class="lbl"><div>Имя:</div></td>
                        <td class="inp"><div><xsl:value-of select="@manager_name"/></div></td>
                    </tr>
                    <tr>
                        <td class="lbl"><div>Логин:</div></td>
                        <td class="inp"><div><input type="text" class="text" name="manager_login" value="{@manager_login}" autocomplete="off"/></div></td>
                    </tr>
                    <tr>
                        <td class="lbl"><div>Пароль:</div></td>
                        <td class="inp"><div><input type="password" class="text" name="manager_password" value="" autocomplete="off"/></div></td>
                    </tr>
                    <tr>
                        <td class="lbl"><div>Статус:</div></td>
                        <td class="sel"><div>
                        	<select name="manager_state">
                        		<option value="0"><xsl:if test="@manager_state = 0"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>активирован</option>
                        		<option value="1"><xsl:if test="@manager_state = 1"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>заблокирован</option>
                        	</select>
                        </div></td>
                    </tr>
                    <tr>
                        <td class="lbl">&#160;</td>
                        <td class="inp"><div><input type="submit" value="Сохранить"/></div></td>
                    </tr>
                </table></div></td><td class="r">&#160;</td></tr>
                <tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
            </table></div></form></div>
            </xsl:for-each>
            </div></td>
        </tr></table></div>
    </xsl:template>
    
    <xsl:template match="RequestList">
        <div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Список заявок</h1>
            </div></td>
            <td class="ccol"><div class="content">
        
        <div class="list"><table>
            <tr>
            	<th class="col_view">&#160;</th>
                <th class="col_state"><div>Состояние</div></th>
                <th class="col_datetime"><div>Дата добавления</div></th>
                <th class="col_manager"><div>Менеджер</div></th>
                <th class="col_client"><div>Контрагент</div></th>
                <th class="col_receive"><div>Доставка</div></th>
                <th class="col_more"><div>Дополнительно</div></th>
            </tr>
            <xsl:for-each select="Request">
                <tr>
                <td class="col_view"><div><a href="{../@base_url}/{@request_id}/">Просмотреть</a></div></td>
                <td class="col_state"><div><xsl:choose>
                    <xsl:when test="@request_state = 1">
                        <xsl:attribute name="class">state_old</xsl:attribute>
                        ВЫГРУЖЕНА
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="class">state_new</xsl:attribute>
                        НОВАЯ
                    </xsl:otherwise>
                </xsl:choose></div></td>
                <td class="col_datetime"><div><xsl:value-of select="@request_creation_date"/></div></td>
                <td class="col_manager"><div><xsl:value-of select="Manager[1]/@manager_name"/></div></td>
                <td class="col_client"><div><xsl:value-of select="Client[1]/@client_name"/></div></td>
                <td class="col_receive"><div>
                    <xsl:value-of select="@request_receive_date"/><br/>
                    с <xsl:value-of select="@request_time1_from"/> по <xsl:value-of select="@request_time1_to"/><br/>
                    с <xsl:value-of select="@request_time2_from"/> по <xsl:value-of select="@request_time2_to"/>
                </div></td>
                <td class="col_more"><div>
                    <xsl:if test="@request_flag_money_must_be = 1"><span>деньги обязательно</span></xsl:if>
                    <xsl:if test="@request_flag_money_simple = 1"><span>просто деньги</span></xsl:if>
                    <xsl:if test="@request_flag_certificate = 1"><span>сертификат</span></xsl:if>
                    <xsl:if test="@request_flag_sticker = 1"><span>наклейки</span></xsl:if>
                </div></td>
                </tr>
            </xsl:for-each>
        </table></div>
        <xsl:apply-templates select="Pager"/>
            </div></td>
        </tr></table></div>
    </xsl:template>
    
    <xsl:template match="RequestEdit">
    <div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Заявка</h1>
            </div></td>
            <td class="ccol"><div class="content">
        
        <xsl:for-each select="Request[1]">
        <div class="client_form"><div class="x9"><table>
			<tr class="top"><td class="l">&#160;</td>
				<td class="c">&#160;</td>
			<td class="r">&#160;</td></tr>
			<tr class="mid"><td class="l">&#160;</td><td class="c"><div class="x9_cont"><table>
				<tr>
					<td class="lbl"><div>Дата создания:</div></td>
					<td class="inp"><div><xsl:value-of select="@request_creation_date"/></div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Менеджер:</div></td>
					<td class="inp"><div><xsl:value-of select="Manager[1]/@manager_code"/> &#8212; <xsl:value-of select="Manager[1]/@manager_name"/></div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Контрагент:</div></td>
					<td class="inp"><div><xsl:value-of select="Client[1]/@client_code"/> &#8212; <xsl:value-of select="Client[1]/@client_name"/></div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Дата доставки:</div></td>
					<td class="inp"><div><xsl:value-of select="@request_receive_date"/></div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Время доставки:</div></td>
					<td class="inp"><div>
						c <xsl:value-of select="@request_time1_from"/> по <xsl:value-of select="@request_time1_to"/>,
						c <xsl:value-of select="@request_time2_from"/> по <xsl:value-of select="@request_time2_to"/>
					</div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Дополнительно:</div></td>
					<td class="inp"><div>
						<xsl:if test="@request_flag_money_must_be = 1"><span class="break">деньги обязательно</span></xsl:if>
	                    <xsl:if test="@request_flag_money_simple = 1"><span class="break">просто деньги</span></xsl:if>
	                    <xsl:if test="@request_flag_certificate = 1"><span class="break">сертификат</span></xsl:if>
	                    <xsl:if test="@request_flag_sticker = 1"><span class="break">наклейки</span></xsl:if>
						&#160;
					</div></td>
				</tr>
				<tr>
					<td class="lbl"><div>Номенклатура:</div></td>
					<td class="inp"><div>
						<div class="list"><table>
				            <tr>
				                <th class="col_code"><div>Код</div></th>
				                <th class="col_name"><div>Наименование</div></th>
				                <th class="col_amount"><div>Количество</div></th>
				            </tr>
				            <xsl:for-each select="RequestProduct">
				                <tr>
					                <td class="col_code"><div><xsl:value-of select="@request_product_code"/></div></td>
					                <td class="col_name"><div><xsl:value-of select="Product[1]/@product_name"/></div></td>
					                <td class="col_amount"><div><xsl:value-of select="@request_product_amount"/></div></td>
				                </tr>
				            </xsl:for-each>
				        </table></div>
					</div></td>
				</tr>
			</table></div></td><td class="r">&#160;</td></tr>
			<tr class="bot"><td class="l">&#160;</td><td class="c">&#160;</td><td class="r">&#160;</td></tr>
		</table></div></div>
		</xsl:for-each>
        
            </div></td>
        </tr></table></div>
    </xsl:template>
    
    <xsl:template match="ExchangeList">
    	<div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Список обмена</h1>
            </div></td>
            <td class="ccol"><div class="content">
		        <div class="list"><table>
		            <tr>
		                <th class="col_datetime"><div>Дата создания</div></th>
		                <th class="col_name"><div>Наименование</div></th>
		            </tr>
		            <xsl:for-each select="Exchange">
		                <tr>
		                <td class="col_datetime"><div><xsl:value-of select="@exchange_date"/></div></td>
		                <td class="col_name"><div><a href="{../@base_url}/?name={@exchange_name}"><xsl:value-of select="@exchange_name"/></a></div></td>
		                </tr>
		            </xsl:for-each>
		        </table></div>
            </div></td>
        </tr></table></div>
    </xsl:template>
    
    <xsl:template match="ExchangeView">
    	<div class="container"><table><tr>
            <td class="lcol"><div class="lcont">
                <h1>Список записей обмена</h1>
            </div></td>
            <td class="ccol"><div class="content">
		        <div class="list"><table>
		            <tr>
		                <xsl:for-each select="th">
		                	<th><div><xsl:value-of select="text()"/></div></th>
		                </xsl:for-each>
		            </tr>
		            <xsl:for-each select="tr">
		                <tr>
		                <xsl:for-each select="td">
		                	<td><div><xsl:value-of select="text()"/></div></td>
		                </xsl:for-each>
		                </tr>
		            </xsl:for-each>
		        </table></div>
            </div></td>
        </tr></table></div>
    </xsl:template>
    
</xsl:stylesheet>
