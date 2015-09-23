<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Logon($Settings,$Params){
	/******************************************************************************/
	$__args_types = Array('array','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	return Array('Url'=>$Settings['Params']['Url'],'Args'=>Array('lang'=>$Settings['Params']['Language'],'theme'=>$Settings['Params']['Theme'],'checkcookie'=>'no','username'=>$Params['Login'],'password'=>$Params['Password'],'func'=>'auth'));
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Get_Domains($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'domain','su'=>$Settings['Login']));
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Domains = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Domains['error']))
		return new gException('GET_DOMAINS_ERROR',$Domains['error']);
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Users = %s',print_r($Users,true)));
	$Result = Array();
	#-------------------------------------------------------------------------------
	foreach($Domains as $Domain){
		#-------------------------------------------------------------------------------
		if(!IsSet($Domain['name']))
			continue;
		#-------------------------------------------------------------------------------
		if(!IsSet($Domain['user']))
			continue;
		#-------------------------------------------------------------------------------
		if(!IsSet($Result[$Domain['user']]))
			$Result[$Domain['user']] = Array();
		#-------------------------------------------------------------------------------
		$Result[$Domain['user']][] = $Domain['name'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Users = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём домены реселлеров
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'reseller',));
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Resellers = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Resellers['error']))
		return new gException('GET_RESELLERS_ERROR',$Resellers['error']);
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Resellers = %s',print_r($Resellers,true)));
	#-------------------------------------------------------------------------------
	foreach($Resellers as $Reseller){
		#-------------------------------------------------------------------------------
		if(!IsSet($Reseller['name']))
			continue;
		#-------------------------------------------------------------------------------
		$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'domain','su'=>$Reseller['name']));
		if(Is_Error($Response))
			return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray('elem');
		#-------------------------------------------------------------------------------
		$Domains = $XML['doc'];
		#-------------------------------------------------------------------------------
		if(IsSet($Domains['error']))
			return new gException('GET_DOMAINS_ERROR',$Domains['error']);
		#-------------------------------------------------------------------------------
		#Debug(SprintF('[system/libs/DNSmanager5.php]: Users = %s',print_r($Users,true)));
		#-------------------------------------------------------------------------------
		foreach($Domains as $Domain){
			#-------------------------------------------------------------------------------
			if(!IsSet($Domain['name']))
				continue;
			#-------------------------------------------------------------------------------
			if(!IsSet($Domain['user']))
				continue;
			#-------------------------------------------------------------------------------
			if(!IsSet($Result[$Reseller['name']]))
				$Result[$Reseller['name']] = Array();
			#-------------------------------------------------------------------------------
			$Result[$Reseller['name']][] = $Domain['name'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Users = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Result;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Get_Users($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'user'));
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Users = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Users['error']))
		return new gException('GET_USERS_ERROR',$Users['error']);
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Users = %s',print_r($Users,true)));
	$Result = Array();
	#-------------------------------------------------------------------------------
	foreach($Users as $User){
		#-------------------------------------------------------------------------------
		if(!IsSet($User['name']))
			continue;
		#-------------------------------------------------------------------------------
		if(!IsSet($User['parent']))
			continue;
		#-------------------------------------------------------------------------------
		$Result[] = $User['name'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'reseller'));
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Users = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Users['error']))
		return new gException('GET_USERS_ERROR',$Users['error']);
	#-------------------------------------------------------------------------------
	foreach($Users as $User){
		#-------------------------------------------------------------------------------
		if(!IsSet($User['name']))
			continue;
		#-------------------------------------------------------------------------------
		$Result[] = $User['name'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/DNSmanager5.php]: Result = %s',print_r($Result,true)));
	return $Result;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Create($Settings,$Login,$Password,$DNSmanagerScheme){
	/******************************************************************************/
	$__args_types = Array('array','string','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$IsReselling = $DNSmanagerScheme['IsReselling'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# JBS-543, проверяем наличие такого юзера
	$Request = Array(
			'authinfo'      => $authinfo,
			'func'          => $IsReselling?'reseller.edit':'user.edit',
			'out'           => 'xml',
			'elid'          => $Login
			);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	#-------------------------------------------------------------------------------
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['error']))
		return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Request = Array(
			'authinfo'	=> $authinfo,
			'out'		=> 'xml', # Формат вывода
			'func'		=> ($IsReselling?'reseller.edit':'user.edit'), # Целевая функция
			'sok'		=> 'yes', # Значение параметра должно быть равно "yes"
			'name'		=> $Login, # Имя пользователя (реселлера)
			'passwd'	=> $Password, # Пароль
			'confirm'	=> $Password, # Подтверждение
			'domainlimit'	=> $DNSmanagerScheme['DomainLimit'],
			'namespace'	=> $DNSmanagerScheme['ViewArea']
			);
	#-------------------------------------------------------------------------------
	if($DNSmanagerScheme['Reseller'])
		$Request['su'] = $DNSmanagerScheme['Reseller'];
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Create]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------  
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('ACCOUNT_CREATE_ERROR','Не удалось создать заказ вторичного DNS');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Active($Settings,$Login,$IsReseller = FALSE){
	/******************************************************************************/
	$__args_types = Array('array','string','boolean');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>$IsReseller?'reseller.resume':'user.resume','elid'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Activate]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('ACCOUNT_ACTIVATE_ERROR','Не удалось активировать заказ вторичного DNS');
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Suspend($Settings,$Login,$IsReseller = FALSE){
	/******************************************************************************/
	$__args_types = Array('array','string','boolean');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>$IsReseller?'reseller.suspend':'user.suspend','elid'=>$Login));
	#-------------------------------------------------------------------------------
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Suspend]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('ACCOUNT_SUSPEND_ERROR','Не удалось заблокировать заказ вторичного DNS');
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Delete($Settings,$Login,$IsReseller = FALSE){
	/******************************************************************************/
	$__args_types = Array('array','string','boolean');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# JBS-543, проверяем наличие такого юзера
	$Request = Array(
			'authinfo'      => $authinfo,
			'func'          => $IsReseller?'reseller.edit':'user.edit',
			'out'           => 'xml',
			'elid'          => $Login
			);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# проверка что это реселлер, если так - надо дропать его юзеров
	if($IsReseller){
		#-------------------------------------------------------------------------------
		# достаём список всех его юзеров
		$Request = Array(
				'authinfo'      => $authinfo,
				'func'          => 'user',
				'out'           => 'xml',
				'su'            => $Login
				);
		#-------------------------------------------------------------------------------
		$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
		if(Is_Error($Response))
			return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray('elem');
		#-------------------------------------------------------------------------------
		$Users = $XML['doc'];
		#-------------------------------------------------------------------------------
		if(Is_Array($Users)){
			#-------------------------------------------------------------------------------
			# дропаем юзеров
			foreach($Users as $User){
				#-------------------------------------------------------------------------------
				if(!IsSet($User['name']))
					continue;
				#-------------------------------------------------------------------------------
				$Request = Array(
						'authinfo'      => $authinfo,
						'func'		=> 'user.delete',
						'out'		=> 'xml',
						'su'		=> $Login,
						'elid'		=> $User['name']
						);
				#-----------------------------------------------------------------------------
				$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
				if(Is_Error($Response))
					return ERROR | @Trigger_Error('[DNSmanager5_Delete]: не удалось соедениться с сервером');
				# я так думаю, неважно чё он там ответил, если ответил...
				#-----------------------------------------------------------------------------
			}
			#-----------------------------------------------------------------------------
		}
		#-----------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# собственно дропаем юзера/реселлера
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>$IsReseller?'reseller.delete':'user.delete','elid'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Delete]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('ACCOUNT_DELETE_ERROR','Не удалось удалить заказ вторичного DNS');
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Scheme_Change($Settings,$Login,$DNSmanagerScheme){
	/******************************************************************************/
	$__args_types = Array('array','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = DNSmanager5_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$IsReselling = $DNSmanagerScheme['IsReselling'];
	#-------------------------------------------------------------------------------
	$Request = Array(
			'authinfo'	=> $authinfo,
			'out'		=> 'xml', # Формат вывода
			'func'		=> ($IsReselling?'reseller.edit':'user.edit'), # Целевая функция
			'elid'		=> $Login, # Уникальный идентификатор
			'sok'		=> 'yes', # Значение параметра должно быть равно "yes"
			'name'		=> $Login, # Имя пользователя (реселлера)
			'domainlimit'	=> $DNSmanagerScheme['DomainLimit'],
			'namespace'	=> $DNSmanagerScheme['ViewArea']
			  );
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Scheme_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('SCHEME_CHANGE_ERROR','Не удалось изменить тарифный план для заказа вторичного DNS');
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Password_Change($Settings,$Login,$Password,$Params){
	/******************************************************************************/
	$__args_types = Array('array','string','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = Array(
			'Address'  => $Settings['Address'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo,
			'IsLogging'=> $Settings['Params']['IsLogging']
			);
	#-------------------------------------------------------------------------------
	$Request = Array(
			'authinfo'	=> $authinfo,
			'out'		=> 'xml',
			'func'		=> 'usrparam',
			'su'		=> $Login,
			'sok'		=> 'ok',
			'atype'		=> 'atany',         # разрешаем доступ к панели с любого IP
			'password'	=> $Password,
			'confirm'	=> $Password,
			);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[DNSmanager5_Password_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error('[DNSmanager5_Password_Change]: неверный ответ от сервера');
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('PASSWORD_CHANGE_ERROR','Не удалось изменить пароль для заказа вторичного DNS');
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


# внутренние функции
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function DNSmanager5_Build_HTTP($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = Array(
			'Address'       => $Settings['Address'],
			'Port'          => $Settings['Port'],
			'Host'          => $Settings['Address'],
			'Protocol'      => $Settings['Protocol'],
			'Hidden'        => $authinfo,
			'IsLogging'     => $Settings['Params']['IsLogging']
			);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $HTTP;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------




?>
