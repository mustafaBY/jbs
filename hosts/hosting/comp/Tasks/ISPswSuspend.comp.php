<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','ISPswOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/BillManager.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Columns = Array(
			'*',
			'(SELECT `ProfileID` FROM `Contracts` WHERE `Contracts`.`ID` = `ISPswOrdersOwners`.`ContractID`) as `ProfileID`',
			'(SELECT `elid` FROM `ISPswLicenses` WHERE `ISPswOrdersOwners`.`LicenseID`=`ISPswLicenses`.`ID`) AS `elid`',
			'(SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `ISPswOrdersOwners`.`OrderID`) AS `ServerID`'
		);
$ISPswOrder = DB_Select('ISPswOrdersOwners',$Columns,Array('UNIQ','ID'=>$ISPswOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswOrder)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#-------------------------------------------------------------------------------
        $Server = DB_Select('Servers','*',Array('UNIQ','ID'=>$ISPswOrder['ServerID']));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Server)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		Debug(SPrintF('[comp/Tasks/ISPswActive]: found server: Address = %s; ID = %s',$Server['Address'],$Server['ID']));
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$ISPswScheme = DB_Select('ISPswSchemes','*',Array('UNIQ','ID'=>$ISPswOrder['SchemeID']));
	#-------------------------------------------------------------------------------
	switch(ValueOf($ISPswScheme)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$ISPswScheme['elid']     = $ISPswOrder['elid'];
		$ISPswScheme['LicenseID']= $ISPswOrder['LicenseID'];
		#-------------------------------------------------------------------------------
		# блокируем
		if(!BillManager_Lock($Server,$ISPswScheme))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Event = Array(
				'UserID'	=> $ISPswOrder['UserID'],
				'PriorityID'	=> 'Billing',
				'Text'		=> SPrintF('Заказ ПО ISPsystem (%s), IP адрес (%s) заблокирован',$ISPswScheme['Name'],$ISPswOrder['IP'])
				);
		$Event = Comp_Load('Events/EventInsert',$Event);
		if(!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$GLOBALS['TaskReturnInfo'] = Array($ISPswOrder['IP']=>Array($ISPswScheme['Name']));
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}	# end of ISPswScheme
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}  # end of ISPswOrder
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
