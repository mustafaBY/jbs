<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('StatusID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
switch($StatusID){
  case 'Waiting':
    $Color = 'F9E47D';
  break;
  case 'OnCreate':
    $Color = 'FFFF2F';
  break;
  case 'Active':
    $Color = 'D5F66C';
  break;
  case 'OnProlong':
    $Color = 'FFFF2F';
  break;
  case 'Suspended':
    $Color = 'FF6666';
  break;
  case 'Deleted':
    $Color = 'DCDCDC';
  break;
  default:
    $Color = '999999';
}
#-------------------------------------------------------------------------------
return Array('bgcolor'=>SPrintF('#%s',$Color));
#-------------------------------------------------------------------------------

?>