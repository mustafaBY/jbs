<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(!Comp_IsLoaded('Mp3Player')){
  #-----------------------------------------------------------------------------
  $Links = &Links();
  #-----------------------------------------------------------------------------
  $DOM = &$Links['DOM'];
  #-----------------------------------------------------------------------------
  $Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Mp3Player.js}'));
  #-----------------------------------------------------------------------------
  $DOM->AddChild('Head',$Script);
  #-----------------------------------------------------------------------------
  $Td = new Tag('TD',Array('id'=>'Mp3PlayerInto','valign'=>'top','style'=>'padding:0px;','width'=>200,'height'=>120),'Загрузка...');
  #-----------------------------------------------------------------------------
  $Table = new Tag('TABLE',Array('id'=>'Mp3Player','cellspacing'=>5,'style'=>'border:2px solid #DCDCDC;background-color:#FFFFFF;display:none;position:absolute;top:-1000;left:-1000;'),new Tag('TR',new Tag('TD',Array('align'=>'right','style'=>'font-size:11px;border-bottom:1px solid #DCDCDC;'),new Tag('A',Array('href'=>'javascript:Mp3PlayerHide();'),'[закрыть]'))),new Tag('TR',$Td));
  #-----------------------------------------------------------------------------
  $DOM->AddChild('Floating',$Table);
}
#-------------------------------------------------------------------------------

?>
