<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$Code		=  (string) @$Args['Code'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Code)
	return new gException('NO_CODE','Введите ПромоКод');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Code = DB_Escape(Trim($Code));
#-------------------------------------------------------------------------------
$PromoCode = DB_Select('PromoCodes','*',Array('UNIQ','Where'=>SPrintF("`Code` = '%s'",$Code)));
#-------------------------------------------------------------------------------
switch(ValueOf($PromoCode)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('PROMOCODE_NOT_FOUND','Промокод не найден. Проверьте правильность ввода.');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($PromoCode['ExpirationDate'] < Time())
	return new gException('PROMOCODE_EXPIRED','Срок действия промокода истёк');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($PromoCode['MaxAmount'] < $PromoCode['CurrentAmount'] + 1)
	return new gException('NO_FREE_PROMOCODES','Данные промокоды закончились');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(
			SPrintF('`PromoCodeID` = %u',$PromoCode['ID']),
			SPrintF('`UserID` = %u',$GLOBALS['__USER']['ID'])
		);
#-------------------------------------------------------------------------------
$Count = DB_Count('PromoCodesExtinguished',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('PROMOCODE_ALREADY_EXTINGUISHED','Вы уже активировали этот промокод, можете пользоватся скидкой');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ICode = Array(
		'PromoCodeID'	=> $PromoCode['ID'],
		'UserID'	=> $GLOBALS['__USER']['ID'],
		'CreateDate'	=> Time()
);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IBonus = Array(
		'UserID'	=> $GLOBALS['__USER']['ID'],
		'ServiceID'	=> $PromoCode['ServiceID'],
		'SchemeID'	=> $PromoCode['SchemeID'],
		'SchemesGroupID'=> $PromoCode['SchemesGroupID'],
		'DaysReserved'	=> $PromoCode['DaysDiscont'],
		'Discont'	=> $PromoCode['Discont'],
		'Comment'	=> SPrintF('Активация промокода "%s"',$Code)
		);
#-------------------------------------------------------------------------------
#-------------------------------TRANSACTION-------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID('PromoCodesExtinguished'))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// фтыкаем в таблицу погашеных промокодов
$IsInsert = DB_Insert('PromoCodesExtinguished',$ICode);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// втыкаем бонусы в таблицу бонусов
$IsInsert = DB_Insert('Bonuses',$IBonus);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Проверяем, не надо ли сделать юзера рефералом
if($PromoCode['OwnerID'] > 2000){
	#-------------------------------------------------------------------------------
	$User = DB_Select('Users','*',Array('UNIQ','ID'=>$GLOBALS['__USER']['ID']));
	#-------------------------------------------------------------------------------
	switch(ValueOf($User)){
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
	if((IntVal($User['OwnerID']) > 0 && $PromoCode['ForceOwner']) || (IntVal($User['OwnerID']) == 0)){
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update('Users',Array('OwnerID'=>$PromoCode['OwnerID']),Array('ID'=>$GLOBALS['__USER']['ID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// считаем число заюзанных промокодов
$Count = DB_Count('PromoCodesExtinguished',Array('Where'=>SPrintF('`PromoCodeID` = %u',$PromoCode['ID'])));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// обновляем число заюзанных промокодов
// TODO подумать надо ли это. можно просто SELECT по двум таблицам гонять и не парится
$IsUpdate = DB_Update('PromoCodes',Array('CurrentAmount'=>$Count),Array('ID'=>$PromoCode['ID']));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// записываем событие
$Event = Array('UserID'=>$GLOBALS['__USER']['ID'],'PriorityID'=>'Billing','Text'=>SPrintF('Промокод (%s) успешно активирован',$Code));
$Event = Comp_Load('Events/EventInsert',$Event);
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(DB_Commit($TransactionID)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','Location'=>'/PromoCodesExtinguished');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
