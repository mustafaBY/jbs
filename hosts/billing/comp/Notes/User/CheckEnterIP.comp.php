<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru  **/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['User']['Notes']['CheckEnterIP'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Cached = CacheManager::get(Md5(SPrintF('LastLogon_%s',$GLOBALS['__USER']['Email'])));
if(!$Cached)
	return $Result;
#-------------------------------------------------------------------------------
if(!Is_Array($Cached))
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Array = Explode(',',$Settings['ExcludeIPs']);
#-------------------------------------------------------------------------------
foreach($Array as $IP)
	if(Trim($IP) == $Cached['EnterIP'])
		return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Cached['EnterIP'] != $GLOBALS['__USER']['EnterIP']){
	#-------------------------------------------------------------------------------
	$Params = Array('EnterIP'=>$Cached['EnterIP'],'IP'=>$GLOBALS['__USER']['EnterIP'],'EnterDate'=>Date('Y-m-d H:i:s',$Cached['EnterDate']));
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY');
	$NoBody->AddHTML(TemplateReplace('Notes.CheckEnterIP',$Params));
	$Result[] = $NoBody;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
