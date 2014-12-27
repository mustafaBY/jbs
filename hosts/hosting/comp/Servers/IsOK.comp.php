<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsOK','ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Servers/IsOK]: IsOK = %s; ID = %s',$IsOK,$ID));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Is_Null($IsOK) && $IsOK){
	#-------------------------------------------------------------------------------
	if($IsOK < 25)
		$Number = 0;
	#-------------------------------------------------------------------------------
	if($IsOK < 35)
		$Number = 1;
	#-------------------------------------------------------------------------------
	if($IsOK < 45)
		$Number = 2;
	#-------------------------------------------------------------------------------
	if($IsOK < 65)
		$Number = 3;
	#-------------------------------------------------------------------------------
	if($IsOK < 75)
		$Number = 4;
	#-------------------------------------------------------------------------------
	if($IsOK < 85)
		$Number = 5;
	#-------------------------------------------------------------------------------
	if($IsOK < 95)
		$Number = 6;
	#-------------------------------------------------------------------------------
	if($IsOK >= 95)
		$Number = 9;
	#-------------------------------------------------------------------------------
	$Image = SPrintF('UpTime.%u.png',$Number);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$IsOK = NULL;
	#-------------------------------------------------------------------------------
	$Image = 'UpTime.no.png';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Message = SPrintF('%s',(Is_Null($IsOK))?'не мониторится':SPrintF('%s%%',$IsOK));
#-------------------------------------------------------------------------------
$Out = new Tag(
		'IMG',
		Array(
			'alt'		=> '+',
			'class'		=> 'Button',
			'onmouseover'	=> SPrintF("PromptShow(event,'%s',this);",$Message),
			'onclick'	=> SPrintF("ShowWindow('/Administrator/ServerUpTimeInfo',{ServerID:%u});",$ID),
			'width'		=> 16,
			'height'	=> 16,
			'src'		=> SPrintF('SRC:{/Images/UpTime/%s}',$Image)
			)
		);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


?>
