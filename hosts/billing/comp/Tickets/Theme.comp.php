<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('TicketID','Theme','Length');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Comp = Comp_Load('Formats/String',HtmlSpecialChars($Theme),$Length);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $TextDiv = new Tag('DIV',Array('style'=>'overflow:hidden; cursor:pointer;','onclick'=>SPrintF("ShowWindow('/TicketRead',{TicketID:%u});",$TicketID)),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
