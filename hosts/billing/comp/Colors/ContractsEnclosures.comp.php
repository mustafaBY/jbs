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
  case 'Complite':
    $Color = 'D5F66C';
  break;
  case 'Breaked':
    $Color = 'DCDCDC';
  break;
  default:
    $Color = '999999';
}
#-------------------------------------------------------------------------------
return Array('bgcolor'=>SPrintF('#%s',$Color));
#-------------------------------------------------------------------------------

?>
