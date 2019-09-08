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
$Method		= (string) @$Args['Method'];	// метод оповещения
$Value		= (string) @$Args['Value'];	// контактный адрес пользователя
$ContactID	= (integer)@$Args['ContactID'];	// идентификатор контакта в БД
$Code		= (string) @$Args['Code'];	//
$Confirm	= (string) @$Args['Confirm'];	//
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Server.php','modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if(!In_Array($Method,Array_Keys($Config['Notifies']['Methods'])))
	return new gException('WRONG_CONTACT_ADDRESS','Несуществующий способ оповещения');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods'][$Method]['IsActive'])
	return new gException('WRONG_CONTACT_ADDRESS','Данный способ оповещения отключен администратором');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars[$Method],$Value))
	return new gException('WRONG_CONTACT_ADDRESS','Неверно указан адрес');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// проверяем существование адрреса у этого юзера
foreach($__USER['Contacts'] as $iContact)
	if($iContact['ID'] == $ContactID)
		if($iContact['UserID'] == $__USER['ID'])
			if($iContact['MethodID'] == $Method)
				$Contact = $iContact;
#-------------------------------------------------------------------------------
if(!IsSet($Contact))
	return new gException('CONTACT_NOT_FOUND',SPrintF('Контакт с указанными параметрами не найден'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// и не подтверждён ли он уже
if($Contact['Confirmed']){
	#-------------------------------------------------------------------------------
	return new gException('ALREADY_CONFIRMED','Уже подтверждено');
	#-------------------------------------------------------------------------------
	Header(SPrintF('Location: /Home'));
	#-------------------------------------------------------------------------------
	return NULL;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServerSettings = SelectServerSettingsByTemplate($Method);
#-------------------------------------------------------------------------------
switch(ValueOf($ServerSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	if($Method != 'Email')
		return $ServerSettings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['User']['Notes'][$Method];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Cache = Array(
		'Limit'		=> SPrintF('li-%s-%s-%s',$Method,$Value,$__USER['ID']),
		);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Confirm && !$Code){
	#-------------------------------------------------------------------------------
	# возможный вариант: контакта не было, или был другой - а юзер его ввёл и не сохраняя нажал "подтвердить"
	if($Contact['Address'] != $Value)
		return new gException('INFORMATION_NOT_SAVED', 'Для подтверждения, вначале сохраните настройки с введёнными данными');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// Защита от агрессивно настроенных, любителей долбить кнопку раз за разом
	$Result = CacheManager::get($Cache['Limit']);
	#-------------------------------------------------------------------------------
	if($Result){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Date/Remainder',$Settings['ConfirmInterval']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		return new gException('INTERVAL_NOT_EXPIRED', SPrintF("Вы уже отправили сообщение с кодом подтверждения. Новое сообщение вы сможете отправить только через %s",$Comp));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Confirm1 = Comp_Load('Passwords/Generator',4,TRUE);
	if(Is_Error($Confirm1))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Confirm2 = Comp_Load('Passwords/Generator');
	if(Is_Error($Confirm2))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# строка подтверждения будет содержать сразу оба подтверждения - и короткое и длинное
	$IsUpdate = DB_Update('Contacts',Array('Confirmation'=>SPrintF('%s/%s',$Confirm1,$Confirm2)),Array('ID'=>$ContactID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/API/Confirm]: Confirm1 = %s; Confirm2 = %s;',$Confirm1,$Confirm2));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Executor = DB_Select('Users',Array('Sign','Email'),Array('UNIQ','ID'=>100));
	if(!Is_Array($Executor))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# сообщение для SMS и часть остальных вариантов оповещения
	$Message1 = SPrintF('Ваш проверочный код: %s',$Confirm1);
	#-------------------------------------------------------------------------------
	$Message2 = "%s\r\n\r\nДля подтверждения вашего контактного адреса, вы можете пройти по этой ссылке:\r\n%s\r\nЕсли ссылка не открывается, то скопируйте и вставьте её в адресную строку браузера\r\n\r\n--\r\n%s\r\n";
	#-------------------------------------------------------------------------------
	$Url = SPrintF('http://%s/API/Confirm?Method=%s&ContactID=%u&Value=%s&Code=%s/%s',HOST_ID,$Method,$ContactID,$Value,$Confirm1,$Confirm2);
	#-------------------------------------------------------------------------------
	$Message2 = SPrintF($Message2,$Message1,$Url,$Executor['Sign']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Theme = SPrintF('Подтверждение %s адреса',$Config['Notifies']['Methods'][$Method]['Name']);
	#-------------------------------------------------------------------------------
	$Heads = Array(SPrintF('From: %s',$Executor['Email']),'MIME-Version: 1.0','Content-Transfer-Encoding: 8bit',SPrintF('Content-Type: multipart/mixed; boundary="----==--%s"',HOST_ID));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(SPrintF('Tasks/%s',$Method),NULL,$Value,($Config['Notifies']['Methods'][$Method]['IsShort'])?$Message1:$Message2,Array('Heads'=>Implode("\n",$Heads),'UserID'=>$__USER['ID'],'Theme'=>$Theme,'TimeBegin'=>0,'TimeEnd'=>0,'ChargeFree'=>TRUE));
	if(Is_Error($Comp))
		return new gException('ERROR_MESSAGE_SEND','Не удалось отправить сообщение');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// обнуляем что адерс подтверждён, раз запрошено подтверждение
	$IsUpdate = DB_Update('Contacts',Array('Confirmed'=>0),Array('ID'=>$ContactID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	CacheManager::add($Cache['Limit'],Time(),IntVal($Settings['ConfirmInterval']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return Array('Status' => 'Ok');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	if(Empty($Confirm) && Empty($Code))
		return new gException('ERROR_CODE_EMPTY', 'Введите полученный код подтверждения, или пройдите по ссылке из сообщения');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$Contact['Confirmation']){
		#-------------------------------------------------------------------------------
		return new gException('NO_CONFIRM_CODE','В базе отсутствует код подтверждения');
		#-------------------------------------------------------------------------------
		Header(SPrintF('Location: /Home'));
		#-------------------------------------------------------------------------------
		return NULL;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём короткий код подвтерждения
	$Array = Explode("/", $Contact['Confirmation']);
	#-------------------------------------------------------------------------------
	if($Confirm)
		if($Confirm != $Array[0])
			return new gException('BAD_CONFIRM_CODE', 'Введён неверный код, попробуйте подтвердить ещё раз');
	#-------------------------------------------------------------------------------
	if($Code){
		#-------------------------------------------------------------------------------
		# если подтверждение через код в ссылке
		$DOM = new DOM();
		#-------------------------------------------------------------------------------
		$Links = &Links();
		# Коллекция ссылок
		$Links['DOM'] = &$DOM;
		#-------------------------------------------------------------------------------
		if(Is_Error($DOM->Load('Base')))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$DOM->AddText('Title','Подтверждение контактного адреса');
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if($Code != $Contact['Confirmation']){
			#-------------------------------------------------------------------------------
			$DOM->AddAttribs('Body',Array('onload'=>"ShowAlert('Ссылка устарела, попробуйте подтвердить ещё раз','Warning');location.href = '/Home';"));
			#-------------------------------------------------------------------------------
			$DOM->AddChild('Into',$NoBody);
			#-------------------------------------------------------------------------------
			$Out = $DOM->Build();
			#-------------------------------------------------------------------------------
			if(Is_Error($Out))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			return $Out;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Contacts',Array('Confirmed'=>Time()),Array('ID'=>$ContactID));
	if (Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# TODO исправляем юзера - проверить что это реально надо, и надо тут
	$Comp = Comp_Load('Tasks/RecoveryUsers',NULL,$__USER['ID']);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Settings['SettingsReset']){
		#-------------------------------------------------------------------------------
		// Отключаем все уведомлепния в настройках
		$Notifies = $Config['Notifies'];
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Notifies['Types']) as $TypeID){
			#-------------------------------------------------------------------------------
			$Where = Array(
					SPrintF("`UserID` = %u",$__USER['ID']),
					SPrintF("`MethodID` = '%s'",$Method),
					SPrintF("`TypeID` = '%s'",$TypeID)
					);
			#-------------------------------------------------------------------------------
			$Count = DB_Count('Notifies',Array('Where'=>$Where));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(!$Count){
				#-------------------------------------------------------------------------------
				$INotify = Array('UserID'=>$__USER['ID'],'MethodID'=>$Method,'TypeID'=>$TypeID);
				#-------------------------------------------------------------------------------
				$IsInsert = DB_Insert('Notifies', $INotify);
				if(Is_Error($IsInsert))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$__USER['ID'],'PriorityID'=>'Billing','Text'=>SPrintF('Контактный адрес (%s) подтверждён через "%s"',$Contact['Address'],$Config['Notifies']['Methods'][$Method]['Name']));
	#-------------------------------------------------------------------------------
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если подтверждали цифирками через интерфейс
	if($Confirm)
		return Array('Status' => 'Ok');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если не вариант выше - значит подтверждение через код в ссылке
	$DOM->AddAttribs('Body',Array('onload'=>"ShowAlert('Контактный адрес подтверждён'); location.href = '/Home';"));
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',$NoBody);
	#-------------------------------------------------------------------------------
	$Out = $DOM->Build();
	#-------------------------------------------------------------------------------
	if(Is_Error($Out))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status' => 'Error');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
