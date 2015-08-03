<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','UIN','Message','ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Tasks/ICQ]: отправка ICQ сообщения для (%u)',$UIN));
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
$GLOBALS['TaskReturnInfo'] = $UIN;
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
  Debug(SPrintF('Текущий UIN: %u',$MasterUin));
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
$IsMessage = $WebIcqLite->send_message((integer)$UIN,$Message);
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
		'UserID'	=> $ID,
		'Text'		=> SPrintF('Сообщение для (%u) через службу ICQ отправлено',$UIN)
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
