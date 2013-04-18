<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ClauseID','IsEdit','Preview');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Preview = IsSet($Preview)?$Preview:FALSE;
#Debug(SPrintF("[comp/Clauses/Load]: Preview = %s",$Preview));
#-------------------------------------------------------------------------------
$Where = SPrintF("`ID` = %u OR `Partition` = '%s'",$ClauseID,DB_Escape($ClauseID));
#-------------------------------------------------------------------------------
$Clause = DB_Select('Clauses','*',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Clause)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    #---------------------------------------------------------------------------
    $P = new Tag('P');
    #---------------------------------------------------------------------------
    $Parse = '<P><SPAN>К сожалению, необходимая статья не найдена: </SPAN><U>%s</U></P>';
    #---------------------------------------------------------------------------
    $P->AddHTML(SPrintF($Parse,$ClauseID));
    #---------------------------------------------------------------------------
    return Array('Title'=>'Статья не найдена','DOM'=>$P,'IsExists'=>FALSE);
  case 'array':
    #---------------------------------------------------------------------------
    $Clause = Current($Clause);
    #---------------------------------------------------------------------------
    if(!$Clause['IsPublish'] && !$Preview){
      #-------------------------------------------------------------------------
      $P = new Tag('P');
      #-------------------------------------------------------------------------
      $Parse = '<P><SPAN>К сожалению, необходимая статья еще не опубликована: </SPAN><U>%s</U></P>';
      #---------------------------------------------------------------------------
      $P->AddHTML(SPrintF($Parse,$Clause['Partition']));
      #-------------------------------------------------------------------------
      return Array('Title'=>'Статья не опубликована','DOM'=>$P,'IsExists'=>TRUE);
    }
    #---------------------------------------------------------------------------
    $Text = $Clause['Text'];
    #---------------------------------------------------------------------------
    if(Preg_Match('/@link:([a-zA-Z0-9\/\_\-]+)/',$Text,$Matches)){
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Clauses/Load',Next($Matches));
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      return $Comp;
    }
    #---------------------------------------------------------------------------
    $Title = (!$Clause['IsPublish'])?SPrintF('НЕ ОПУБЛИКОВАНО: %s',$Clause['Title']):$Clause['Title'];
    #$Title = $Clause['Title'];
    #---------------------------------------------------------------------------
    $Result = Array('Title'=>$Title,'IsExists'=>TRUE);
    #---------------------------------------------------------------------------
    $Replace = Array('HOST_ID'=>HOST_ID,'Clause'=>Array('ID'=>$Clause['ID']));
    #---------------------------------------------------------------------------
    if(IsSet($GLOBALS['__USER']))
      $Replace['__USER'] = $GLOBALS['__USER'];
    #---------------------------------------------------------------------------
    $Replace = Array_ToLine($Replace,'%');
    #---------------------------------------------------------------------------
    foreach(Array_Keys($Replace) as $Key)
      $Text = Str_Replace($Key,$Replace[$Key],$Text);
    #---------------------------------------------------------------------------
    if($Clause['IsDOM']){
      #-------------------------------------------------------------------------
      $DOM = String_XML_Parse($Text);
      if(Is_Exception($DOM))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM = Current($DOM->Childs);
    }else
      $DOM = new Tag('DIV',Array('force-escape'=>'yes'),$Text);
    #---------------------------------------------------------------------------
    $Result['DOM'] = $DOM;
    #---------------------------------------------------------------------------
    if($IsEdit){
      #-------------------------------------------------------------------------
      if(IsSet($GLOBALS['__USER'])){
        #-----------------------------------------------------------------------
        $Permission = Permission_Check('ClauseEdit',(integer)$GLOBALS['__USER']['ID'],(integer)$Clause['AuthorID']);
        #-----------------------------------------------------------------------
        switch(ValueOf($Permission)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return ERROR | @Trigger_Error(400);
          case 'false':
            # No more...
          break;
          case 'true':
            #-------------------------------------------------------------------
            $NoBody = new Tag('NOBODY');
            #-------------------------------------------------------------------
            $NoBody->AddChild($DOM);
            #-------------------------------------------------------------------
            $Div = new Tag('DIV',Array('align'=>'right','id'=>'ClauseTrash'),new Tag('HR',Array('align'=>'right','width'=>'40%')));
            #-------------------------------------------------------------------
            $Div->AddChild(new Tag('A',Array('href'=>SPrintF("javascript: var Window = window.open('/Administrator/ClauseEdit?ClauseID=%s','ClauseEdit',SPrintF('left=%%u,top=%%u,width=800,height=680,toolbar=0, scrollbars=1, location=0',(screen.width-800)/2,(screen.height-600)/2));",$Clause['ID'])),'[редактировать]'));
            #-------------------------------------------------------------------
            $NoBody->AddChild($Div);
            #-------------------------------------------------------------------
            $Result['DOM'] = $NoBody;
          break;
          default:
            return ERROR | @Trigger_Error(101);
        }
      }
    }
    #---------------------------------------------------------------------------
    return $Result;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
