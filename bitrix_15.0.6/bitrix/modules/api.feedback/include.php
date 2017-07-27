<?
global $MESS;
IncludeModuleLangFile(__FILE__);

Class CApiFeedback
{
    function Send($event_name, $site_id, $arFields, $Duplicate="Y", $message_id=false, $user_mess=false, $mime_boundary=false, $arFieldsCodeName = array(), $arParams = array())
    {
        $strFields =  $strFieldsNames =  $SITE_NAME = "";
        $bReturn = false;
	    global $DB;

        $arFilter = Array(
            //"ID"            => $message_id,FF
            "TYPE_ID"       => $event_name,
            "SITE_ID"       => $site_id,
            "ACTIVE"        => "Y",
        );
        if($message_id) $arFilter['ID'] = $message_id;

        $arMess = array();
        $rsMess = CEventMessage::GetList($by="id", $order="asc", $arFilter);
        while($obMess = $rsMess->Fetch())
            $arMess[] = $obMess;

        $rs_sites = CSite::GetList($by="sort", $order="desc");
        while ($ar_site = $rs_sites->Fetch())
            $arSites[$ar_site['ID']] = $ar_site;

        if(count($arSites)>1 && $site_id)
            $SITE_NAME = $arSites[$site_id]['SITE_NAME'];
        else
            $SITE_NAME = COption::GetOptionString("main", "site_name", $GLOBALS["SERVER_NAME"]);

	    if($arParams['IBLOCK_ID'])
	    {
		    if(CModule::IncludeModule('iblock'))
			    $el = new CIBlockElement;
		    else
			    $arParams['IBLOCK_ID'] = false;
	    }

	    if(!empty($arMess))
        {
            foreach($arMess as $k => $v)
            {
                $sFilesFields =  $subject = '';

                //$email_to = ($v['EMAIL_TO']=='#EMAIL_TO#') ? $arFields['EMAIL_TO'] : $v['EMAIL_TO'];
                //v1.4.8
                $email_to = (strpos($v['EMAIL_TO'],'@')!==false) ? $v['EMAIL_TO'] : $arFields[str_replace('#','',$v['EMAIL_TO'])] ;
                $email_from = !$user_mess ? (($v['EMAIL_FROM'] == '#DEFAULT_EMAIL_FROM#') ? $arFields['DEFAULT_EMAIL_FROM'] : $v['EMAIL_FROM']) : $arFields['EMAIL_FROM'];
                if($v['BODY_TYPE'] == 'text')	$v['BODY_TYPE'] = 'plain';

                $headers = "MIME-Version: 1.0\n";
                $headers .= "Content-Type: multipart/mixed;\n boundary=\"{$mime_boundary}\"\n";
                $headers .= "From: {$email_from}\n";
                $headers .= "Reply-To: {$email_from}\n";

                if(!empty($arFields))
                {
                    //v1.3.2 - this for #WORK_AREA# in e-mail template
                    if(!empty($arFieldsCodeName))
                    {
                        $i = 0;
                        $cnt = count($arFieldsCodeName);
                        foreach($arFieldsCodeName as $code=>$name)
                        {
                            $i++;
                            //If empty field value
                            if(strlen($arFields[$code]))
                            {
                                if($v['BODY_TYPE'] == 'html')
                                {
                                    $strFieldsNames .= "<b>". $name ."</b><br>". $arFields[$code] ."<br><br>";
                                }
                                else
                                {
                                    $strFieldsNames .= $name ."\n". $arFields[$code];
                                    if($i != $cnt)	$strFieldsNames .= "\n\n";
                                }
                            }
                        }
                    }


                    //Include FILES
                    if($v['BODY_TYPE'] == 'html')
                        $sFilesFields ="\n\n" . $arFields['FILES'];
                    else
                    {
                        $arExpFilesLink = explode('<br>',$arFields['FILES']);
                        if(!empty($arExpFilesLink))
                        {
                            $i = 0;
                            foreach($arExpFilesLink as $fileLink)
                            {
                                $i++;
                                if(strlen(trim($fileLink)))
                                {
                                    if($i==1) $sFilesFields .= "\n";
                                    $sFilesFields .= "\n" .strip_tags(trim($fileLink));
                                }
                            }
                        }
                    }

                    $search = $replace = array();
                    $bFindFilesMacros = (strpos($v['MESSAGE'],'#FILES#') !== false) ? true : false;

                    foreach($arFields as $k2=>$v2)
                    {
                        if($k2 == 'FILES')
                            $v2 = (!$arParams['SEND_ATTACHMENT'] && !$arParams['DELETE_FILES_AFTER_UPLOAD']) ? $sFilesFields : "\n";

                        $search[] = '#'. $k2 .'#';
                        $replace[] = $v2;
                    }
                    $strFields = str_replace($search,$replace,$v['MESSAGE']);

	                //v.1.4.2
                    $subject = str_replace($search,$replace,$v['SUBJECT']);

                    if(strpos($strFields,'#SITE_NAME#') !== false)
                        $strFields = str_replace('#SITE_NAME#', $SITE_NAME, $strFields);


                    if($v['BODY_TYPE'] == 'html')
                    {
                        $message_header ='<html>
                        <head>
                        <meta http-equiv="content-type" content="text/html; charset='. SITE_CHARSET .'">
                        </head>
                        <body text="#000000" bgcolor="#FFFFFF">
                        ';
                        $message_footer ='
                        </body></html>';

                        $strFields = $message_header . $strFields . $message_footer;
                    }

	                if(strlen($strFieldsNames))
                    {
                        if(strlen($arFields['FILES']) && !$bFindFilesMacros && !$arParams['SEND_ATTACHMENT'] && !$arParams['DELETE_FILES_AFTER_UPLOAD'])
                            $strFields = str_replace('#WORK_AREA#','#WORK_AREA#'.$sFilesFields,$strFields);

                        $strFields = str_replace('#WORK_AREA#',$strFieldsNames,$strFields);
                    }


	                //Work with iblock v.1.4.2
	                if($arParams['IBLOCK_ID'])
	                {
		                //Номер заказа по шаблону
                        $TICKET_ID =  self::GetOrderNumber('TICKET_ID',$arParams['IBLOCK_ID']);
                        if($user_mess)
                            $TICKET_ID -=1;

		                if($TICKET_ID)
			                $strFields = str_replace('#TICKET_ID#', $TICKET_ID, $strFields);

		                $arProps = array(
			                'TICKET_ID' => $TICKET_ID,
			                'FILES' => $arFields['AR_FILES'],
		                );
		                $arLoadFields = array(
			                'IBLOCK_ID'         => $arParams['IBLOCK_ID'],
			                'DATE_ACTIVE_FROM'  => date($DB->DateFormatToPHP(CSite::GetDateFormat())),
			                'IBLOCK_SECTION_ID' => false,
			                'ACTIVE'            => 'N',
			                'NAME'              => strlen($arParams['FORM_TITLE']) ? $arParams['FORM_TITLE'] : '#'.$TICKET_ID,
			                'PROPERTY_VALUES'   => $arProps,
			                'DETAIL_TEXT'       => $strFields,
			                'DETAIL_TEXT_TYPE'  => $v['BODY_TYPE'],
			                'CODE'              => 'Ticket#'.$TICKET_ID
		                );

                        if(!$user_mess)
		                    $el->Add($arLoadFields,false,false,false);
	                }

	                //Include attachments in message
                    if( strlen($arFields['FILES']) && ($arParams['SEND_ATTACHMENT'] || $arParams['DELETE_FILES_AFTER_UPLOAD']) )
                        $strFields .= $sFilesFields;
                }


                // multipart boundary
                $message = "--{$mime_boundary}\n";
                $message .= "Content-Type: text/". $v['BODY_TYPE'] ."; charset=".  SITE_CHARSET ."\n";
                $message .= "Content-Transfer-Encoding: 8bit\n\n";
                $message .= htmlspecialcharsback($strFields) . "\n\n";//iso-8859-1 ::  text/plain
                $message .= "--{$mime_boundary}--";

	            if(strlen($SITE_NAME)) $subject =  str_replace('#SITE_NAME#', $SITE_NAME, $subject);
	            if(strlen($TICKET_ID)) $subject =  str_replace('#TICKET_ID#', $TICKET_ID, $subject);
	            $subject = "=?". SITE_CHARSET ."?B?". base64_encode($subject) . "?=";

	            if(!$user_mess)
		            foreach(GetModuleEvents('api.feedback', "OnBeforeEmailSend", true) as $arEvent)
			            ExecuteModuleEventEx($arEvent, array(&$event_name, &$site_id, &$arFields, &$message_id));

	            if(bxmail($email_to, $subject, $message, $headers))
                {
                    if(!$user_mess)
                        foreach(GetModuleEvents('api.feedback', "OnAfterEmailSend", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array(&$event_name, &$site_id, &$arFields, &$message_id));

                    $bReturn = true;
                }
                else
                    return false;
            }

            if($bReturn)
                return true;

        }
        else
            return false;

    }

    /**
     * FakeTranslit()
     *
     * @param string $str
     *
     * @return string
     */
    function FakeTranslit($str)
    {
        $str = trim($str);

        $trans_from = explode(",", GetMessage("TRANSLIT_FROM"));
        $trans_to = explode(",", GetMessage("TRANSLIT_TO"));

        $str = str_replace($trans_from, $trans_to, $str);

        $str = preg_replace('/\s+/u', '-', $str);

        return $str;
    }

	/**
	 * GetOrderNumber()
	 *
	 * @param string $sTicketNumPropCode
	 * @param int $IBLOCK_ID
	 *
	 * @return int
	 */
	function GetOrderNumber($sTicketNumPropCode, $IBLOCK_ID)
	{
		//For first ticket
		$TICKET_ID = 1;
		$el = new CIBlockElement();

		//found last order number from property ORDER_NUMBER, not ID
		if($IBLOCK_ID && $sTicketNumPropCode)
		{
			$db_res = $el->GetList(array('ID'=>'DESC'), array('IBLOCK_ID' => $IBLOCK_ID), false, array('nTopCount'=>1), array('ID','PROPERTY_'.$sTicketNumPropCode));
			if($ar_res = $db_res->Fetch())
			{
				$tmpTicketNum = $ar_res['PROPERTY_'.$sTicketNumPropCode.'_VALUE'];
				if(intval($tmpTicketNum))
					$TICKET_ID = intval($tmpTicketNum) + 1;
			}
		}

		return $TICKET_ID;
	}

    function GetUUID($length=10,$prefix='')
    {
        if($length>32)
            $length = 32;

        mt_srand((double)microtime()*10000);
        $chars = strtoupper(md5(uniqid(rand(), true)));

        $uuid = substr($chars, 0, $length);

        if(strlen($prefix))
            $uuid = $prefix . $uuid;

        return $uuid;
    }
}