<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
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
Debug(SPrintF('[comp/Tasks/ICQ]: отправка ICQ сообщения для (%u)',$Address));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['ICQ']['IsActive']){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/ICQ]: уведомления через ICQ отключены'));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/WebIcqLite.class.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('ICQ');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: ICQ, params: IsActive, IsDefault not found';
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
if($Config['Notifies']['Methods']['ICQ']['Greeting'])
	$Message = SPrintF("%s\n\n%s",SPrintF(Trim($Config['Notifies']['Methods']['ICQ']['Greeting']),$Attribs['UserName']),Trim($Message));
#-------------------------------------------------------------------------------
// добавляем подпись, если необходимо
if(!$Config['Notifies']['Methods']['ICQ']['CutSign'])
	$Message = SPrintF("%s\n\n--\n%s",Trim($Message),$GLOBALS['__USER']['Sign']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5('WebIcqLite');
#-------------------------------------------------------------------------------
if(!IsSet($Links[$LinkID])){
  #-----------------------------------------------------------------------------
  $Links[$LinkID] = NULL;
  #-----------------------------------------------------------------------------
  $WebIcqLite = &$Links[$LinkID];
  #-----------------------------------------------------------------------------
  $WebIcqLite = new WebIcqLite();
  if(Is_Error($WebIcqLite))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $MasterUin = Explode(',',$Settings['Login']);
  #-----------------------------------------------------------------------------
  $MasterUin = $MasterUin[Rand(0,Count($MasterUin)-1)];
  #-----------------------------------------------------------------------------
  $MasterUin = (integer)$MasterUin;
  #-----------------------------------------------------------------------------
  Debug(SPrintF('Текущий Address: %u',$MasterUin));
  #-----------------------------------------------------------------------------
  $IsLogin = $WebIcqLite->connect($MasterUin,$Settings['Password']);
  #-----------------------------------------------------------------------------
  switch(ValueOf($IsLogin)){
    case 'error':
      #-------------------------------------------------------------------------
      Debug("[comp/Tasks/ICQ]: error when login,  error is '" . $WebIcqLite->error . "'");
      #-------------------------------------------------------------------------
      return 300;
    case 'false':
      #-------------------------------------------------------------------------
      Debug("[comp/Tasks/ICQ]: login return false, error is '" . $WebIcqLite->error . "'");
      UnSet($Links[$LinkID]);
      #-------------------------------------------------------------------------
      return 3600;
    case 'true':
      # No more...
      Debug("[comp/Tasks/ICQ]: Connect to ICQ service is OK");
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$WebIcqLite = &$Links[$LinkID];
#-------------------------------------------------------------------------------
$Message = Mb_Convert_Encoding($Message,$Settings['Params']['Encoding']);
# переводы строк
$Message = Str_Replace("\r","",$Message);
$Message = Str_Replace("\n","\n\r",$Message);
#-------------------------------------------------------------------------------
$IsMessage = $WebIcqLite->send_message((integer)$Address,$Message);
if(Is_Error($IsMessage)){
  #-----------------------------------------------------------------------------
  UnSet($Links[$LinkID]);
  #-----------------------------------------------------------------------------
  Debug("[comp/Tasks/ICQ]: error sending message, error is '" . $WebIcqLite->error . "'");
  #-----------------------------------------------------------------------------
  return 3600;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['ICQ']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
$Event = Array(
		'UserID'	=> $Attribs['UserID'],
		'Text'		=> SPrintF('Сообщение для (%u) через службу ICQ отправлено',$Address)
		);
$Event = Comp_Load('Events/EventInsert',$Event);
#-------------------------------------------------------------------------------
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
