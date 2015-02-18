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
$Settings = $Config['Interface']['User']['Notes']['CheckReferer'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['ShowCheckReferer'])
	return $Result;
#-------------------------------------------------------------------------------
# также, проверяем используется ли проверка отсутствия реферера.
# если нет - нет смысла чё-то выводить юзеру
if($Config['Other']['Modules']['Security']['IsNoReferer'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($_SERVER["HTTP_REFERER"])){
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY');
	$NoBody->AddHTML(TemplateReplace('Notes.User.CheckReferer'));
	$Result[] = $NoBody;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
