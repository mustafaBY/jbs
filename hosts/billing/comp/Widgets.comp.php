<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Interface','Width','Height');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(!IsSet($GLOBALS['__USER']))
	return new Tag('NOBODY','User not authorisated...');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# cache added by lissyara, 2011-10-03 in 21:08 MSK
$CacheID = Md5($__FILE__ . $GLOBALS['__USER']['ID']);
#-------------------------------------------------------------------------------
$Result = CacheManager::get($CacheID);
if($Result)
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$HostsIDs = Array_Reverse($GLOBALS['HOST_CONF']['HostsIDs']);
#------------------------------------------------------------------------------- 
$Td = new Tag('TD',Array('valign'=>'top'));
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
	#-------------------------------------------------------------------------------
	$Folder = SPrintF('%s/hosts/%s/comp/Widgets/%s',SYSTEM_PATH,$HostID,$Interface);
	#-------------------------------------------------------------------------------
	if(!File_Exists($Folder))
		continue;
	#-------------------------------------------------------------------------------
	$Files = IO_Scan($Folder);
	if(Is_Error($Files))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	foreach($Files as $File){
		#-------------------------------------------------------------------------------
		$WidgetID = SubStr($File,0,StriPos($File,'.'));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(SPrintF('Widgets/%s/%s',$Interface,$WidgetID));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(Is_Array($Comp)){
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Widget',Md5($WidgetID),$Comp['Title'],$Comp['DOM']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Td->AddChild($Comp);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Count($Td->Childs))
	$Td->AddChild(new Tag('SPAN',Array('style'=>'color:#848484;'),'Виджеты не загружены. Виджет - примитивы графического интерфейса пользователя, имеющие стандартный внешний вид и выполняющие стандартные действия. Виджеты будут автоматически загружаться по мере появления информации в БД.'));
#-------------------------------------------------------------------------------
$Out = new Tag('TABLE',Array('class'=>'Standard','style'=>'background-image:url(SRC:{Images/Grid.png});','width'=>$Width,'height'=>$Height),new Tag('TR',new Tag('TD',Array('height'=>'20px','style'=>'font-size:11px;color:#DCDCDC;'),'Рабочая область')),new Tag('TR',$Td));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
CacheManager::add($CacheID,$Out,($Interface == 'User')?300:30);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
