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
$ContractID	=  (string) @$Args['ContractID'];
$DSSchemeID	= (integer) @$Args['DSSchemeID'];
$StepID		= (integer) @$Args['StepID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/WhoIs.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'User/Services'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Аренда сервера');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Order.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DSOrderForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if($StepID){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'ContractID','type'=>'hidden','value'=>$ContractID));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Regulars = Regulars();
	#-------------------------------------------------------------------------------
	if(!$DSSchemeID)
		return new gException('DS_SCHEME_NOT_DEFINED','Сервер для аренды не выбран');
	#-------------------------------------------------------------------------------
	$DSScheme = DB_Select('DSSchemes',Array('ID','Name','IsActive'),Array('UNIQ','ID'=>$DSSchemeID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DSScheme)){
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
	if(!$DSScheme['IsActive'])
		return new gException('SCHEME_NOT_ACTIVE','Выбранный тарифный план заказа DS не активен');
	#-------------------------------------------------------------------------------
	$Table = Array(Array('Тарифный план',$DSScheme['Name']));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DSSchemeID','type'=>'hidden','value'=>$DSScheme['ID']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Rows = Array();
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('align'=>'right'),'');
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'Order("DS");','value'=>'Продолжить'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Table[] = $Div;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table,Array('width'=>400));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',$Form);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$__USER = $GLOBALS['__USER'];
	#-------------------------------------------------------------------------------
	$Contracts = DB_Select('Contracts',Array('ID','Customer'),Array('Where'=>SPrintF("`UserID` = %u AND `TypeID` != 'NaturalPartner'",$__USER['ID'])));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contracts)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('CONTRACTS_NOT_FOUND','Система не обнаружила у Вас ни одного договора. Пожалуйста, перейдите в раздел [Мой офис - Договоры] и сформируйте хотя бы 1 договор.');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	foreach($Contracts as $Contract){
		#-------------------------------------------------------------------------------
		$Customer = $Contract['Customer'];
		#-------------------------------------------------------------------------------
		$Number = Comp_Load('Formats/Contract/Number',$Contract['ID']);
		if(Is_Error($Number))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(Mb_StrLen($Customer) > 20)
			$Customer = SPrintF('%s...',Mb_SubStr($Customer,0,20));
		#-------------------------------------------------------------------------------
		$Options[$Contract['ID']] = SPrintF('#%s / %s',$Number,$Customer);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'ContractID'),$Options,$ContractID);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Window = JSON_Encode(Array('Url'=>'/DSOrder','Args'=>Array()));
	#-------------------------------------------------------------------------------
	$A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/ContractMake',{Window:'%s'});",Base64_Encode($Window))),'[новый]');
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($A);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'Order("DS");','value'=>'Продолжить'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Table = Array(Array('Базовый договор',$NoBody));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$UniqID = UniqID('DSSchemes');
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Services/Schemes','DSSchemes',$__USER['ID'],Array('Name','ServerID'),$UniqID);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Columns = Array(
			'ID','Name','ServerID','UserNotice','CostMonth','CostInstall','CPU', 'ram', 'raid', 'disks',
			SPrintF('(SELECT `Name` FROM `ServersGroups` WHERE `ID` = (SELECT `ServersGroupID` FROM `Servers` WHERE `Servers`.`ID` = `%s`.`ServerID`)) as `ServersGroupName`',$UniqID),
			SPrintF('(SELECT `Comment` FROM `ServersGroups` WHERE `ID` = (SELECT `ServersGroupID` FROM `Servers` WHERE `Servers`.`ID` = `%s`.`ServerID`)) as `ServersGroupComment`',$UniqID),
			SPrintF('(SELECT `SortID` FROM `ServersGroups` WHERE `ID` = (SELECT `ServersGroupID` FROM `Servers` WHERE `Servers`.`ID` = `%s`.`ServerID`)) as `ServersGroupSortID`',$UniqID),
			);
	#-------------------------------------------------------------------------------
	$DSSchemes = DB_Select($UniqID,$Columns,Array('SortOn'=>Array('ServersGroupSortID','CostMonth','Name','SortID'),'Where'=>Array('`IsActive` = "yes"','`IsBroken` = "no"')));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DSSchemes)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('DS_SCHEMES_NOT_FOUND','Нет свободных серверов');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	# массив с именами
	$NoBody = new Tag('NOBODY');
	#-------------------------------------------------------------------------------
	$Tr = new Tag('TR');
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','colspan'=>2),'Сервер'));
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Цена в мес.'));
	#-------------------------------------------------------------------------------
	$Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','Процессор'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	#-------------------------------------------------------------------------------
	$LinkID = UniqID('Prompt');
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = &$Td;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Prompt',$LinkID,'Информация о процессоре(-ах) усновленном в сервере');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild($Td);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','RAM'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = &$Td;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Prompt',$LinkID,'Количество установленной оперативной памяти, Gb');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild($Td);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','RAID'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = &$Td;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Prompt',$LinkID,'Тип установленного RAID контроллера, его характеристики');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild($Td);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','HDD'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = &$Td;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Prompt',$LinkID,'Характеристики жёстких дисков установленных в сервер');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild($Td);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	#-------------------------------------------------------------------------------
	$Rows = Array($Tr);
	#-------------------------------------------------------------------------------
	$ServersGroupName = UniqID();
	#-------------------------------------------------------------------------------
	foreach($DSSchemes as $DSScheme){
		#-------------------------------------------------------------------------------
		if($ServersGroupName != $DSScheme['ServersGroupName']){
			#-------------------------------------------------------------------------------
			$ServersGroupName = $DSScheme['ServersGroupName'];
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/String',$DSScheme['ServersGroupComment'],75);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>11,'class'=>'Separator'),new Tag('SPAN',Array('style'=>'font-size:16px;'),SPrintF('%s |',$ServersGroupName)),new Tag('SPAN',Array('style'=>'font-size:11px;'),$Comp)));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',Array('name'=>'DSSchemeID','type'=>'radio','value'=>$DSScheme['ID']));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if($DSScheme['ID'] == $DSSchemeID || (!$DSSchemeID && !IsSet($IsChecked))){
			#-------------------------------------------------------------------------------
			$Comp->AddAttribs(Array('checked'=>'true'));
			#-------------------------------------------------------------------------------
			$IsChecked = TRUE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comment = $DSScheme['UserNotice'];
		#-------------------------------------------------------------------------------
		if($Comment)
			$Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>2)),new Tag('TD',Array('colspan'=>9,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),$Comment));
		#-------------------------------------------------------------------------------
		$CostMonth = Comp_Load('Formats/Currency',$DSScheme['CostMonth']);
		if(Is_Error($CostMonth))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$raid = Comp_Load('Formats/String',$DSScheme['raid'],9);
		if(Is_Error($raid))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Rows[] = new Tag('TR',
					Array('OnClick'=>SPrintF('document.forms[\'DSOrderForm\'].DSSchemeID.value=%s',$DSScheme['ID'])),
					new Tag('TD',Array('width'=>20),$Comp),
					new Tag('TD',Array('class'=>'Comment'),$DSScheme['Name']),
					new Tag('TD',Array('class'=>'Standard','align'=>'right'),$CostMonth),
					new Tag('TD',Array('class'=>'Standard','align'=>'left'),$DSScheme['CPU']),
					new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['ram']),
					new Tag('TD',Array('class'=>'Standard','align'=>'right'),$raid),
					new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['disks'])
					);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Extended',$Rows,Array('align'=>'center'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'Order("DS");','value'=>'Продолжить'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'StepID','value'=>1,'type'=>'hidden'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',$Form);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
