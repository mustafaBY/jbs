<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('File');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Mime = StrToLower(SubStr($File['Name'],StrRiPos($File['Name'],'.')+1));
#-------------------------------------------------------------------------------
$Element = SPrintF('Images/Mime/%s.gif',$Mime);
#-------------------------------------------------------------------------------
if(Is_Error(Styles_Element($Element)))
	$Element = 'Images/Mime/unknown.gif';
#-------------------------------------------------------------------------------
$Img = new Tag('IMG',Array('title'=>'комментарий к файлу','border'=>0,'width'=>48,'height'=>48,'src'=>SPrintF('SRC:{%s}',$Element)));
#-------------------------------------------------------------------------------
$Table = new Tag('TABLE',new Tag('TR',new Tag('TD',Array('align'=>'center'),new Tag('A',Array('class'=>'Image','href'=>SPrintF('/FileDownload?FileID=%u',$File['ID'])),$Img))));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String',$File['Name'],10);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table->AddChild(new Tag('TR',new Tag('TD',Array('align'=>'center'),new Tag('FONT',Array('size'=>'1'),$Comp))));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Table;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
