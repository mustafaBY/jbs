<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ModeID','Row');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array('Текущее состояние');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Status/Name',$ModeID,$Row['StatusID'],$Row['ID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result[] = Array('Статус',new Tag('TD',Array('class'=>'Standard'),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Extended',$Row['StatusDate']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result[] = Array('Статус установлен',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Remainder',Time() - $Row['StatusDate']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result[] = Array('От статуса',$Comp);
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------

?>
