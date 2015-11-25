<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','UserID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/

if($UserID){
	#-------------------------------------------------------------------------------
	$Users = DB_Select('Users',Array('ID','Email','Params'),Array('ID'=>$UserID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Users)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Users = DB_Select('Users',Array('ID','Email','Params'),Array('Limits'=>Array('Start'=>$i,'Length'=>100)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Users)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		break;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Template = System_XML('xml/Params/Users.xml');
if(Is_Error($Template))
	return new gException('ERROR_TEMPLATE_LOAD','Ошибка загрузки шаблона');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Users as $User){
	#-------------------------------------------------------------------------------
	$Attribs = @$User['Params']['Settings'];
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Template['Settings']) as $AttribID)
		if(!IsSet($Attribs[$AttribID]))
			$Attribs[$AttribID] = $Template['Settings'][$AttribID]['Value'];
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Attribs) as $AttribID)
		if(!IsSet($Template['Settings'][$AttribID]))
			UnSet($Attribs[$AttribID]);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$NotificationMethods = @$User['Params']['NotificationMethods'];
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Template['NotificationMethods']) as $Key){
		#-------------------------------------------------------------------------------
		if(!IsSet($NotificationMethods[$Key]))
			$NotificationMethods[$Key] = Array();
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Template['NotificationMethods'][$Key]) as $Value)
			if(!IsSet($NotificationMethods[$Key][$Value]))
				$NotificationMethods[$Key][$Value] = $Template['NotificationMethods'][$Key][$Value];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($NotificationMethods) as $Key)
		if(!IsSet($Template['NotificationMethods'][$Key]))
			UnSet($NotificationMethods[$Key]);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Params = Array();
	#-------------------------------------------------------------------------------
	$Params['Settings'] = $Attribs;
	#-------------------------------------------------------------------------------
	$Params['NotificationMethods'] = $NotificationMethods;
	#-------------------------------------------------------------------------------
	if(IsSet($User['Params']['IsAutoRegistered']) && $User['Params']['IsAutoRegistered']){
		#-------------------------------------------------------------------------------
		$Params['IsAutoRegistered'] = TRUE;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Params['IsAutoRegistered'] = FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Users',Array('Email'=>StrToLower($User['Email']),'Params'=>$Params),Array('ID'=>$User['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(SizeOf(Array_Keys($Users)) > 1){
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>100,'PriorityID'=>'Billing','Text'=>SPrintF('Успешно восстановлено %u пользователей',$Count));
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = SPrintF('Recovered: %u users',SizeOf(Array_Keys($Users)));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
