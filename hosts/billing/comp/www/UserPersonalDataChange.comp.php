<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Upload.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Персональные данные');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserPersonalDataChange.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Messages = Messages();
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'	=> 'Name',
				'type'	=> 'text',
				'prompt'=> $Messages['Prompts']['User']['Name'],
				'value'	=> $__USER['Name'],
				'style'	=> 'width: 100%'
				)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ваше имя',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('name'=>'Sign','rows'=>3,'cols'=>30),$__USER['Sign']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Ваши контактные данные';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# see JBS-277
$Params = Array('name'=>'Email','size'=>25,'type'=>'text','value'=>$__USER['Email']);
#-------------------------------------------------------------------------------
# менять можно в течении часа после потдвержения или в течении суток после регистрации
if($__USER['EmailConfirmed'] + 3600 > Time() || Time() - $__USER['RegisterDate'] < 24*3600){
	#-------------------------------------------------------------------------------
	# можно менять
	$Params['prompt']  = 'Введите новый почтовый адрес';
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	# нельзя менять
	$Params['prompt']  = 'Для смены почтового адреса, подтвердите текущий почтовый адрес, после чего поле смены адреса станет доступно';
	#-------------------------------------------------------------------------------
	$Params['readonly']= 'readonly';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',$Params);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(CacheManager::isEnabled()){
    $Config = Config();
    #-------------------------------------------------------------------------------
    if($Config['Notifies']['Methods']['Email']['IsActive']){
	$Params = Array('onclick' => 'EmailConfirm();', 'type' => 'button');
	#-----------------------------------------------------------------------------
	if ($__USER['EmailConfirmed'] > 0) {
	    $EmailConfirmed = Comp_Load('Formats/Date/Extended', $__USER['EmailConfirmed']);
	    if (Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	    $Params['value'] = 'Подтвержден';
	    $Params['prompt'] = "Ваш почтовый адрес был подтверждён: ".$EmailConfirmed;
	    #$Params['disabled'] = 'disabled'; # смена возможна ткоа час после подтверждения. как и переносы заказов.
	}else{
	    $Params['prompt'] = "Нажмите для подтверждения вашего почтового адреса";
	    $Params['value'] = 'Подтвердить';
	}
	#-----------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input', $Params);
	if (Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------------
	$NoBody->AddChild($Comp);
    }
    #-----------------------------------------------------------------------------
    $Table[] = Array('Электронный адрес', $NoBody);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'	=> 'Mobile',
			'size'	=> 25,
			'type'	=> 'number',
			'prompt'=> $Messages['Prompts']['Mobile'],
			'value'	=> $__USER['Mobile']
			)
		);
if (Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY', $Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if ($Config['Notifies']['Methods']['SMS']['IsActive']) {
    #-----------------------------------------------------------------------------
    $Params = Array('onclick' => 'MobileConfirm();', 'type' => 'button');
    #-----------------------------------------------------------------------------
    if ($__USER['MobileConfirmed'] > 0) {
        #-------------------------------------------------------------------------------
	$MobileConfirmed = Comp_Load('Formats/Date/Extended', $__USER['MobileConfirmed']);
	if (Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Params['value'] = 'Подтвержден';
	$Params['onclick']= "javascript:ShowAlert('Нет необходимости в повторном подтверждении вашего телефонного номера','Warning');";
	$Params['prompt'] = SPrintF('Ваш мобильный телефон был подтверждён: %s',$MobileConfirmed);
	#-------------------------------------------------------------------------------
    }else{
	$Params['value'] = 'Подтвердить';
	$Params['prompt'] = "Нажмите для получения кода подтверждения";
    }
    #-----------------------------------------------------------------------------
    $Comp = Comp_Load('Form/Input', $Params);
    if (Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
    #-----------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('Мобильный телефон', $NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if ($__USER['MobileConfirmed'] == 0 && $Config['Notifies']['Methods']['SMS']['IsActive']){
    $Comp = Comp_Load(
	    'Form/Input', Array(
	'name' => 'MobileConfirmCode',
	'size' => 25,
	'type' => 'number',
	'prompt' => $Messages['Prompts']['MobileConfirm'],
	'value' => ''
	    )
    );
    if (Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
    $NoBody = new Tag('NOBODY', $Comp);
#-------------------------------------------------------------------------------
    $Config = Config();
#-------------------------------------------------------------------------------
    if ($Config['Notifies']['Methods']['SMS']['IsActive']) {
	#---------------------------------------------------------------------------
	$Comp = Comp_Load(
		'Form/Input', Array(
	    'onclick' => 'MobileConfirmCheck();',
	    'type' => 'button',
	    'value' => 'Проверить',
	    'prompt' => 'Нажмите для проверки вашего кода'
		)
	);
	if (Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
	$NoBody->AddChild($Comp);
    }
    #---------------------------------------------------------------------------
    $Table[] = Array('Код подтверждения телефона', $NoBody);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'ICQ',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['ICQ'],
    'value'  => $__USER['Params']['NotificationMethods']['ICQ']['Address']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if ($Config['Notifies']['Methods']['ICQ']['IsActive']) {
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
	'Form/Input', Array(
	'onclick' => 'ICQTest();',
	'type' => 'button',
	'value' => 'Тест',
	'prompt' => 'Отправить тестовое сообщение'
	    )
    );
    if (Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('ICQ-номер',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'JabberID',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['JabberID'],
    'value'  => $__USER['Params']['NotificationMethods']['Jabber']['Address']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
if($Config['Notifies']['Methods']['Jabber']['IsActive']){
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'onclick' => 'JabberTest();',
      'type'    => 'button',
      'value'   => 'Тест',
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('Jabber',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Данные для участия в обсуждениях';
#-------------------------------------------------------------------------------
$Foto = GetUploadedFileSize('Users',$__USER['ID']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','UserFoto',$Foto?SPrintF('%01.2f Кб.',$Foto/1024):'не загружена');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Аватар (90x110)',$Comp);
#-------------------------------------------------------------------------------
if($Foto){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'checkbox',
      'name'  => 'IsClear',
      'value' => 'yes'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsClear\'); return false;'),'Удалить фотографию'),$Comp);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "javascript: if(form.Mobile.value.charAt(0) == 8 || form.Mobile.value.charAt(0) == 9){ ShowConfirm('С цифры 8 начинаются коды таких стран как Китай, Бангладеш и т.п. С цифры 9 начинаются телефонов в Афганистане, Монголии, Турции ... Вы уверены что ваш мобильный телефон относится именно к этой стране? Например код РФ: 7, Беларуси: 375, Украины: 380. Соответственно, обычный номер Российского мобильного телефона выглядит так: 79262223344. Вы всё ещё хотите сохранить свой телефонный номер в таком виде?','UserPersonalDataChange();'); }else{ UserPersonalDataChange();}",
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserPersonalDataChangeForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
