<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IP','ExtraIP','Length');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$IPs = ($ExtraIP?Explode("\n",$ExtraIP):Array());
#-------------------------------------------------------------------------------
$IPs[] = $IP;
#-------------------------------------------------------------------------------
$IPs = Array_Unique($IPs);
#-------------------------------------------------------------------------------
$Count = Count($IPs);
#-------------------------------------------------------------------------------
if($Count > 10)
	$IPs = Array_Slice($IPs,0,9);
#-------------------------------------------------------------------------------
if(Mb_StrLen($IP) > $Length)
	$IP = SPrintF('%s...',Mb_SubStr($IP,0,$Length));
#-------------------------------------------------------------------------------
$A = new Tag('A',Array('target'=>'blank','href'=>SPrintF('http://%s/',$IP)),$IP);
#-------------------------------------------------------------------------------
$LinkID = UniqID('IPs');
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links[$LinkID] = &$A;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Prompt',$LinkID,SPrintF('<B>Всего: %u</B><BR />%s',$Count,Implode('<BR />',$IPs)));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
UnSet($Links[$LinkID]);
#-------------------------------------------------------------------------------
return $A;
#-------------------------------------------------------------------------------

?>
