<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['Contact']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Jabber]: отправка Jabber сообщения для (%s)', $Address));
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/JabberClient.class.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Jabber');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Jabber, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// добавляем привествие, если необходимо
if($Config['Notifies']['Methods']['Jabber']['Greeting'])
	$Message = SPrintF("%s\n\n%s",SPrintF(Trim($Config['Notifies']['Methods']['Jabber']['Greeting']),$Attribs['UserName']),Trim($Message));
#-------------------------------------------------------------------------------
// добавляем подпись, если необходимо
if(!$Config['Notifies']['Methods']['Jabber']['CutSign'])
	$Message = SPrintF("%s\n\n--\n%s",Trim($Message),$GLOBALS['__USER']['Sign']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
$LinkID = Md5('JabberClient');
#-------------------------------------------------------------------------------
if(!IsSet($Links[$LinkID])) {
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = NULL;
	#-------------------------------------------------------------------------------
	$JabberClient = &$Links[$LinkID];
	#-------------------------------------------------------------------------------
	$JabberClient = new JabberClient($Settings['Address'],$Settings['Port'],$Settings['Login'],$Settings['Password'],($Settings['Protocol'] == 'ssl')?TRUE:FALSE);
	#-------------------------------------------------------------------------------
	# TODO тут надо переделать, ошибки из функций не вернутся
	#Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
	if(Is_Error($JabberClient))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IsConnect = $JabberClient->connect();
	#Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
	if(Is_Error($IsConnect))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IsLogin = $JabberClient->login();
	#Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
	if(Is_Error($IsLogin))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$JabberClient = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsMessage = $JabberClient->send_message($Address,$Message,$Attribs['Theme']);
if(Is_Error($IsMessage)){
	#-------------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/Jabber]: error sending message, error is "%s"',$JabberClient->get_log()));
	#-------------------------------------------------------------------------------
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Jabber']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('Сообщение для (%s) через службу Jabber отправлено', $Address)));
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
