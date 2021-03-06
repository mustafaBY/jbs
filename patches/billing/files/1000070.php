<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/

#-------------------------------------------------------------------------------
# перебираем все контакты, обновляем их настройки в таблице уведоммлений
$Contacts = DB_Select('Contacts',Array('ID','UserID','MethodID'),Array('SortOn'=>'ID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Contacts)){
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
foreach($Contacts as $Contact){
	#-------------------------------------------------------------------------------
	// обновляем таблицу уведомлений, на этом этапе у юзера максимум по одному контакту каждого типа, несложно
	$IsUpdate = DB_Update('Notifies',Array('ContactID'=>$Contact['ID']),Array('Where'=>SPrintF('`UserID` = %u AND MethodID = "%s"',$Contact['UserID'],$Contact['MethodID'])));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// удаляем все настройки уведомлений с ContactID = 1, это должен быть системный пользователь, нечего там ему уведомлять
$IsDelete = DB_Delete('Notifies',Array('Where'=>'`ContactID` = 1'));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// удаляем внешний ключ
$Result = DB_Query('ALTER TABLE `Notifies` DROP FOREIGN KEY `NotifiesUserID`');
if(Is_Error($Result))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// удаляем столбец таблицы
$Result = DB_Query('ALTER TABLE `Notifies` DROP `UserID`');
if(Is_Error($Result))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// удаляем столбец таблицы
$Result = DB_Query('ALTER TABLE `Notifies` DROP `MethodID`');
if(Is_Error($Result))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsQuery = DB_Query('ALTER TABLE `Notifies` ADD CONSTRAINT `NotifiesContactID` FOREIGN KEY (`ContactID`) REFERENCES `Contacts` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;');
if(Is_Error($IsQuery))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
