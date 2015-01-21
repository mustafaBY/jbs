<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Length','UseLetters','UseDigits','UseSpecials');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# Наборы символов используемых для построения паролей
$Letters	= 'qwertyuiopasdfghjklzxcvbnm';
$Digits		= '0123456789';
$Specials	= '@#%/-_+=';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
$Settings = $Config['Other']['PasswordGenerator'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# Длинна пароля. Если не задано, используем дефолт из конфига
$Length = ($Length)?$Length:$Settings['Length'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Chars = '';
#-------------------------------------------------------------------------------
if(IsSet($UseLetters)){
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($UseLetters)?$Letters:'');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($Settings['UseLetters']?$Letters:''));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(IsSet($UseDigits)){
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($UseDigits)?$Digits:'');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($Settings['UseDigits']?$Digits:''));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(IsSet($UseSpecials)){
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($UseSpecials)?$Specials:'');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Chars,($Settings['UseSpecials']?$Specials:''));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# число символов по которым строим пароль
$Size = StrLen($Chars) - 1;
#-------------------------------------------------------------------------------
if($Size < 1){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Passwords/Generator]: наборы символов явно не заданы для построения пароля, используем весь диапазон'));
	#-------------------------------------------------------------------------------
	$Chars = SPrintF('%s%s',$Letters,$Digits);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Passwords/Generator]: Chars = %s',$Chars));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Password = '';
#-------------------------------------------------------------------------------
while($Length--){
	#-------------------------------------------------------------------------------
	$Char = $Chars[Mt_Rand(0,$Size)];
	#-------------------------------------------------------------------------------
	# рандомно, символ переводим в верхний регистр
	$Char = (Mt_Rand(0,2)%3)?$Char:StrToUpper($Char);
	#-------------------------------------------------------------------------------
	$Password = SPrintF('%s%s',$Password,$Char);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Password;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
