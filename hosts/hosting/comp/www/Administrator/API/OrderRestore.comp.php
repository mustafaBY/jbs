<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Null($Args)){
	#-------------------------------------------------------------------------------
	if(Is_Error(System_Load('modules/Authorisation.mod')))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Args	= IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$OrderID= (integer) @$Args['OrderID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = SPrintF('`ID` = (SELECT `ServiceID` FROM `OrdersOwners` WHERE `ID` = %u )',$OrderID);
$Service = DB_Select('Services',Array('Code','NameShort'),Array('UNIQ','Where'=>$Where));
switch(ValueOf($Service)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#--------------------------------TRANSACTION------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID(SPrintF('%sOrderRestore',$Service['Code'])))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Order = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),Array('ID','OrderID','UserID','ContractID'),Array('UNIQ','Where'=>SPrintF('`OrderID` = %u',$OrderID)));
#-------------------------------------------------------------------------------
switch(ValueOf($Order)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OrderConsiders = DB_Select('OrdersConsider',Array('ID','DaysReserved','DaysRemainded','`DaysRemainded`*`Cost`*(1-`Discont`) as `SummRemainded`'),Array('Where'=>SPrintF('`OrderID` = %u AND `DaysRemainded` > 0',$Order['OrderID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($OrderConsiders)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	# ничё нету, значит надо собрать свой массив
	$OrderConsiders = Array(Array('ID'=>0,'DaysReserved'=>0,'DaysRemainded'=>0,'SummRemainded'=>0));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($OrderConsiders as $OrderConsider){
	#-------------------------------------------------------------------------------
	$SummRemainded = (double)$OrderConsider['SummRemainded'];
	#-------------------------------------------------------------------------------
	if($SummRemainded){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Order/Number',$Order['OrderID']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IsUpdate = Comp_Load('www/Administrator/API/PostingMake',Array('ContractID'=>$Order['ContractID'],'Summ'=>$SummRemainded,'ServiceID'=>3000,'Comment'=>SPrintF('Услуга "%s", #%s',$Service['NameShort'],$Comp)));
		#-------------------------------------------------------------------------------
		switch(ValueOf($IsUpdate)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#-------------------------------------------------------------------------------
			if($OrderConsider['ID'] != 0){
				#-------------------------------------------------------------------------------
				$IsUpdate = DB_Update('OrdersConsider',Array('DaysReserved'=>($OrderConsider['DaysReserved'] - $OrderConsider['DaysRemainded']),'DaysRemainded'=>0,'DaysConsidered'=>0),Array('ID'=>$OrderConsider['ID']));
				if(Is_Error($IsUpdate))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if($OrderConsider['ID'] != 0){
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Currency',$SummRemainded);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Event = Array(
					'UserID'        => $Order['UserID'],
					'PriorityID'    => 'Hosting',
					'Text'          => SPrintF('Осуществлён возврат средств за заказ (#%u), услуга (%s), сумма (%s)',$OrderID,$Service['NameShort'],$Comp)
					);
			$Event = Comp_Load('Events/EventInsert',$Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(Is_Error(DB_Commit($TransactionID)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#---------------------------END TRANSACTION-------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
