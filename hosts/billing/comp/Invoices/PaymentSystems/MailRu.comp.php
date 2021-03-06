<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda (for www.host-food.ru) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('PaymentSystemID','InvoiceID','Summ');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['MailRu'];
#-------------------------------------------------------------------------------
$Send = $Settings['Send'];
#-------------------------------------------------------------------------------
$Send['sum'] = Round($Summ/$Settings['Course'],2);
#-------------------------------------------------------------------------------
$Send['issuer_id'] = $InvoiceID;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Invoice/Number',$InvoiceID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
$Send['description'] .= SPrintF('%s, %s (%s)',$Comp,Translit($__USER['Name']),$__USER['Email']);
#-------------------------------------------------------------------------------
$Send['message'] = $Send['description'];
#-------------------------------------------------------------------------------
$sha = sha1($Settings['Hash']);
$Hash = Array(
  #-----------------------------------------------------------------------------
  $Send['currency'],
  $Send['description'],
  $Send['issuer_id'],
  $Send['message'],
  $Send['shop_id'],
  $Send['sum'],
  $sha
);
#-------------------------------------------------------------------------------
$Send['signature'] = sha1(Implode('',$Hash));
#-------------------------------------------------------------------------------
return $Send;
#-------------------------------------------------------------------------------

?>
