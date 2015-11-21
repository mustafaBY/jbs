<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Tree.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/Config.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Мастера настройки → Прочее → Настройка уведомлений');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Notifies = $Config['Notifies'];
#-------------------------------------------------------------------------------
$Methods = $Notifies['Methods'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Row = Array(new Tag('TD',Array('class'=>'Head'),'Тип сообщения'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach(Array_Keys($Methods) as $MethodID){
	#-------------------------------------------------------------------------------
	$Method = $Methods[$MethodID];
	#-------------------------------------------------------------------------------
	#if(!$Method['IsActive'])
	#	continue;
	#-------------------------------------------------------------------------------
	$Row[] = new Tag('TD',Array('class'=>'Head'),$MethodID);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = IsSet($Row2)?Array($Row2,$Row):Array($Row);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Types = $Notifies['Types'];
#-------------------------------------------------------------------------------
foreach(Array_Keys($Types) as $TypeID){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/UserNotifiesSet]: TypeID = %s',$TypeID));
	$Type = $Types[$TypeID];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Type['Title']))
		$Table[] = Array(new Tag('TD',Array('colspan'=>(SizeOf(Array_Keys($Methods)) + 1),'class'=>'Separator'),$Type['Title']));
	#-------------------------------------------------------------------------------
	$Row = Array(new Tag('TD',Array('class'=>'Comment'),$Type['Name']));
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Methods) as $MethodID){
		#-------------------------------------------------------------------------------
		$Method = $Methods[$MethodID];
		#-------------------------------------------------------------------------------
		#if(!$Method['IsActive'])
		#	continue;
		#-------------------------------------------------------------------------------
		$UseName = SPrintF('Use%s',$MethodID);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'		=> SPrintF('Notifies/Types/%s/%s',$TypeID,$UseName),
					'type'		=> 'checkbox',
					'value'		=> 1,
					'prompt'	=> (IsSet($Type[$UseName]) && !$Type[$UseName])?'Данная настройка отключена администратором':'Настройка уведомления',
					'onChange'	=> SPrintF("ConfigChange('Notifies/Types/%s/%s',(checked?1:0));",$TypeID,$UseName),
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if($Types[$TypeID][$UseName])
			$Comp->AddAttribs(Array('checked'=>'true'));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Row[] = new Tag('TD',Array('align'=>'center'),$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Table[] = $Row;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'UserNotifiesSet();','value'=>'Сохранить'));
#if(Is_Error($Comp))
#	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#$Table[] = Array(new Tag('TD',Array('colspan'=>6,'align'=>'right'),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','Administrator/Masters',new Tag('FORM',Array('name'=>'GlobalNotifiesSetForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
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

?>
