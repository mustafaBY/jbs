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
$ServerID	= (integer) @$Args['ServerID'];
$TemplateID	=  (string) @$Args['TemplateID'];
$ServiceID	= (integer) @$Args['ServiceID'];
$TemplatesIDs	=  (string) @$Args['TemplatesIDs'];
$Window		=  (string) @$Args['Window'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Tree.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if($ServerID){
	#-------------------------------------------------------------------------------
	$Server = DB_Select('Servers',Array('ID','ServiceID','(SELECT `Code` FROM `Services` WHERE `Services`.`ID` = `ServiceID`) as `Code`','Params'),Array('UNIQ','ID'=>$ServerID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Server)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$TemplateID = $Server['Code'];
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Server = Array(
			'ServersGroupID'	=> 0,
			'IsDefault'		=> TRUE,
			'Protocol'		=> 'ssl',
			'Address'		=> 'srv1.isp.su',
			'Port'			=> 443,
			'PrefixAPI'		=> '/manager/ispmgr',
			'Login'			=> 'root',
			'Password'		=> '',
			'Notice'		=> "Intel Xeon 1.8\nАдминистратор: Иванов Иван\n"
			);
	#-------------------------------------------------------------------------------
}
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
$Config = Config();
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ServerEditForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
if($Window){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'  => 'Window',
				'type'  => 'hidden',
				'value' => $Window
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array('`IsActive` = "yes"','`IsHidden` != "yes"');
#-------------------------------------------------------------------------------
$Services = DB_Select('ServicesOwners',Array('ID','Code','Item'),Array('Where'=>$Where,'SortOn'=>'SortID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	break;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Options = Array('Любой активный сервис');
#---------------------------------------------------------------------------
if(Is_Array($Services))
	foreach($Services as $Service)
		$Options[$Service['ID']] = SPrintF('%s (%s)',$Service['Code'],$Service['Item']);
#-------------------------------------------------------------------------------
$Services = Comp_Load('Form/Select',Array('name'=>'ServiceID'),$Options,$ServiceID);
if(Is_Error($Services))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$TemplateID){
	#-------------------------------------------------------------------------------
	$DOM->AddText('Title','Новый сервер');
	#-------------------------------------------------------------------------------
	$Templates = $Config['Servers']['Templates'];
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	$TemplatesIDs = ($TemplatesIDs?Explode(',',$TemplatesIDs):Array());
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Templates) as $TemplateID){
		#-------------------------------------------------------------------------------
		if(Count($TemplatesIDs) && !In_Array($TemplateID,$TemplatesIDs))
			continue;
		#-------------------------------------------------------------------------------
		$Template = $Templates[$TemplateID];
		#-------------------------------------------------------------------------------
		if($Template['IsActive'])
			$Options[$TemplateID] = $Templates[$TemplateID]['Name'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!Count($Options))
		return new gException('TEMPLATES_NOT_DEFINED','Шаблоны не определены');
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'TemplateID'),$Options);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table = Array(Array('Шаблон',$Comp));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = Array('Сервис',$Services);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'onclick' => "ShowWindow('/Administrator/ServerEdit',FormGet(form));",
				'type'    => 'button',
				'value'   => 'Продолжить'
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$DOM->AddText('Title',SPrintF('%s: %s',($ServerID)?'Редактирование':'Добавление',$Config['Servers']['Templates'][$TemplateID]['Name']));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'  => 'TemplateID',
				'type'  => 'hidden',
				'value' => $TemplateID
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'  => 'ServiceID',
				'type'  => 'hidden',
				'value' => $ServiceID
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table = Array();
	#-------------------------------------------------------------------------------
	$Table[] = 'Общие параметры';
	#-------------------------------------------------------------------------------
	$Table[] = Array('Сервис',$Services);
	#-------------------------------------------------------------------------------
	$ServersGroups = DB_Select('ServersGroups',Array('ID','Name'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($ServersGroups)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		break;
	case 'array':
		# No more...
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Options = Array(0=>'Не входит в группу');
	#-------------------------------------------------------------------------------
	if(Is_Array($ServersGroups))
		foreach($ServersGroups as $ServersGroup)
			$Options[$ServersGroup['ID']] = $ServersGroup['Name'];
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'ServersGroupID','prompt'=>'Группа серверов, в которую входит сервер'),$Options,$Server['ServersGroupID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Группа серверов',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'IsDefault','type'=>'checkbox','value'=>'yes'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Server['IsDefault'])
		$Comp->AddAttribs(Array('checked'=>'yes'));
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsDefault\'); return false;'),'Основной в группе'),$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = 'Параметры соединения';
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'Protocol'),Array('ssl'=>'ssl','tcp'=>'tcp'),$Server['Protocol']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Протокол',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'class'	=> 'Duty',
				'type'	=> 'text',
				'name'	=> 'Address',
				'prompt'=> 'Используется для связи с сервером',
				'value'	=> $Server['Address']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Адрес сервера',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'class'	=> 'Duty',
				'type'  => 'text',
				'name'  => 'Port',
				'prompt'=> 'Порт на который устанавливать соединение с сервером (для SSL - обычно 443, для HTTP - 80)',
				'value' => $Server['Port']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Порт',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'class'	=> 'Duty',
				'type'  => 'text',
				'name'  => 'PrefixAPI',
				'prompt'=> 'Префикс для API софта используемого на сервере',
				'value' => $Server['PrefixAPI']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Префикс API',$Comp);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'	=> 'text',
				'class'	=> 'Duty',
				'prompt'=> 'Имя администратора или реселлера имеющего права на создание новых клиентов, на сервере, через систему управления',
				'name'	=> 'Login',
				'value'	=> $Server['Login']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Логин',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'class' => 'Duty',
				'type'  => ($ServerID?'password':'text'),
				'name'  => 'Password',
				'value' => $Server['Password']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Пароль',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($ServerID){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'  => 'ServerID',
					'type'  => 'hidden',
					'value' => $Server['ID']
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Template = System_XML(SPrintF('servers/%s.xml',$TemplateID));
	if(Is_Error($Template))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Attribs = $Template['Attribs'];
	#-------------------------------------------------------------------------------
	$Replace = Array_ToLine($__USER,'%');
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Attribs) as $AttribID){
		#-------------------------------------------------------------------------------
		$Attrib = $Attribs[$AttribID];
		#-------------------------------------------------------------------------------
		if(IsSet($Attrib['Title']))
			$Table[] = $Attrib['Title'];
		#-------------------------------------------------------------------------------
		if($ServerID){
			#-------------------------------------------------------------------------------
			$Value = (string)@$Server['Attribs'][$AttribID];
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Value = $Attrib['Value'];
			#-------------------------------------------------------------------------------
			foreach(Array_Keys($Replace) as $Key)
				$Value = Str_Replace($Key,$Replace[$Key],$Value);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Params = &$Attrib['Attribs'];
		#-------------------------------------------------------------------------------
		$Params['name'] = $AttribID;
		#-------------------------------------------------------------------------------
		if($Attrib['IsDuty'])
			$Params['class'] = 'Duty';
		#-------------------------------------------------------------------------------
		switch($Attrib['Type']){
		case 'Input':
			#-------------------------------------------------------------------------------
			$Params['value'] = $Value;
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Input',$Params);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(101);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'TextArea':
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/TextArea',$Params,$Value);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(101);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'Select':
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Select',$Params,$Attrib['Options'],$Value);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(101);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY',new Tag('SPAN',(IsSet($Attrib['CommentAttribs'])?$Attrib['CommentAttribs']:Array()),$Attrib['Comment']));
		#-------------------------------------------------------------------------------
		$NoBody->AddChild(new Tag('BR'));
		#-------------------------------------------------------------------------------
		if(IsSet($Attrib['Example']))
			$NoBody->AddChild(new Tag('SPAN',Array('class'=>'Comment'),SPrintF('Например: %s',$Attrib['Example'])));
		#-------------------------------------------------------------------------------
		$Table[] = Array($NoBody,$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = 'Заметка';
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/TextArea',
			Array(
				'name'		=> 'Notice',
				'style'		=> 'width:100%;',
				'rows'		=> 5,
				'prompt'	=> 'Информация о сервере, "чисто для себя"'
				),
			$Server['Notice']
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'    => 'button',
				'onclick' => SPrintF("FormEdit('/Administrator/API/ServerEdit','ServerEditForm','%s');",($ServerID?'Сохранение настроек':'Добавление сервера')),
				'value'   => ($ServerID?'Сохранить':'Добавить сервер')
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table,Array('style'=>'width:500px;'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
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
