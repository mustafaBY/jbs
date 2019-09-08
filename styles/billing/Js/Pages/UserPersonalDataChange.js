//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function UserPersonalDataChange() {
	//------------------------------------------------------------------------------
	var $Form = document.forms['UserPersonalDataChangeForm'];
	//------------------------------------------------------------------------------
	$HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if (!$HTTP.Resource) {
		//------------------------------------------------------------------------------
		alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onLoaded = function() {
		//------------------------------------------------------------------------------
		HideProgress();
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer) {
		//------------------------------------------------------------------------------
		switch ($Answer.Status) {
		case 'Error':
			//------------------------------------------------------------------------------
			ShowAlert($Answer.Error.String, 'Warning');
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Exception':
			//------------------------------------------------------------------------------
			ShowAlert(ExceptionsStack($Answer.Exception), 'Warning');
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Ok':
			//------------------------------------------------------------------------------
			try {
				//------------------------------------------------------------------------------
				document.getElementById('UserFoto').src = '/UserFoto?Rand=' + (Math.round(Math.random() * 8999) + 1000);
				//------------------------------------------------------------------------------
			} catch (exception) {
				//------------------------------------------------------------------------------
				Debug(exception);
				//------------------------------------------------------------------------------
			};
			//------------------------------------------------------------------------------
			//ShowTick('Информация успешно сохранена');
			ShowWindow('/UserPersonalDataChange');
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		default:
			//------------------------------------------------------------------------------
			alert('Не известный ответ');
			//------------------------------------------------------------------------------
		}
		//------------------------------------------------------------------------------
	};
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	var $Args = FormGet($Form);
	//------------------------------------------------------------------------------
	if (!$HTTP.Send('/API/UserPersonalDataChange', $Args)) {
		//------------------------------------------------------------------------------
		alert('Не удалось отправить запрос на сервер');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//ShowProgress('Смена персональных данных');
	//------------------------------------------------------------------------------
	ShowWindow('/UserPersonalDataChange');
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

function ContactDelete($ContactID){
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if(!$HTTP.Resource){
		//------------------------------------------------------------------------------
		alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	$HTTP.onLoaded = function(){
		//------------------------------------------------------------------------------
		HideProgress();
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer){
		//------------------------------------------------------------------------------
		switch($Answer.Status){
		case 'Error':
			ShowAlert($Answer.Error.String,'Warning');
			break;
		case 'Exception':
			ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
			break;
		case 'Ok':
			//GetURL(SPrintF('%s?IsUpdate=yes',document.location));
			ShowWindow('/UserPersonalDataChange');
			break;
		default:
			alert('Не известный ответ');
		}
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	var $Args = {TableID:'Contacts',RowsIDs:$ContactID};
	//------------------------------------------------------------------------------
	if(!$HTTP.Send('/API/Delete',$Args)){
		//------------------------------------------------------------------------------
		alert('Не удалось отправить запрос на сервер');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//ShowProgress('Удаление заказов');
	ShowWindow('/UserPersonalDataChange');
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

