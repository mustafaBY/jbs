<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Adding','TypeID','MessageID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if($MessageID){
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('class'=>'Note','id'=>$MessageID));
	#-------------------------------------------------------------------------------
	$Img = new Tag('IMG',Array('alt'=>'Закрыть','align'=>'center','height'=>10,'width'=>10,'src'=>'SRC:{Images/Icons/Close.gif}','border'=>0));
	#-------------------------------------------------------------------------------
	$Button = new Tag('BUTTON',Array('class'=>'Transparent','style'=>'cursor: pointer;','onclick'=>SPrintF('JavaScript: HideNote(\'%s\');',$MessageID)),$Img);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('DIV',Array('style'=>'text-align: right;'),$Button));
	#-------------------------------------------------------------------------------
	#$Span = new Tag('SPAN',Array('title'=>'Закрыть'),new Tag('B','x'));
	#-------------------------------------------------------------------------------
	#$Div->AddChild(new Tag('DIV',Array('style'=>'cursor: pointer; text-align: right;','OnClick'=>SPrintF('JavaScript: HideNote("%s");',$MessageID)),$Span));
	#-------------------------------------------------------------------------------
	$Div->{Is_Object($Adding)?'AddChild':'AddText'}($Adding);
	#-------------------------------------------------------------------------------
	return $Div;
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Td = new Tag('TD',Array('style'=>'padding:5px;'));
	#-------------------------------------------------------------------------------
	$Td->{Is_Object($Adding)?'AddChild':'AddText'}($Adding);
	#-------------------------------------------------------------------------------
	$Tr = new Tag('TR',$Td);
	#-------------------------------------------------------------------------------
	$Table = new Tag('TABLE',Array('class'=>$TypeID,'cellspacing'=>5,'align'=>'center'),$Tr);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Table;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
