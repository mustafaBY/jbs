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
if(!Count($Args))
	return 'No args...';
#-------------------------------------------------------------------------------
$ArgsIDs = Array('command','bill_id','status','error','amount','user','prv_name','ccy','comment');
#-------------------------------------------------------------------------------
foreach($ArgsIDs as $ArgID)
	$Args[$ArgID] = @$Args[$ArgID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Header('Content-type: text/xml; charset=utf-8');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['QIWI'];
#-------------------------------------------------------------------------------
#foreach(Array_Keys($_SERVER) as $Key)
#	Debug(SPrintF('[comp/www/Merchant/QIWI]: %s => %s',$Key,$_SERVER[$Key]));
if($Settings['Send']['from'] != $_SERVER['PHP_AUTH_USER']){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: не совпадает номер магазина %s != %s',$Settings['Send']['from'],$_SERVER['PHP_AUTH_USER']));
	#-------------------------------------------------------------------------------
	return ERROR | @Trigger_Error(700);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Settings['QIWI_REST_Hash'] != $_SERVER['PHP_AUTH_PW']){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: не совпадает пароль REST, прислано значение = %s',$_SERVER['PHP_AUTH_PW']));
	#-------------------------------------------------------------------------------
	return ERROR | @Trigger_Error(700);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Preg_Match('/TEST/', $Args['bill_id'])){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: тестовый запрос, bill_id = %s, status = %s; amount = %s',$Args['bill_id'],$Args['status'],$Args['amount']));
	#-------------------------------------------------------------------------------
	return '<?xml version="1.0"?><result><result_code>0</result_code></result>';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем наличие такого счёта
$Invoice = DB_Select('Invoices',Array('ID','Summ','PaymentSystemID'),Array('UNIQ','ID'=>IntVal($Args['bill_id'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Invoice)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: счёт не найден'));
	return '<?xml version="1.0"?><result><result_code>5</result_code></result>';
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Invoice['PaymentSystemID'] != 'QIWI'){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: платёжная система изменилась: %s',$Invoice['PaymentSystemID']));
	#-------------------------------------------------------------------------------
	return '<?xml version="1.0"?><result><result_code>5</result_code></result>';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Users/Init',100);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$InvoiceID = $Invoice['ID'];
#-------------------------------------------------------------------------------
$IsStatusSet = TRUE;
#-------------------------------------------------------------------------------
$StatusID = 'Rejected';
#-------------------------------------------------------------------------------
$Comment = 'Счёт отклонён QIWI';
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/www/Merchant/QIWI]: status = %s',$Args['status']));
#-------------------------------------------------------------------------------
switch($Args['status']){
case 'paid':
	#-------------------------------------------------------------------------------
	# проверяем сумму
	if(Round($Invoice['Summ']/$Settings['Course'],2) != $Args['amount'])
		return ERROR | @Trigger_Error('[comp/Merchant/QIWI]: проверка суммы платежа завершилась неудачей');
	#-------------------------------------------------------------------------------
	$StatusID = 'Payed';
	$Comment = 'Автоматическое зачисление';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'expired':
	#-------------------------------------------------------------------------------
	$Comment = 'Время жизни счета истекло. Счёт не оплачен.';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'unpaid':
	#-------------------------------------------------------------------------------
	$Comment = 'Ошибка при проведении оплаты. Счёт не оплачен.';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'rejected':
	#-------------------------------------------------------------------------------
	$Comment = 'Счёт отклонен в QIWI';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/QIWI]: необрабатываемый статус = %s',$Args['status']));
	#-------------------------------------------------------------------------------
	$IsStatusSet = FALSE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($IsStatusSet){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Invoices','StatusID'=>$StatusID,'RowsIDs'=>$InvoiceID,'Comment'=>$Comment));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Comp)){
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
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return '<?xml version="1.0"?><result><result_code>0</result_code></result>';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
