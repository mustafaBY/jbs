<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ExpirationDate');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
return Array('bgcolor'=>SPrintF('#%s',($ExpirationDate > Time())?'D5F66C':'DCDCDC'));
#-------------------------------------------------------------------------------

?>
