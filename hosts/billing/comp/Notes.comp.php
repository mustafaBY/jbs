<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Interface');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(IsSet($GLOBALS['__USER'])){
	#-------------------------------------------------------------------------------
	$Span = new Tag('SPAN',Array('style'=>'margin-bottom: 5px; width: 100%'));
	#-------------------------------------------------------------------------------
	$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
	#-------------------------------------------------------------------------------
	foreach($HostsIDs as $HostID){
		#-------------------------------------------------------------------------------
		$Folder = SPrintF('%s/hosts/%s/comp/Notes/%s',SYSTEM_PATH,$HostID,$Interface);
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
			$Path = SPrintF('Notes/%s/%s',$Interface,SubStr($File,0,StriPos($File,'.')));
			#-------------------------------------------------------------------------------
			$CacheID = Md5($Path . $GLOBALS['__USER']['ID']);
			#-------------------------------------------------------------------------------
			$Result = CacheManager::get($CacheID);
			#-------------------------------------------------------------------------------
			if($Result){
				#-------------------------------------------------------------------------------
				$Notes = $Result;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$Notes = Comp_Load($Path);
				if(Is_Error($Notes))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				CacheManager::add($CacheID,$Notes,60);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			foreach($Notes as $Note){
				#-------------------------------------------------------------------------------
				$MessageID = SPrintF('note_%s_%s',$GLOBALS['__USER']['ID'],SubStr(Md5(JSON_Encode($Note)),0,6));
				#-------------------------------------------------------------------------------
				if(IsSet($_COOKIE[$MessageID]))
					continue;
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Information',$Note,'Warning',$MessageID);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Span->AddChild($Comp);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Span->Childs = Array_Reverse($Span->Childs);
	#-------------------------------------------------------------------------------
	$Out = (Count($Span->Childs)?$Span:new Tag('NOBODY','No nodes...'));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return new Tag('NOBODY','User not authorisated...');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
