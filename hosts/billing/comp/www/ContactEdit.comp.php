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
$ContactID = (integer) @$Args['ContactID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ContactID){
	#-------------------------------------------------------------------------------
	foreach($GLOBALS['__USER']['Contacts'] as $iContact)
		if($iContact['ID'] == $ContactID)
			$Contact = $iContact;
	#-------------------------------------------------------------------------------
	if(!IsSet($Contact))
		return new gException('CONTACT_ID_NOT_FOUND',SPrintF('Неверно указан идентификатор контакта: %s',$ContactID));
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Contact = Array(
				'UserID'	=> $GLOBALS['__USER']['ID'],
				'MethodID'	=> 'Email',
				'Address'	=> '',
				'Confirmed'	=> '',
				'TimeBegin'	=> 00,
				'TimeEnd'	=> 00,
				'IsPrimary'	=> FALSE,
				'IsActive'	=> TRUE
			);      
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Permission = Permission_Check('ContactRead',(integer)$GLOBALS['__USER']['ID'],(integer)$Contact['UserID']);
#---------------------------------------------------------------------------
switch(ValueOf($Permission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return ERROR | @Trigger_Error(700);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
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
$DOM->AddText('Title',SPrintF('Изменение контактного адреса'));
#---------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/ContactEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Table = Array(SPrintF('%s: %s',$Contact['MethodID'],$Contact['Address']));
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$Options = Array();
#---------------------------------------------------------------------------
$Config = Config();
#---------------------------------------------------------------------------
$Messages = Messages();
#---------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach(Array_Keys($Config['Notifies']['Methods']) as $MethodID)
	if($Config['Notifies']['Methods'][$MethodID]['IsActive'])
		$Options[$MethodID] = $Config['Notifies']['Methods'][$MethodID]['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'MethodID','style'=>'width: 100%'),$Options,$Contact['MethodID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ContactID)
	$Comp->AddAttribs(Array('disabled'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array('Тип адреса',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'Address',
			'type'  => 'text',
			'prompt'=> $Messages['Prompts'][$Contact['MethodID']],
			'value' => $Contact['Address'],
			'style' => 'width: 100%'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Contact['IsPrimary'])
	$Comp->AddAttribs(Array('disabled'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array('Адрес',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
for($i = 0; $i <= 23; $i++){
	#-------------------------------------------------------------------------------
	$Value = SPrintF('%02d',$i);
	#-------------------------------------------------------------------------------
	$Options[$Value] = SPrintF('%s:00',$Value);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'TimeBegin','style'=>'width: 100%','prompt'=>'Время начала рассылки сообщений'),$Options,$Contact['TimeBegin']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Время начала рассылки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'TimeEnd','style'=>'width: 100%','prompt'=>'Время окончания рассылки сообщений'),$Options,$Contact['TimeEnd']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Время конца рассылки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Array = Array('type'=>'checkbox','name'=>'IsActive','value'=>'yes');
#-------------------------------------------------------------------------------
if(!$Contact['Confirmed']){
	#-------------------------------------------------------------------------------
	$Array['disabled']	= 'true';
	#-------------------------------------------------------------------------------
	$Array['prompt']	= 'Для включения уведомлений адрес должен быть подтверждён';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',$Array);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// если активен или не подтверждён - чтобы при уходе со страницы кнопкой отправить - не отключались уведомления
if($Contact['IsActive'] || !$Contact['Confirmed'])
	$Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsActive\'); return false;'),'Использовать для уведомлений'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Contact['MethodID'] == 'Email'){
	#-------------------------------------------------------------------------------
	$Array = Array('type'=>'checkbox','name'=>'IsPrimary','value'=>'yes');
	#-------------------------------------------------------------------------------
	if(!$Contact['Confirmed']){
		#-------------------------------------------------------------------------------
		$Array['disabled']	= 'true';
		#-------------------------------------------------------------------------------
		$Array['prompt']	= 'Адрес необходимо подтвердить, тогда его можно будет использовать как логин в биллинговую систему';
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',$Array);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Contact['IsPrimary'])
		$Comp->AddAttribs(Array('checked'=>'true'));
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsPrimary\'); return false;'),'Использовать как логин'),$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Contact['Confirmed']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Extended',$Contact['Confirmed']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Адрес подтверждён',$Comp);
	#-------------------------------------------------------------------------------
}elseif($Config['Notifies']['Methods'][$Contact['MethodID']]['IsActive'] && $ContactID){
	#-------------------------------------------------------------------------------
	$Attribs 		= Array('type'=>'button','prompt'=>'Нажмите для получения кода подтверждения');
	$Attribs['onclick']     = SPrintF('form.AddressCode.disabled=false; form.CheckButton.disabled=false; Confirm(\'%s\',form.Address.value,form.ContactID.value);',$Contact['MethodID']);
	$Attribs['value']       = 'Получить код подтвержения';
	$Attribs['prompt']      = 'Нажмите для получения кода подтверждения';
	#-------------------------------------------------------------------------------
	$Confirm = Comp_Load('Form/Input',$Attribs);
	#-------------------------------------------------------------------------------
	if(Is_Error($Confirm))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input', Array('name'=>'AddressCode','type'=>'text','prompt'=>SPrintF('Введите код полученный в сообщении через %s, и нажмите кнопку "Проверить"',$Config['Notifies']['Methods'][$Contact['MethodID']]['Name']),'value'=>'','disabled'=>'yes'));
	if (Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY', $Comp);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('onclick'=>SPrintF('ConfirmCheck(\'%s\',\'%s\',%s);',$Contact['MethodID'],$Contact['Address'],$Config['Interface']['User']['Notes'][$Contact['MethodID']]['SettingsReset']),'type'=>'button','value'=>'Проверить','prompt'=>'Нажмите для проверки вашего кода','disabled'=>'yes','name'=>'CheckButton'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Table[] = Array($Confirm, $NoBody);
	#-------------------------------------------------------------------------------
	#$Table[] = Array('Нажмите для подтверждения', $Comp);
	#---------------------------------------------------------------------------
}
#---------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'		=> 'button',
			#'onclick'	=> "FormEdit('/API/ContactEdit','ContactEditForm','Изменение контактаного адреса');",
			'onclick'	=>'ConfirmSubmit();',
			'value'		=> 'Изменить'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);

#$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'ConfirmSubmit();','value'=>'Сохранить'));
#if(Is_Error($Comp))
#return ERROR | @Trigger_Error(500);

#---------------------------------------------------------------------------
$Table[] = $Comp;
#---------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ContactEditForm','onsubmit'=>'return false;'),$Comp);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'ContactID',
			'type'  => 'hidden',
			'value' => $ContactID
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Form->AddChild($Comp);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#---------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
?>
