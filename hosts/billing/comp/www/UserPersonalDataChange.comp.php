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
$Config = Config();
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











$Methods = $Config['Notifies']['Methods'];

Debug(SPrintF('[comp/www/UserPersonalDataChange]: %s',print_r($__USER['Params']['NotificationMethods'],true)));

foreach(Array_Keys($Methods) as $Key){
	#-------------------------------------------------------------------------------
	$Method = $Methods[$Key];
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/UserPersonalDataChange]: Key = %s; Name = %s; IsActive = %s;',$Key,$Method['Name'],$Method['IsActive']));
	#-------------------------------------------------------------------------------
	# кастыли для почты
	$__USER['Params']['NotificationMethods']['Email'] = Array('Address'=>$__USER['Email'],'Confirmed'=>$__USER['EmailConfirmed']);
	#-------------------------------------------------------------------------------
	$NotificationMethod = $__USER['Params']['NotificationMethods'][$Key];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/UserPersonalDataChange]: до кнопки'));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>$Key,'type'=>'text','prompt'=> $Messages['Prompts'][$Key],'value'=>$NotificationMethod['Address']));
	if (Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY', $Comp);
	#-------------------------------------------------------------------------------
	if($Method['IsActive']){
		#-------------------------------------------------------------------------------
		$Attribs = Array('type'=>'button','prompt'=>'Нажмите для получения кода подтверждения');
		#-------------------------------------------------------------------------------
		if(!$NotificationMethod['Confirmed']){
			#-------------------------------------------------------------------------------
			$Attribs['onclick']	= SPrintF('form.%sCode.disabled=false; Confirm(\'%s\',form.%s.value);',$Key,$Key,$Key);
			$Attribs['value']	= 'Подтвердить';
			$Attribs['prompt']	= 'Нажмите для получения кода подтверждения';
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Date/Extended', $NotificationMethod['Confirmed']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Attribs['value']	= 'Подтверждён';
			$Attribs['prompt']	= SPrintF('Ваш адрес был подверждён: %s',$Comp);
			$Attribs['style']	= 'cursor: not-allowed;';
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',$Attribs);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody->AddChild($Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Table[] = Array($Method['Name'], $NoBody);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Method['IsActive'] && !$NotificationMethod['Confirmed']){
		Debug(SPrintF('[comp/www/UserPersonalDataChange]: %s',print_r($Config['Interface']['User']['Notes'][$Key],true)));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input', Array('name'=>SPrintF('%sCode',$Key),'type'=>'text','prompt'=>SPrintF('Введите код полученный в сообщении через %s, и нажмите кнопку "Проверить"',$Methods[$Key]['Name']),'value'=>'','disabled'=>'yes'));
		if (Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY', $Comp);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',Array('onclick'=>SPrintF('ConfirmCheck(\'%s\',%s);',$Key,$Config['Interface']['User']['Notes'][$Key]['SettingsReset']),'type'=>'button','value'=>'Проверить','prompt'=>'Нажмите для проверки вашего кода'));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Код подтверждения', $NoBody);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}














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
    'onclick' => "javascript: if(form.Mobile && (form.Mobile.value.charAt(0) == 8 || form.Mobile.value.charAt(0) == 9)){ ShowConfirm('С цифры 8 начинаются коды таких стран как Китай, Бангладеш и т.п. С цифры 9 начинаются телефонов в Афганистане, Монголии, Турции ... Вы уверены что ваш мобильный телефон относится именно к этой стране? Например код РФ: 7, Беларуси: 375, Украины: 380. Соответственно, обычный номер Российского мобильного телефона выглядит так: 79262223344. Вы всё ещё хотите сохранить свой телефонный номер в таком виде?','UserPersonalDataChange();'); }else{ UserPersonalDataChange();}",
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
