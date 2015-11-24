<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Name		=  (string) @$Args['Name'];
$Sign		=  (string) @$Args['Sign'];
$IsClear	= (boolean) @$Args['IsClear'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Upload.php','libs/Image.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['Char'],$Name))
	return new gException('WRONG_NAME','Вы ввели неверное имя');
#-------------------------------------------------------------------------------
if(!$Sign)
	return new gException('SIGN_IS_EMPTY','Укажите Вашу подпись');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$UUser = Array('Name'=>$Name,'Sign'=>$Sign,'Params'=>$__USER['Params']);
#-------------------------------------------------------------------------------
# кастыли для почты
$__USER['Params']['NotificationMethods']['Email'] = Array('Address'=>$__USER['Email'],'Confirmed'=>$__USER['EmailConfirmed']);
#-------------------------------------------------------------------------------
$Methods = $Config['Notifies']['Methods'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach(Array_Keys($Methods) as $Key){
	#-------------------------------------------------------------------------------
	if($Key == 'Email')
		continue;
	#-------------------------------------------------------------------------------
	$Method = $Methods[$Key];
	#-------------------------------------------------------------------------------
	$NotificationMethod = $__USER['Params']['NotificationMethods'][$Key];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Value = Mb_StrToLower(Trim(@$Args[$Key]));
	#-------------------------------------------------------------------------------
	if($Value){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/www/API/UserPersonalDataChange]: Key = %s; value = %s',$Key,$Value));
		#-------------------------------------------------------------------------------
		if(!Preg_Match($Regulars[$Key],$Value))
			return new gException('WRONG_CONTACT',SPrintF('Неверно указан адрес: %s',$Value));
		#-------------------------------------------------------------------------------
		if($Value != $NotificationMethod['Address']){
			#-------------------------------------------------------------------------------
			$Message = ($NotificationMethod['Address'])?SPrintF('Смена контактного адреса %s -> %s (%s)',$NotificationMethod['Address'],$Value,$Method['Name']):SPrintF('Добавлен контактный адрес (%s) для %s',$Value,$Method['Name']);
			#-------------------------------------------------------------------------------
			if($Key == 'Email'){
				#-------------------------------------------------------------------------------
				$Count = DB_Count('Users',Array('Where'=>SPrintF("`Email` = '%s'",$Value)));
				if(Is_Error($Count))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				if($Count)
					return new gException('USER_EXISTS','Пользователь с таким электронным адресом уже существует');
				#-------------------------------------------------------------------------------
				$UUser['Email'] = $Value;
				#-------------------------------------------------------------------------------
				$UUser['EmailConfirmed'] = 0;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$UUser['Params']['NotificationMethods'][$Key]['Address'] = $Value;
				#-------------------------------------------------------------------------------
				$UUser['Params']['NotificationMethods'][$Key]['Confirmed'] = 0;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		if($NotificationMethod['Address']){
			#-------------------------------------------------------------------------------
			$Message = SPrintF('Удалён контактный адрес (%s / %s)',$NotificationMethod['Address'],$Method['Name']);
			#-------------------------------------------------------------------------------
			$UUser['Params']['NotificationMethods'][$Key]['Address'] = $Value;
			#-------------------------------------------------------------------------------
			$UUser['Params']['NotificationMethods'][$Key]['Confirmed'] = 0;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(!IsSet($Message))
		continue;
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$__USER['ID'],'PriorityID'=>'Billing','Text'=>$Message);
	#-------------------------------------------------------------------------------
	$Event = Comp_Load('Events/EventInsert', $Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	UnSet($Message);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Upload = Upload_Get('UserFoto');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$Foto = $Upload['Data'];
	#-------------------------------------------------------------------------------
	$Foto = Image_Resize($Foto,90,110);
	#-------------------------------------------------------------------------------
	if(Is_Error($Foto))
		return new gException('FOTO_RESIZE_ERROR','Ошибка изменения размеров персональной фотографии');
	#-------------------------------------------------------------------------------
	if(!SaveUploadedFile('Users',$__USER['ID'],$Foto))
		return new gException('CANNOT_SAVE_UPLOADED_FILE','Не удалось сохранить загруженный файл');
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
if($IsClear)
	if(!DeleteUploadedFile('Users',$__USER['ID']))
		return new gException('CANNOT_DELETE_FILE','Не удалось удалить связанный файл');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Users',$UUser,Array('ID'=>$__USER['ID']));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# исправляем юзера, на всякий случай
$Comp = Comp_Load('Tasks/RecoveryUsers',NULL,$__USER['ID']);
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
