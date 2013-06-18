<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('MessageID','CreateDate','UserID','OwnerID','Content','FileName','IsVisible','VoteBall');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Upload.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$IsVisible && !$GLOBALS['__USER']['IsAdmin'])
	#return SPrintF('<!-- invisible #%s -->', $MessageID);
	return "";
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$User = DB_Select('Users',Array('ID','GroupID','EnterDate','Email','Name','Sign','(SELECT SUM(`Summ`) FROM `InvoicesOwners` WHERE `InvoicesOwners`.`StatusID`="Payed" AND `InvoicesOwners`.`UserID`=`Users`.`ID`) AS `TotalPayments`'),Array('UNIQ','ID'=>$UserID));
#-------------------------------------------------------------------------------
switch(ValueOf($User)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	# No more...
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$CreateDate = Comp_Load('Formats/Date/Extended',$CreateDate);
if(Is_Error($CreateDate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Group = DB_Select('Groups','Name',Array('UNIQ','ID'=>$User['GroupID']));
#-------------------------------------------------------------------------------
switch(ValueOf($Group)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	# No more...
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем наличие аттача
$FileLength = GetUploadedFileSize('EdesksMessages', $MessageID);
#-------------------------------------------------------------------------------
if((integer)$FileLength){
	#-------------------------------------------------------------------------------
	# проверяем наличие точки в имени (значит расширение определить удастся)
	if(StrRIPos($FileName,'.')){
		#-------------------------------------------------------------------------------
		# проверяем что это картинка
		if(!IsSet($_COOKIE['EdeskNoPreview']) && In_Array(StrToLower(SubStr($FileName,StrRIPos($FileName,'.') + 1,4)),Array('png','gif','jpg','jpeg'))){
			#-------------------------------------------------------------------------------
			# добавляем к тексту превьюху
			$Content = SPrintF("%s\n\n[image]%s://%s/FileDownload?TypeID=EdesksMessages&FileID=%u[/image]",$Content,Url_Scheme(),HOST_ID,$MessageID);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Params = Array('User'=>$User,'Status'=>((Time() - $User['EnterDate']) < 600?'OnLine':'OffLine'),'Delete'=>'','MessageID'=>SPrintF('%06u',$MessageID),'CreateDate'=>$CreateDate,'Group'=>$Group);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Text = Comp_Load('Edesks/Text',Array('String'=>$Content,'IsLockText'=>($OwnerID != @$GLOBALS['__USER']['ID'])));
if(Is_Error($Text))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Params['Text'] = $Text;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$EnterDate = Comp_Load('Formats/Date/Remainder',(Time() - $User['EnterDate']));
if(Is_Error($EnterDate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Params['EnterDate'] = $EnterDate;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Params['BgColor'] = (!$IsVisible)?'FFE4E1':(($UserID != $OwnerID)?'FFFFFF':'FDF6D3');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($__USER['IsAdmin'])
	$Params['Delete'] = SPrintF('<a href="JavaScript:EdeskMessageEdit(%u,\'%s\');">[редактировать]</a> | <a href="JavaScript:ShowConfirm(\'Вы подтверждаете удаление сообщения?\',\'AjaxCall(\\\'/API/EdeskMessageDelete\\\',{MessageID:%u},\\\'Удаление сообщения\\\',\\\'GetURL(document.location);\\\');\');" onmouseover="PromptShow(event,\'Удалить это сообщение\',this);">[удалить]</a>',$MessageID,AddcSlashes(HtmlSpecialChars($Content),"\0\n\r\\\'"),$MessageID);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if((integer)$FileLength){
	#-------------------------------------------------------------------------------
	$Delete = ($__USER['IsAdmin'])?SPrintF('<a href="JavaScript:ShowConfirm(\'Вы подтверждаете удаление файла?\',\'AjaxCall(\\\'/Administrator/API/FileDelete\\\',{Table:\\\'EdesksMessages\\\',ID:%u},\\\'Удаление файла\\\',\\\'GetURL(document.location);\\\');\');" onmouseover="PromptShow(event,\'Удалить это вложение\',this);">[удалить]</a>',$MessageID):' ';
	#-------------------------------------------------------------------------------
	$Params2 = Array('Delete'=>$Delete,'FileName'=>$FileName,'FileSize'=>SPrintF('%01.2f',$FileLength/1024),'MessageID'=>$MessageID);
	#-------------------------------------------------------------------------------
	#$Table->AddHTML(TemplateReplace('Edesks.Message.Uploaded',$Params));
	$Params['File'] = TemplateReplace('Edesks.Message.Uploaded',$Params2);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Params['File'] = '';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Comp_IsLoaded('Edesks/Message')){
	#-------------------------------------------------------------------------------
	$Links = &Links();
	#-------------------------------------------------------------------------------
	$DOM = &$Links['DOM'];
	#-------------------------------------------------------------------------------
	$Script = "function EdeskMessageEdit(MessageID,Message){ ShowAnswer('Сообщение','Сохранить',Message,SPrintF('AjaxCall(\"/API/EdeskMessageEdit\",{MessageID:%u,Message:__VALUE__},\"Сохранение сообщения\",\"GetURL(document.location);\")',MessageID)); }";
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript'),$Script));
	#-------------------------------------------------------------------------------
	# TODO всё что про prompt надо бы причесать как-то...
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Prompt.js}'));
	$DOM->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/TicketStars.js}'));
	$DOM->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Css',Array('Prompt'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	foreach($Comp as $Css)
		$DOM->AddChild('Head',$Css);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Span = new Tag('SPAN');
#-------------------------------------------------------------------------------
# Mb_StrLen закомментил, откуда оно не знаю, вероятно проблемы какие-то на больших значениях... посмотрим.
if(IsSet($GLOBALS['__USER']) /*&& Mb_StrLen($Content) < 1000*/){
	#-------------------------------------------------------------------------------
	if(!$__USER['IsAdmin']){
		#-------------------------------------------------------------------------------
		if($__USER['ID'] != $UserID){
			#-------------------------------------------------------------------------------
			$VoteTitle = Array(
						'0'     => 'Совсем плохо',
						'1'     => 'Очень плохо',
						'2'     => 'Плохо',
						'3'     => 'Не очень хорошо',
						'4'     => 'Нейтрально',
						'5'     => 'Удовлетворительно',
						'6'     => 'Хорошо',
						'7'     => 'Очень хорошо',
						'8'     => 'Отлично'
					);
			#-------------------------------------------------------------------------------
			$VoteMessage = SPrintF('Пожалуйста, оцените ответ сотрудника [%s]: ',($VoteBall > 0)?$VoteTitle[($VoteBall - 1)]:'без оценки');
			#-------------------------------------------------------------------------------
			$Span->AddChild(new Tag('SPAN',$VoteMessage));
			#-------------------------------------------------------------------------------
			for ($i = 0; $i < 9; $i++){
				#-------------------------------------------------------------------------------
			        $Img = new Tag('IMG',
						Array(
							'id'            =>SPrintF('star_%d_%d', $MessageID, $i),
							'src'           =>'SRC:{Images/Icons/DisableStar.png}',
							'onMouseOver'   => SPrintF('selectStars(event, %d, %d);PromptShow(event,\'%s\',this);',$MessageID, $i, $VoteTitle[$i]),
							'onClick'       => SPrintF("AjaxCall('/API/TicketVote',{MessageID:%u,VoteBall:%u},'Оценка сообщения','ShowTick(\"Ваша оценка \'%s\' успешно сохранена\");');",$MessageID,$i+1,$VoteTitle[$i]),
							#'title'         => $VoteTitle[$i],
							'style'		=> 'cursor: pointer;'
						));
				#-------------------------------------------------------------------------------
				$Span->AddChild($Img);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Span->AddChild(new Tag('SPAN','-'));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
  	}else{	# $IsAdmin false->true
		#-------------------------------------------------------------------------------
		# дополнительно проверяем - не сотрудник ли это, для сотрудников не надо линки в подпись лепить
		#Debug("[comp/Edesks/Message]: check for links, user id = " . (integer)$User['ID']);
		$IsPermission = Permission_Check('/Administrator/',(integer)$User['ID']);
		switch(ValueOf($IsPermission)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'false':
			#-------------------------------------------------------------------------------
			# не сотрудник, выводим всё
			#-------------------------------------------------------------------------------
			# шукаем его заказы на услуги
			$Columns = Array('Item','Code','Name');
			$Where = Array('`Services`.`ID`=`OrdersOwners`.`ServiceID`',SPrintF('`OrdersOwners`.`UserID`=%s',$UserID));
			$Items = DB_Select(Array('OrdersOwners','Services'),$Columns,Array('Where'=>$Where,'GroupBy'=>'Code','SortOn'=>'`Services`.`SortID`'));
			#-------------------------------------------------------------------------------
			switch(ValueOf($Items)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				# No orders.
				break;
			case 'array':
				#-------------------------------------------------------------------------------
				foreach($Items as $Item){
					#-------------------------------------------------------------------------------
					$UrlPart = $Item['Code'];
					#-------------------------------------------------------------------------------
					if($Item['Code'] == 'Domains'){$UrlPart = 'Domain';}
					if($Item['Code'] == 'Default'){$UrlPart = 'Services';}
					#-------------------------------------------------------------------------------
					$LinkTarget = SPrintF('/Administrator/%sOrders?Search=%s&PatternOutID=Default',$UrlPart,$User['Email']);
					#-------------------------------------------------------------------------------
					$UserLinks = Comp_Load('Formats/String',SPrintF('[%s]',$Item['Code']),10,$LinkTarget);
					if(Is_Error($UserLinks))
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
					$UserLinks->AddAttribs(Array('target'=>'_blank','onMouseOver'=>SPrintF('PromptShow(event,\'Заказы на %s (%s)\',this);',$Item['Item'],$Item['Name'])));
					#-------------------------------------------------------------------------------
					$Span->AddChild($UserLinks);
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			default:
					return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			$InvoicesText = ($User['TotalPayments'] > 0)?SPrintF('[%s]',$User['TotalPayments']):'[Счета]';
			#-------------------------------------------------------------------------------
			$UserLinks = Comp_Load('Formats/String',$InvoicesText,12,SPrintF('/Administrator/Invoices?Search=%s&PatternOutID=Default',$User['Email']));
			if(Is_Error($UserLinks))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$UserLinks->AddAttribs(Array('target'=>'_blank','onMouseOver'=>'PromptShow(event,\'Сумма оплаченных счетов пользователя\',this);'));
			$Span->AddChild($UserLinks);
			#-------------------------------------------------------------------------------
			$UserLinks = Comp_Load('Formats/String','[Тикеты]',10,SPrintF('/Administrator/Tickets?Search=%s&PatternOutID=Default',$User['Email']));
			if(Is_Error($UserLinks))
				return ERROR | @Trigger_Error(500);
			$UserLinks->AddAttribs(Array('target'=>'_blank','onMouseOver'=>'PromptShow(event,\'Найти все тикеты пользователя\',this);'));
			$Span->AddChild($UserLinks);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'true':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Params['Links'] = IsSet($Span)?$Span->ToXMLString():'-';
$Table = new Tag('TABLE',Array('class'=>'EdeskMessage','cellspacing'=>5,'height'=>'100%','width'=>'100%'));
$Table->AddHTML(TemplateReplace(SPrintF('Edesks.Message.TABLE.%s',IsSet($_COOKIE['EdesksDisplay'])?$_COOKIE['EdesksDisplay']:'Left'),$Params));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Table;
#-------------------------------------------------------------------------------

?>
