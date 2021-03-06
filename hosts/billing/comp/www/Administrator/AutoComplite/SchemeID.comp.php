<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ServiceID = (integer) @$Args['ServiceID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Result = Array();
$Status = 'Exception';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ServiceID == 0)
	return Array('Options'=>$Result,'Status'=>$Status);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём код сервиса
Debug("[comp/www/Administrator/AutoComplite/SchemeID]: ServiceID = " . $ServiceID);
$Service = DB_Select('ServicesOwners',Array('ID','`NameShort` AS `Name`','Code'),Array('UNIQ','ID'=>$ServiceID));
#-------------------------------------------------------------------------------
switch(ValueOf($Service)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_SERVICE','Выбранный сервис не найден');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Service['Code'] != 'Default'){
	$Schemes = DB_Select(SPrintF('%sSchemesOwners',$Service['Code']),Array('ID','Name','PackageID'),Array('SortOn'=>'SortID'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Schemes)){
	case 'error':
	return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('NO_RESULT','Тарифы не найдены');
	case 'array':
		foreach($Schemes as $Scheme)
			$Result[UniqID('ID')] = Array('Value'=>$Scheme['ID'],'Label'=>SPrintF('%s [%s]',$Scheme['Name'],$Scheme['PackageID']));
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(SizeOf($Result) > 0)
	$Status = 'Ok';
#-------------------------------------------------------------------------------
return Array('Options'=>$Result,'Status'=>$Status);

?>
