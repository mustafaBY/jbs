<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('MenuPath','Replace');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
#-------------------------------------------------------------------------------
$DOM = &$Links['DOM'];
#-------------------------------------------------------------------------------
if(!Comp_IsLoaded('Menus/List')){
	#-------------------------------------------------------------------------------
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/ListMenu.js}'));
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
	$Table = new Tag('TABLE',Array('id'=>'ListMenu','class'=>'Standard','cellspacing'=>0,'cellpadding'=>0,'style'=>'display:none;position:absolute;top:-1000;left:-1000;'));
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Floating',$Table);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$XML = Styles_XML(SPrintF('Menus/%s',$MenuPath));
if(Is_Error($XML))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$CacheID = Md5($MenuPath);
#-------------------------------------------------------------------------------
$Items = $XML['Items'];
#-------------------------------------------------------------------------------
if(!IsSet($Links[$CacheID])){
	#-------------------------------------------------------------------------------
	foreach($Items as $Item){
		#-------------------------------------------------------------------------------
		if(IsSet($Item['JavaScript'])){
			#-------------------------------------------------------------------------------
			$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>SPrintF('SRC:{Js/%s}',$Item['JavaScript'])));
			#-------------------------------------------------------------------------------
			$DOM->AddChild('Head',$Script);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links[$CacheID] = UniqID();
#-------------------------------------------------------------------------------
$Replace = Array_ToLine(Is_Array($Replace)?$Replace:Array('Replace'=>$Replace),'%');
#-------------------------------------------------------------------------------
foreach($Items as $Item){
	#-------------------------------------------------------------------------------
	$Href = $Item['Href'];
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Replace) as $Key)
		$Href = Str_Replace($Key,($Replace[$Key])?$Replace[$Key]:'0000',$Href);
	#-------------------------------------------------------------------------------
	$Array[] = SPrintF("{Text:'%s',Href:'%s',Icon:'SRC:{Images/Icons/%s}'}",$Item['Text'],$Href,$Item['Icon']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$String = Implode(',',$Array);
#-------------------------------------------------------------------------------
$Button = new TAG('BUTTON',Array('class'=>'Standard','style'=>'width: 15px','onclick'=>SPrintF('ListMenuShow(event,[%s]);',$String)));
#-------------------------------------------------------------------------------
$LinkID = UniqID('Button');
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links[$LinkID] = &$Button;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Prompt',$LinkID,'Расширенное меню');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
UnSet($Links[$LinkID]);
#-------------------------------------------------------------------------------
$Button->AddChild(new Tag('IMG',Array('align'=>'center','width'=>5,'height'=>10,'style'=>'display:block;','src'=>'SRC:{Images/ListMenuArrow.gif}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Button;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
