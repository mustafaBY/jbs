<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
$Settings = $Config['Tasks']['Types']['Taxation'];
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Taxation]: Settings = %s',print_r($Settings,true)));
#-------------------------------------------------------------------------------
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecutePeriod'=>$Settings['ExecutePeriod']));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
# если неактивна, то через день запуск
if(!$Settings['IsActive'])
	return 24*3600;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Taxation]: Config.Invoices.Kassa_54-FZ = %s',print_r($Config['Invoices']['Kassa_54-FZ'],true)));
#-------------------------------------------------------------------------------
# налоггобложение
$TaxationSystem = $Config['Invoices']['Kassa_54-FZ']['TaxationSystem'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, активен ли какой-то модуль для работы с электронной кассой
$CashBoxes = $Config['Invoices']['Kassa_54-FZ'];
#-------------------------------------------------------------------------------
foreach(Array_Keys($CashBoxes) as $Key){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/Taxation]: Key = %s',$Key));
	#-------------------------------------------------------------------------------
	if(!Is_Array($CashBoxes[$Key]))
		continue;
	#-------------------------------------------------------------------------------
	if(!$CashBoxes[$Key]['IsActive'])
		continue;
	#-------------------------------------------------------------------------------
	$KassaID = $Key;
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/Taxation]: CashBox = %s',print_r($CashBoxes[$Key],true)));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# если задача активна, но нет активных касс - пропускаем цикл
if(!IsSet($KassaID))
	return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# TODO Вообще, надо написать класс-надстройку, как с хостингами и ВПС - для разных касс и дёргать её.
# а уже из неё дёргать конкретную кассу... но, пока что так оставлю, пока только одна касса используется
#-------------------------------------------------------------------------------
$KassaSettings = $Config['Invoices']['Kassa_54-FZ'][$KassaID];
#-------------------------------------------------------------------------------
# а дальше идёт код для работы с самой касссой. а ндо с той самой насдстройкой из TODO
#-------------------------------------------------------------------------------
# https://github.com/Komtet/komtet-kassa-php-sdk
if(Is_Error(System_Load(SPrintF('classes/%s/Client.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/QueueManager.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// PSR-совместимый логгер (опциональный параметр)
$logger = null;
$client = new Client($KassaSettings['ShopId'], $KassaSettings['Hash'], $logger);
$manager = new QueueManager($client);
#-------------------------------------------------------------------------------
#После чего зарегистрировать очереди:
$manager->registerQueue(HOST_ID, $KassaSettings['QueueId']);
# и установить очередь по умолчанию
$manager->setDefaultQueue(HOST_ID);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# Отправка чека на печать:
if(Is_Error(System_Load(SPrintF('classes/%s/Agent.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Check.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Cashier.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Payment.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Position.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/TaxSystem.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Vat.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/CalculationSubject.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/AuthorisedPerson.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Correction.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/TaskManager.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/CalculationMethod.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/CorrectionCheck.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Exception/SdkException.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load(SPrintF('classes/%s/Exception/ClientException.php',$KassaID))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём все оплаченные счета, по которым не отправлен отчёт в налоговую
$Invoices = DB_Select('InvoicesOwners',Array('*','(SELECT `Email` FROM `Users` WHERE `Users`.`ID` = `UserID`) AS `Email`'),Array('Where'=>Array('`IsCheckSent` = "no"')));
switch(ValueOf($Invoices)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	return $ExecuteTime;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array('Invoices_OK'=>Array(),'Invoices_ERROR'=>Array());
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Invoices as $Invoice){
	#-------------------------------------------------------------------------------
	$Number = Comp_Load('Formats/Invoice/Number',$Invoice['ID']);
	if(Is_Error($Number))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Taxation]: обработка счёта = %s; Summ = %s',$Number,$Invoice['Summ']));
	#-------------------------------------------------------------------------------
	// уникальный ID, предоставляемый магазином
	$checkID = $Invoice['ID'];
	// E-Mail клиента, на который будет отправлен E-Mail с чеком.
	$Email = $Invoice['Email'];
	$Email = 'admin@lissyara.su';
	#-------------------------------------------------------------------------------
	$check = Check::createSell($checkID, $Email, TaxSystem($TaxationSystem)); // или Check::createSellReturn для оформления возврата
	// Говорим, что чек нужно распечатать
	$check->setShouldPrint(true);
	#-------------------------------------------------------------------------------
	$vat = new Vat(Vat::RATE_18);
	#-------------------------------------------------------------------------------
	# TODO: по уму, надо все позиции отображать:
	$InvoicesItems = DB_Select('InvoicesItems',Array('*','(SELECT `NameShort` FROM `Services` WHERE `InvoicesItems`.`ServiceID` = `ID`) AS `Name`'),Array('Where'=>Array(SPrintF('`InvoiceID` = %u',$Invoice['ID']))));
	switch(ValueOf($InvoicesItems)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		// Позиция в чеке: имя, цена, кол-во, общая стоимость, скидка, налог
		$position = new Position(SprintF('Оплата по счёту #%s',$Number), (float) $Invoice['Summ'], 1, (float) $Invoice['Summ'], 0, $vat);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		foreach($InvoicesItems as $Item){
			// Позиция в чеке: имя, цена, кол-во, общая стоимость, скидка, налог
			#-------------------------------------------------------------------------------
			$position = new Position(SprintF('%s%s',$Item['Name'],(StrLen($Item['Comment']) > 0)?SPrintF(' / %s',$Item['Comment']):''), (float) $Item['Summ'], 1, (float) $Item['Summ'], 0, $vat);
			#-------------------------------------------------------------------------------
			// Идентификатор позиции
			$position->setId($Item['ID']);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}

	// Агента по предмету расчета
	// $agent = new Agent(Agent::COMMISSIONAIRE, "+77777777777", "ООО 'Лютик'", "12345678901");
	// $position->setAgent($agent);

	$check->addPosition($position);

	// Итоговая сумма расчёта
	$payment = new Payment(Payment::TYPE_CARD, (float) $Invoice['Summ']);
	$check->addPayment($payment);

	// Добавление кассира (опционально)
	#$cashier = new Cashier('Иваров И.П.', '1234567890123');
	#$check->addCashier($cashier);
	
	#-------------------------------------------------------------------------------
	// Добавляем чек в очередь.
	try {
		#-------------------------------------------------------------------------------
		$manager->putCheck($check);
		#-------------------------------------------------------------------------------
	} catch (SdkException $e) {
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/Taxation]: Invoice = %s; getMessage = %s',$Number,$e->getMessage()));
		#-------------------------------------------------------------------------------
		$GLOBALS['TaskReturnInfo']['Invoices_ERROR'][] = $Number;
		#-------------------------------------------------------------------------------
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo']['Invoices_OK'][] = $Number;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Invoices',Array('IsCheckSent'=>TRUE),Array('ID'=>$Invoice['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Taxation]: TaskReturnInfo = %s',print_r($GLOBALS['TaskReturnInfo'],true)));
#-------------------------------------------------------------------------------
if(SizeOf($GLOBALS['TaskReturnInfo']['Invoices_OK']) < 1)
	UnSet($GLOBALS['TaskReturnInfo']['Invoices_OK']);
#-------------------------------------------------------------------------------
if(SizeOf($GLOBALS['TaskReturnInfo']['Invoices_ERROR']) < 1)
	UnSet($GLOBALS['TaskReturnInfo']['Invoices_ERROR']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
