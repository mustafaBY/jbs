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
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$RowsIDs	= (array)  @$Args['RowsIDs'];
$TableID	= (string) @$Args['TableID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$TableID && !$RowsIDs)
	return new gException('NO_ORDERS','Необходимо выбрать хоть один заказ, который вы планируете оплачивать');
#-------------------------------------------------------------------------------
if(!$TableID)
	return ERROR | @Trigger_Error('[comp/www/OrdersPay]: Не задана таблица, невозможно определить сервис');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Services = DB_Select('Services',Array('ID','Code','Item','ConsiderTypeID'),Array('Where'=>'`Code` != "Default"'));
switch(ValueOf($Services)){
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
foreach($Services as $Service)
	if(Preg_Match(SPrintF('/^%s/',$Service['Code']),$TableID)) break;
#-------------------------------------------------------------------------------
#return new gException('SERVICE_ORDER_CAN_NOT_PAY','ServiceID = ' . $Service['ID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Оплата заказов услуги "%s"',$Service['Item']));
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'OrdersPayForm'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'ServiceID','type'=>'hidden','value'=>$Service['ID']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Table[] = 'Параметры оплаты';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Service['ConsiderTypeID'] == 'Daily'){
	#-------------------------------------------------------------------------------
	# с точностью решил не заморачиваться, 31 день в месяц и всё тут
	$Array = Array(1,2,3,6,9,12,24,36);
	#-------------------------------------------------------------------------------
	$Count = 31;
	#-------------------------------------------------------------------------------
	$Consider = 'мес.';
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Array = Array(1);
	#-------------------------------------------------------------------------------
	$Count = 1;
	#-------------------------------------------------------------------------------
	$Consider = 'лет';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Periods = Array();
#-------------------------------------------------------------------------------
foreach($Array as $Months)
	$Periods[($Months * $Count)] = SPrintF('%u %s',$Months,$Consider);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ItemsPay','style'=>'width:100%;'),$Periods,372);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Период оплаты',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# если на баллансе есть деньги - ставим галку, если нет - нет
$ContractsBalance = DB_Select('ContractsOwners',Array('SUM(`Balance`) AS `Balance`'),Array('UNIQ','Where'=>Array('`UserID` = @local.__USER_ID')));
switch(ValueOf($ContractsBalance)){
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
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'UseBalance','id'=>'UseBalance','value'=>'yes'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ContractsBalance['Balance'] > 0){
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('checked'=>'yes'));
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('disabled'=>'yes'));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('LABEL',Array('for'=>'UseBalance'),'Использовать средства с балланса'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Заказы для оплаты';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Statuses = $Config['Statuses'][SPrintF('%sOrders',$Service['Code'])];
#-------------------------------------------------------------------------------
$Orders = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),Array('*',SPrintF('(SELECT `Name` FROM `%sSchemes` WHERE `%sOrdersOwners`.`SchemeID` = `%sSchemes`.`ID`) as `SchemeName`',$Service['Code'],$Service['Code'],$Service['Code'])),Array('Where'=>Array('`UserID` = @local.__USER_ID','`StatusID` IN ("Active","Waiting","Suspended")')));
switch(ValueOf($Orders)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_ORDERS_FOR_PAY','Отсутствуют заказы которые можно оплатить');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/SelectIDs',SizeOf($Orders));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head'),$Comp));
#-------------------------------------------------------------------------------
foreach(Array('Номер','Тариф','Дата оконч.','Статус') as $Text)
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head'),$Text));
#-------------------------------------------------------------------------------
$Array = Array($Tr);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Orders as $Order){
	#-------------------------------------------------------------------------------
	$Tr = new Tag('TR');
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'RowsIDs[]','value'=>$Order['ID']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(In_Array($Order['ID'],$RowsIDs))
		$Comp->AddAttribs(Array('checked'=>'yes'));
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),$Comp));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),$Order['OrderID']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),$Order['SchemeName']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/ExpirationDate',$Order['DaysRemainded']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),$Comp));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(SPrintF('Colors/%sOrders',$Service['Code']),$Order['StatusID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Style = Array('class'=>'Standard','style'=>SPrintF('background-color:%s;',$Comp['bgcolor']));
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',$Style,$Statuses[$Order['StatusID']]['Name']));
	#-------------------------------------------------------------------------------
	$Array[] = $Tr;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Array);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('align'=>'center'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'		=> 'button',
			'onclick'	=> SPrintF("FormEdit('/API/OrdersPay','OrdersPayForm','%s');",'Продление выбранных заказов'),
			'value'		=> 'Продлить',
			'prompt'	=> 'Продление/оплата всех выбранных заказов'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
