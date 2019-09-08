<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$UsersIDs		=   (array) @$Args['UsersIDs'];
$MethodsIDs		=   (array) @$Args['MethodsIDs'];
$Logic     		=  (string) @$Args['Logic'];
$FromID    		= (integer) @$Args['FromID'];
$Theme     		=  (string) @$Args['Theme'];
$Message    		=  (string) @$Args['Message'];
$FiltersIDs 		=   (array) @$Args['FiltersIDs'];
$IsEmulateDisptch	= (boolean) @$Args['IsEmulateDisptch'];
$IsForceDelivery	= (boolean) @$Args['IsForceDelivery'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Theme)
	return new gException('THEME_IS_EMPTY','Введите тему сообщения');
#-------------------------------------------------------------------------------
if(!$Message)
	return new gException('MESSAGE_IS_EMPTY','Введите сообщение');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($UsersIDs as $UserID)
	$Array[] = (integer)$UserID;
#-------------------------------------------------------------------------------
$UsersIDs = $Array;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Methods = Array();
#-------------------------------------------------------------------------------
foreach($MethodsIDs as $Method)
	$Methods[] = $Method;
#-------------------------------------------------------------------------------
if(!Count($Methods))
	return new gException('METHODS_NOT_SELECTED','Методы рассылки не выбраны');
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: MethodsIDs = %s',Implode(',',$MethodsIDs)));
#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: FiltersIDs = %s',Implode(',',$FiltersIDs)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Dispatches = Array();
#-------------------------------------------------------------------------------
foreach($FiltersIDs as $FilterID){
	#-------------------------------------------------------------------------------
	$FilterID = Explode('|',$FilterID);
	#-------------------------------------------------------------------------------
	$DispatchID = Current($FilterID);
	#-------------------------------------------------------------------------------
	if(!IsSet($Dispatches[$DispatchID]))
		$Dispatches[$DispatchID] = Array();
	#-------------------------------------------------------------------------------
	$Dispatches[$DispatchID][] = Next($FilterID);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: Dispatches = %s',print_r($Dispatches,true)));
#-------------------------------------------------------------------------------
# счётчик фильтров, для условия $Logic = 'AND'
$Counter = 0;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach(Array_Keys($Dispatches) as $DispatchID){
	#-------------------------------------------------------------------------------
	$Result = Comp_Load(SPrintF('Dispatch/%s',$DispatchID),$Dispatches[$DispatchID]);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	foreach($Dispatches[$DispatchID] as $FilterID)
		if(IsSet($Result[$FilterID]))
			$UsersIDs = Array_Merge($UsersIDs,$Result[$FilterID]['UsersIDs']);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: into filters, UserIDs = %s',Implode(',',$UsersIDs)));
	#-------------------------------------------------------------------------------
	if(IsSet($Result['UsersIDs'])){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: into filters, $Result[UsersIDs] = %s',Implode(',',$Result['UsersIDs'])));
		#-------------------------------------------------------------------------------
		$UsersIDs = Array_Merge($UsersIDs,$Result['UsersIDs']);
		#-------------------------------------------------------------------------------
		$Counter++;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Counter = $Counter + SizeOf($Dispatches[$DispatchID]);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
switch($Logic){
case 'AND':
	#-------------------------------------------------------------------------------
	$Matches = Array_Count_Values($UsersIDs);
	#-------------------------------------------------------------------------------
	$UsersIDs = Array();
	#-------------------------------------------------------------------------------
	foreach($Matches as $UserID=>$Match){
		#-------------------------------------------------------------------------------
		if($Match == $Counter)
			$UsersIDs[] = $UserID;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'OR':
	#-------------------------------------------------------------------------------
	Array_Unique($UsersIDs);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return new gException('WRONG_LOGIC','Не верный способ объединения фильтров');
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Count($UsersIDs))
	return new gException('FILTERS_USERS_NOT_FOUND','С использованием фильтров ни один из пользователей для рассылки сообщений не найден');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Users = DB_Select('Users',Array('ID','Params'),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$UsersIDs))));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('USERS_NOT_FOUND','Пользователи для рассылки уведомлений не найдены');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Array = $Messages = Array();
#-------------------------------------------------------------------------------
# массив счётчиков по методам
foreach($Methods as $Method){
	#-------------------------------------------------------------------------------
	// считаем количество подвтерждённых контактов в базе, для выбранных юзеров
	$Where = Array(
			SPrintF('`UserID` IN (%s)',Implode(',',$UsersIDs)),
			SPrintF('`MethodID` = "%s"',$Method),
			'`IsActive` = "yes"'
			);
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Contacts',Array('Where'=>$Where));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Array[$Method] = $Count;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# массив счётчиков со значениями больше нуля
foreach(Array_Keys($Array) as $Key)
	if($Array[$Key])
		$Messages[$Key] = $Array[$Key];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('Users',Array('ID'=>$FromID));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count)
	return new gException('SENDER_NOT_FOUND','Отправитель сообщения не найден');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = 0;
#-------------------------------------------------------------------------------
$SendTo = Array();
#-------------------------------------------------------------------------------
foreach($Users as $User)
	$SendTo[] = $User['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Params = Array(Implode(',',$SendTo),$Theme,$Message,$FromID,'',Implode(',',$Methods),$IsForceDelivery);
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/API/Dispatch]: before send, UserIDs = %s',Implode(',',$UsersIDs)));
if(!$IsEmulateDisptch){
	#-------------------------------------------------------------------------------
	$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$GLOBALS['__USER']['ID'],'TypeID'=>'Dispatch','Params'=>$Params));
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsAdd)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		# No more...
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Messages['NotSend'] = "Это эмуляция";
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','Users'=>SizeOf($Users),'Messages'=>$Messages);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
