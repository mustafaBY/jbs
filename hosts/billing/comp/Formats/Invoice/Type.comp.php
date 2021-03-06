<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('PaymentSystemID','Length');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$PaymentSystems = $Config['Invoices']['PaymentSystems'];
#-------------------------------------------------------------------------------
// платёжная система может и не существовать уже
$Name = IsSet($PaymentSystems[$PaymentSystemID]['Name'])?$PaymentSystems[$PaymentSystemID]['Name']:SPrintF('%s [удалена]',$PaymentSystemID);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Is_Null($Length)){
	#-------------------------------------------------------------------------------
	$Name = Comp_Load('Formats/String',$Name,$Length);
	if(Is_Error($Name))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Name;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
