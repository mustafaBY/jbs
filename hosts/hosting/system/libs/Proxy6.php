<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Get_Users($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send(SPrintF('/api/%s/getproxy/',$Settings['Params']['Token']),$HTTP,Array());
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	$Users = $Doc['list'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SprintF('[system/libs/Proxy6.php]: Users = %s',print_r($Users,true)));
	#-------------------------------------------------------------------------------
	$Result = Array();
	#-------------------------------------------------------------------------------
	foreach($Users as $User){
		#-------------------------------------------------------------------------------
		if(!IsSet($User['user']))
			continue;
		#-------------------------------------------------------------------------------
		if(!IsSet($User['descr']))
			continue;
		#-------------------------------------------------------------------------------
		$Result[] = $User['user'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[system/libs/Proxy6.php]: Result = %s',print_r($Result,true)));
	return $Result;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Create($Settings,$ProxyScheme,$ProxyOrder,$DaysRemainded){
	/******************************************************************************/
	$__args_types = Array('array','array','array','integer');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($ProxyScheme['IPtype'] == 'IPv4'){
		#-------------------------------------------------------------------------------
		$Version = 4;
		#-------------------------------------------------------------------------------
	}elseif($ProxyScheme['IPtype'] == 'IPv4shared'){
		#-------------------------------------------------------------------------------
		$Version = 3;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Version = 6;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	// примечание
	$Descr = SPrintF('Order%u',$ProxyOrder['OrderID']);
	#-------------------------------------------------------------------------------
	# проверяем, нету ли юзера с таким примечанием
	$Response = HTTP_Send(SPrintF('/api/%s/getproxy/',$Settings['Params']['Token']),$HTTP,Array('state'=>'all','descr'=>$Descr));
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['list'])){
		#-------------------------------------------------------------------------------
		# если юзер есть, то возвращаем его данные
		foreach($Doc['list'] as $Proxy)
			if($Proxy['descr'] == $Descr)
				return $Proxy;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если юзера нет, создаём его
	$Request = Array(
			'count'		=> 1,
			'period'	=> $DaysRemainded,
			'country'	=> $ProxyScheme['Country'],
			'version'	=> $Version,
			'type'		=> 'https',
			'descr'		=> $Descr
			);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send(SPrintF('/api/%s/buy/',$Settings['Params']['Token']),$HTTP,$Request);
	if(Is_Error($Response))
		return new gException('NOT_CONNECTED_TO_SERVER','Не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	foreach($Doc['list'] as $Proxy)
		if(IsSet($Proxy['type']))
			return $Proxy;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return new gException('COULD_NOT_FIND_PROXY_IN_SERVER_ANSWER','Не удалось найти заказанный прокси в ответе сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Active($Settings,$ProxyOrder,$DaysRemainded){
	/******************************************************************************/
	$__args_types = Array('array','array','integer');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	// примечание
	$Descr = SPrintF('Order%u',$ProxyOrder['OrderID']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// достаём идентфикатор прокси
	$Response = HTTP_Send(SPrintF('/api/%s/getproxy/',$Settings['Params']['Token']),$HTTP,Array('nokey'=>1,'descr'=>$Descr));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Active]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	$Users = $Doc['list'];
	#-------------------------------------------------------------------------------
	foreach($Users as $User)
		if($User['descr'] == $Descr)
			$ID = $User['id'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!IsSet($ID))
		return new gException('CANNOT_FIND_PROXY_ORDER_ID','Не удалось найти идентификатор прокси-сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// продлеваем на запроешенный период
	$Response = HTTP_Send(SPrintF('/api/%s/prolong/',$Settings['Params']['Token']),$HTTP,Array('nokey'=>1,'ids'=>$ID,'period'=>$DaysRemainded));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Active]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Suspend($Settings,$Login,$IsReseller = FALSE){
	/******************************************************************************/
	$__args_types = Array('array','string','boolean');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>$IsReseller?'reseller.suspend':'user.suspend','elid'=>$Login));
	#-------------------------------------------------------------------------------
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Suspend]: не удалось соедениться с сервером');
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
function Proxy6_Delete($Settings,$OrderID){
	/******************************************************************************/
	$__args_types = Array('array','integer');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	// примечание
	$Descr = SPrintF('Order%u',$OrderID);
	#-------------------------------------------------------------------------------
	// достаём идентфикатор прокси
	$Response = HTTP_Send(SPrintF('/api/%s/getproxy/',$Settings['Params']['Token']),$HTTP,Array('nokey'=>1,'descr'=>$Descr));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Settings_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	$Users = $Doc['list'];
	#-------------------------------------------------------------------------------
	foreach($Users as $User)
		if($User['descr'] == $Descr)
			$ID = $User['id'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// если прокся не найдена - она уже удалена
	if(!IsSet($ID))
		return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send(SPrintF('/api/%s/delete/',$Settings['Params']['Token']),$HTTP,Array('ids'=>$ID));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Settings_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Settings_Change($Settings,$OrderID,$ProtocolType){
	/******************************************************************************/
	$__args_types = Array('array','integer','string');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Proxy6_Build_HTTP($Settings);
	#-------------------------------------------------------------------------------
	// примечание
	$Descr = SPrintF('Order%u',$OrderID);
	#-------------------------------------------------------------------------------
	// достаём идентфикатор прокси
	$Response = HTTP_Send(SPrintF('/api/%s/getproxy/',$Settings['Params']['Token']),$HTTP,Array('nokey'=>1,'descr'=>$Descr));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Settings_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-------------------------------------------------------------------------------
	$Users = $Doc['list'];
	#-------------------------------------------------------------------------------
	foreach($Users as $User)
		if($User['descr'] == $Descr)
			$ID = $User['id'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!IsSet($ID))
		return new gException('CANNOT_FIND_PROXY_ORDER_ID','Не удалось найти идентификатор прокси-сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send(SPrintF('/api/%s/settype/',$Settings['Params']['Token']),$HTTP,Array('ids'=>$ID,'type'=>$ProtocolType));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Settings_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes'){
		#-------------------------------------------------------------------------------
		// возможно, уже стоит этот протокол
		if($Doc['error_id'] == 30)
			return TRUE;
		#-------------------------------------------------------------------------------
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Password_Change($Settings,$Login,$Password,$Params){
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
			'passwd'	=> $Password,
			'confirm'	=> $Password,
			);
	#-------------------------------------------------------------------------------
	$Response = HTTP_Send('/dnsmgr',$HTTP,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[Proxy6_Password_Change]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error('[Proxy6_Password_Change]: неверный ответ от сервера');
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
function Proxy6_Get_Balance($Settings){
        /****************************************************************************/
        $__args_types = Array('array');
        #-----------------------------------------------------------------------------
        $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
        /****************************************************************************/
        $HTTP = Proxy6_Build_HTTP($Settings);
        #-----------------------------------------------------------------------------
        $Response = HTTP_Send(SPrintF('/api/%s/getcountry/',$Settings['Params']['Token']),$HTTP,Array('version'=>4));
        if(Is_Error($Response))
                return ERROR | @Trigger_Error('[Proxy6_Get_Balance]: не удалось соедениться с сервером');
        #-----------------------------------------------------------------------------
        $Response = Trim($Response['Body']);
	#-----------------------------------------------------------------------------
	$Doc = Json_Decode($Response,true);
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['status']) || $Doc['status'] != 'yes')
		return new gException('SERVER_RETURN_ERROR','Сервер вернул ошибку');
	#-----------------------------------------------------------------------------
	if(!IsSet($Doc['balance']) || !IsSet($Doc['currency']))
                return new gException('[Proxy6_Get_Balance]','Не удалось получить баланс аккаунта');
        #---------------------------------------------------------------------------
        #---------------------------------------------------------------------------
        return Array('balance'=>$Doc['balance'],'currency'=>$Doc['currency']);
        #---------------------------------------------------------------------------
}



# внутренние функции
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function Proxy6_Build_HTTP($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Array(
			'Address'       => $Settings['Address'],
			'Port'          => $Settings['Port'],
			'Host'          => $Settings['Address'],
			'Protocol'      => $Settings['Protocol'],
			'Hidden'        => $Settings['Params']['Token'],
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
