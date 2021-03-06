<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['WithdrawalOldUsersSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# запускаемся только один раз в месяц, 1 числа
if(Date('d') != '01'){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/GC/WithdrawalOldUsers]: задача запускается раз в месяц, не в этот день'));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// выбираем неактивных пользователей
$Where = Array(
			/* идентификатор больше 2000 - ниже, тока у системных */
			'`ID` > 2000',
			/* не защищённый */
			'`IsProtected` = "no"',
			/* не входил в биллинг больше чем настроено (год + месяц, по дефолту) */
			SPrintF('`EnterDate` < UNIX_TIMESTAMP() - %u * 24 * 3600',$Settings['InactiveDaysForUser']),
			/* зарегистрированный более этого же времени назад */
			SPrintF('`RegisterDate` < UNIX_TIMESTAMP() - %u * 24 * 3600',$Settings['InactiveDaysForUser']),
			/* нет заказов */
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `UserID` = `Users`.`ID`) = 0',
			/* нет выписанных счетов на оплату */
			# закомментировано 2015-12-25 в 00:01 - непонял, с какой целью я ввёл это условие...
			#'(SELECT COUNT(*) FROM `InvoicesOwners` WHERE `UserID` = `Users`.`ID`) = 0',
			/* есть договора с баллансом больше нуля */
			'(SELECT SUM(`Balance`) FROM `ContractsOwners` WHERE `UserID` = `Users`.`ID`) > 0',
			/* нет свежих постов в тикетницу */
			SPrintF('(SELECT MAX(`CreateDate`) FROM `EdesksMessagesOwners` WHERE `UserID` = `Users`.`ID`) < UNIX_TIMESTAMP() - %u * 24 * 3600 OR (SELECT MAX(`CreateDate`) FROM `EdesksMessagesOwners` WHERE `UserID` = `Users`.`ID`) IS NULL',$Settings['InactiveDaysForUser'],$Settings['InactiveDaysForUser']),
		);
#-------------------------------------------------------------------------------
$Users = DB_Select('Users', Array('ID','Email','Name','EnterDate','RegisterDate'),Array('Where'=>$Where));
switch(ValueOf($Users)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return TRUE;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
// перебираем полученных пользователей.
foreach($Users as $User){
	#-------------------------------------------------------------------------------
	# убираем рефералов юзера, т.к. некоторым начислется от них, потом этим таском списывается...
	$IsUpdate = DB_Update('Users',Array('OwnerID'=>NULL,'IsManaged'=>FALSE),Array('ID'=>$User['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# выбираем меньшую из дат, т.к. мог не входить никогда
	$EnterDate = (($User['EnterDate'] > $User['RegisterDate'])?$User['EnterDate']:$User['RegisterDate']);
	Debug(SPrintF('[comp/Tasks/GC/WithdrawalOldUsers]: автоматическое списание денег с балланса юзера (%s) не заходившего в биллинг %s дней',$User['Email'],Ceil((Time() - $EnterDate)/(24*3600))));
	#-------------------------------------------------------------------------------
	#if($User['Email'] != 'mgolub@moskb.ru')
	#	continue;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# выбираем договора юзера с баллансом больше нуля
	$Where = Array(SPrintF('`UserID` = %u',$User['ID']),'`Balance` > 0');
	#-------------------------------------------------------------------------------
	$Contract = DB_Select('Contracts',Array('ID','TypeID','Customer','Balance'),Array('UNIQ','Where'=>$Where,'Limits'=>Array(0,1)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contract)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('CONTRACT_NOT_FOUND','Договора не найдены');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/GC/WithdrawalOldUsers]: юзер (%s), договор #%u, балланс %s',$User['Email'],$Contract['ID'],$Contract['Balance']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Settings['WithdrawSumm'] = Str_Replace(',','.',$Settings['WithdrawSumm']);
	#-------------------------------------------------------------------------------
	$Summ = ($Contract['Balance'] > $Settings['WithdrawSumm'])?$Settings['WithdrawSumm']:$Contract['Balance'];
	#-------------------------------------------------------------------------------
	$IsUpdate = Comp_Load('www/Administrator/API/PostingMake',Array('ContractID'=>$Contract['ID'],'Summ'=>-$Summ,'ServiceID'=>2100,'Comment'=>SPrintF('Хранение клиентской информации за период %s',Date('Y/m',MkTime(4,0,0,Date('n')-1,5,Date('Y'))))));
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsUpdate)){
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
	if(!$Settings['IsEvent'])
		continue;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Summ = Comp_Load('Formats/Currency',$Summ);
	if(Is_Error($Summ))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$User['ID'],'PriorityID'=>'Billing','IsReaded'=>FALSE,'Text'=>SPrintF('Автоматическое списание средств (%s) у неактивного пользователя',$Summ));
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
