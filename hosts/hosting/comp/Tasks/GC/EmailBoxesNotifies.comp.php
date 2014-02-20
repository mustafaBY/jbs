<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/HostingServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = $Params['EmailBoxesNotifiesSettings'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$HostingServers = DB_Select('HostingServers',Array('ID','Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($HostingServers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	foreach($HostingServers as $HostingServer){
		#-------------------------------------------------------------------------------
		$ClassHostingServer = new HostingServer();
		#-------------------------------------------------------------------------------
		$IsSelected = $ClassHostingServer->Select((integer)$HostingServer['ID']);
		#-------------------------------------------------------------------------------
		switch(ValueOf($IsSelected)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'true':
			#-------------------------------------------------------------------------------
			$Users = $ClassHostingServer->GetEmailBoxes();
			#-------------------------------------------------------------------------------
			switch(ValueOf($Users)){
			case 'error':
				# No more...
			case 'exception':
				# No more...
				break 2;
			case 'array':
				#-------------------------------------------------------------------------------
				if(Count($Users)){
					#-------------------------------------------------------------------------------
					$Array = Array();
					#-------------------------------------------------------------------------------
					foreach(Array_Keys($Users) as $UserID)
						$Array[] = SPrintF("'%s'",$UserID);
					#-------------------------------------------------------------------------------
					$Where = SPrintF('`ServerID` = %u AND `Login` IN (%s)',$HostingServer['ID'],Implode(',',$Array));
					#-------------------------------------------------------------------------------
					$HostingOrders = DB_Select('HostingOrdersOwners',Array('ID','UserID','Login'),Array('Where'=>$Where));
					#-------------------------------------------------------------------------------
					switch(ValueOf($HostingOrders)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						# No more...
						break;
					case 'array':
						#-------------------------------------------------------------------------------
						$Heads = Array(SPrintF('From: admin@%s',$HostingServer['Address']),'MIME-Version: 1.0','Content-Transfer-Encoding: 8bit',SPrintF('Content-Type: multipart/mixed; boundary="----==--%s"',HOST_ID));
						#-------------------------------------------------------------------------------
						foreach($HostingOrders as $HostingOrder){
							#-------------------------------------------------------------------------------
							$Boxes = $Users[$HostingOrder['Login']];
							#-------------------------------------------------------------------------------
							foreach($Boxes as $Email=>$Box){
								#-------------------------------------------------------------------------------
								$Total = Next($Box);
								if(!$Total)
									continue;
								#-------------------------------------------------------------------------------
								$Used = Prev($Box);
								#-------------------------------------------------------------------------------
								$Usage = ($Used/$Total)*100;
								#-------------------------------------------------------------------------------
								if($Usage > $Settings['EmailBoxesNotifiesPercent']){
									#-------------------------------------------------------------------------------
									$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$HostingOrder['UserID'],'TypeID'=>'Email','Params'=>Array($Email,'Квота почтового ящика',TemplateReplace('Tasks.GC.EmailBoxesNotifies',Array('Email'=>$Email,'Usage'=>$Usage),FALSE),Implode("\n",$Heads))));
									#-------------------------------------------------------------------------------
									switch(ValueOf($IsAdd)){
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
								}
								#-------------------------------------------------------------------------------
							}
							#-------------------------------------------------------------------------------
						}
						#-------------------------------------------------------------------------------
						break;
						#-------------------------------------------------------------------------------
					default:
						return ERROR | @Trigger_Error(101);
					}
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				break 2;
				#-------------------------------------------------------------------------------
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
