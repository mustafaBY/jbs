<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('NotUsed');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# достаём список всех сервисов
$Where = Array(
		 "`IsHidden` = 'no'",
		 "`IsActive` = 'yes'",
);
#-------------------------------------------------------------------------------
$Services = DB_Select('ServicesOwners',Array('ID','Name','Item','Code'),Array('Where'=>$Where,'SortOn'=>'SortID'));
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#---------------------------------------------------------------------------
	$Comp = Comp_Load('Information','Активных сервисов не обнаружено','Notice');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
	return $Comp;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY');
	#-------------------------------------------------------------------------------
	$NoBody->AddChild(new Tag('H2','Прайс на оказываемые услуги'));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# перебираем сервисы
foreach($Services as $Service){
	#-------------------------------------------------------------------------------
	# достаём тарифы
	$Where = Array("`IsActive` = 'yes'",'`GroupID` = 1');
	#-------------------------------------------------------------------------------
	$Columns = Array('ID','Name','CostMonth','CostDay');
	#-------------------------------------------------------------------------------
	if($Service['Code'] == 'ISPsw')
		$Columns[] = 'ConsiderTypeID';
	#-------------------------------------------------------------------------------
	$Tariffs = DB_Select(SPrintF('%sSchemesOwners',$Service['Code']),$Columns,Array('Where'=>$Where,'SortOn'=>'SortID'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Tariffs)){
	case 'error':
		#return ERROR | @Trigger_Error(500);
		break;
	case 'exception':
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		# заголовок - названием сервиса
		#$Head = new Tag('B',SPrintF('%s (%s)',$Service['Item'],$Service['Name']));
		#$NoBody->AddChild(new Tag('DIV',Array('align'=>'center','style'=>'font-size:14px;color:black;'),$Head,new Tag('BR')));
		$NoBody->AddChild(new Tag('BR'));
		# таблица для списка тарифов этого сервиса
		$Table = Array();
		#-------------------------------------------------------------------------------
		$Tr = new Tag('TR');
		$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center','bgcolor'=>'#B9CCDF','width'=>200),new Tag('SPAN','Тариф')));
		$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center','bgcolor'=>'#B9CCDF'),new Tag('SPAN','Цена в день')));
		$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center','bgcolor'=>'#B9CCDF'),new Tag('SPAN','Цена в месяц')));
		$Table[] = $Tr;
		#$Table[] = Array('Тариф','Цена');
		#-------------------------------------------------------------------------------
		# пеербираем тарифы
		foreach($Tariffs as $Tariff){
			#-------------------------------------------------------------------------------
			$Tr = new Tag('TR');
			#-------------------------------------------------------------------------------
			$Tr->AddChild(new Tag('TD',Array('align'=>'left','class'=>'Standard'),new Tag('SPAN',$Tariff['Name'])));
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Currency',$Tariff['CostDay']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Tr->AddChild(new Tag('TD',Array('align'=>'left','class'=>'Standard'),new Tag('SPAN',$Comp)));
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Currency',$Tariff['CostMonth']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(IsSet($Tariff['ConsiderTypeID']) && $Tariff['ConsiderTypeID'] == 'Upon')
				$Comp = SPrintF('%s (единоразово)',$Comp);
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Clauses/TariffList]: ConsiderTypeID = %s',IsSet($Tariff['ConsiderTypeID'])?$Tariff['ConsiderTypeID']:'not set'));
			$Tr->AddChild(new Tag('TD',Array('align'=>'left','class'=>'Standard'),new Tag('SPAN',$Comp)));
			#-------------------------------------------------------------------------------
			$Table[] = $Tr;
			#$Table[] = Array($Tariff['Name'],$Tariff['CostMonth']);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Tables/Extended',$Table,Array('width'=>600,'border'=>1,'cellspacing'=>0,'style'=>'border: 1px black; border-collapse: collapse;'),SPrintF('%s (%s)',$Service['Item'],$Service['Name']));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody->AddChild($Comp);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# возвращаем документ
return $NoBody;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
