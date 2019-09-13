<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Joonte Software
 *
 */
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
class NotificationManager {
	#-------------------------------------------------------------------------------
	public static function sendMsg(Msg $msg, $Methods = Array(), $IsForceDelivery = FALSE) {
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Config = Config();
		#-------------------------------------------------------------------------------
		$Notifies = $Config['Notifies'];
		#-------------------------------------------------------------------------------
		# вариант когда методы не заданы - значит все доступные
		if(SizeOf($Methods) == 0){
			#-------------------------------------------------------------------------------
			$Array = Array();
			#-------------------------------------------------------------------------------
			foreach (Array_Keys($Notifies['Methods']) as $MethodID)
				$Array[] = $MethodID;
			#-------------------------------------------------------------------------------
			$Methods = $Array;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Executor = Comp_Load('www/Administrator/API/ProfileCompile', Array('ProfileID' => 100));
		#-------------------------------------------------------------------------------
		switch (ValueOf($Executor)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$msg->setParam('Executor', $Executor['Attribs']);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$User = DB_Select('Users',Array('ID','Name','Sign','Email','UniqID','IsActive','IsNotifies'),Array('UNIQ','ID'=>$msg->getTo()));
		#-------------------------------------------------------------------------------
		switch(ValueOf($User)){
		case 'error':
			return ERROR | @Trigger_Error('[Email_Send]: не удалось выбрать получателя');
		case 'exception':
			return new gException('EMAIL_RECIPIENT_NOT_FOUND','Получатель письма не найден');
		case 'array':
			#-------------------------------------------------------------------------------
			$TypeID = $msg->getTemplate();
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/classes/NotificationManager]: TypeID = %s',$TypeID));
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			if(!$User['IsActive'])
				return new gException('USER_DISABLED','Пользователь отключен');
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			if($TypeID != 'UserPasswordRestore')
				if(!$User['IsNotifies'])
					return new gException('NOTIFIES_RECIPIENT_DISABLED','Уведомления для получателя отключены');
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			// достаём контакты юзера (у новых юзеров почтовыый адрес не подтверждён, но он первичный)
			$Where = Array(SPrintF('`UserID` = %u',$User['ID']),'`Confirmed` > 0 OR `IsPrimary` = "yes"','`IsActive` = "yes"');
			#-------------------------------------------------------------------------------
			$Contacts = DB_Select('Contacts',Array('ID','MethodID','Address','ExternalID','TimeBegin','TimeEnd'),Array('Where'=>$Where));
			#-------------------------------------------------------------------------------
			switch(ValueOf($Contacts)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return new gException('USER_NOT_HAVE_ANY_ACTIVE_CONTACTS','У пользователя нет активных контактов');
			case 'array':
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			// докидываем контакты к массиву с данными пользователя
			$User['Contacts'] = $Contacts;
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			$msg->setParam('User', $User);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$From = DB_Select('Users',Array('ID','Name','Sign','Email','UniqID'),Array('UNIQ','ID'=>$msg->getFrom()));
		#-------------------------------------------------------------------------------
		switch(ValueOf($From)){
		case 'error':
			return ERROR | @Trigger_Error('[Email_Send]: не удалось выбрать отправителя');
		case 'exception':
			return new gException('EMAIL_SENDER_NOT_FOUND','Отправитель не найден');
		case 'array':
			#-------------------------------------------------------------------------------
			$msg->setParam('From', $From);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$sentMsgCnt = 0;
		#-------------------------------------------------------------------------------
		// перебираем контакты пользователя
		foreach($User['Contacts'] as $Contact){
			#-------------------------------------------------------------------------------
			$MethodID = $Contact['MethodID'];
			#-------------------------------------------------------------------------------
			# кусок от JBS-879
			if(!IsSet($Notifies['Types'][$TypeID])){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[system/classes/NotificationManager]: TypeID = %s not found',$TypeID));
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				# такие оповещения вообще могут быть отключены (пока, не настраиваемо, т.к. не нужно)
				if(!$Notifies['Types'][$TypeID]['IsActive'])
					continue;
				#-------------------------------------------------------------------------------
				# проверяем, не отключены ли такие оповещения глобально
				$UseName = SPrintF('Use%s',$MethodID);
				#-------------------------------------------------------------------------------
				if(IsSet($Notifies['Types'][$TypeID][$UseName]) && !$Notifies['Types'][$TypeID][$UseName])
					continue;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# проверяем, не отключены ли такие оповещения в настройках юзера
			$Count = DB_Count('Notifies', Array('Where' => SPrintF("`ContactID` = %u AND `TypeID` = '%s'",$msg->getTo(),$TypeID)));
			if (Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if($Count && !$IsForceDelivery){
				#-------------------------------------------------------------------------------
				# отключено, принудительная доставка не задана
				continue;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				if($IsForceDelivery)
					Debug(SPrintF('[system/classes/NotificationManager]: задана принудительная доставка сообщений',$TypeID));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# JBS-1126: save $MethodID settings
			$msg->setParam('MethodSettings',$Notifies['Methods'][$MethodID]);
			#-------------------------------------------------------------------------------
			// JBS-1125, save message recipient and params
			$msg->setParam('ToRecipient',$Contact['Address']);	// контактный адрес 
			$msg->setParam('TimeBegin',$Contact['TimeBegin']);	// время начала рассылки
			$msg->setParam('TimeEnd',$Contact['TimeEnd']);		// время конца рассылки
			$msg->setParam('ContactID',$Contact['ID']);		// идентификатор контакта
			$msg->setParam('ExternalID',$Contact['ExternalID']);	// ChatID для телеги
			#-------------------------------------------------------------------------------
			// JBS-1283, надо сохранить метод, понадобится
			$msg->setParam('MethodID',$MethodID);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			#if(!class_exists($MethodID))
			#	return new gException('DISPATCHER_NOT_FOUND', 'Dispatcher not found: '.$MethodID);
            		#-------------------------------------------------------------------------------
			#$dispatcher = $MethodID::get();
			#$dispatcher = call_user_func($MethodID.'::get', true);
			$dispatcher = Call_User_Func('SendMessage::get',true);
			#-------------------------------------------------------------------------------
			try {
				#-------------------------------------------------------------------------------
				$dispatcher->send($msg);
				#-------------------------------------------------------------------------------
				$sentMsgCnt++;
				#-------------------------------------------------------------------------------
			}catch(jException $e){
				#-------------------------------------------------------------------------------
				Debug(SPrintF("[system/classes/NotificationManager]: Error while sending message [userId=%s, message=%s]", $User['ID'], $e->getMessage()));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if($sentMsgCnt < 1){
			#-------------------------------------------------------------------------------
			Debug(SPrintF("[system/classes/NotificationManager]: Couldn't send notify by any methods to user #%s",$User['ID']));
			#-------------------------------------------------------------------------------
			return new gException('USER_NOT_NOTIFIED','Не удалось оповестить пользователя ни одним из методов');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
		#------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
