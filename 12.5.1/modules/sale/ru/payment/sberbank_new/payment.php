<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>���������</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?= LANG_CHARSET ?>">
<style type="text/css">
H1 {font-size: 12pt;}
p, ul, ol, h1 {margin-top:6px; margin-bottom:6px} 
td {font-size: 9pt;}
small {font-size: 7pt;}
body {font-size: 10pt;}
</style>
</head>
<body bgColor="#ffffff">

<table border="0" cellspacing="0" cellpadding="0" style="width:180mm; height:145mm;">
<tr valign="top">
	<td style="width:50mm; height:70mm; border:1pt solid #000000; border-bottom:none; border-right:none;" align="center">
	<b>���������</b><br>
	<font style="font-size:53mm">&nbsp;<br></font>
	<b>������</b>
	</td>
	<td style="border:1pt solid #000000; border-bottom:none;" align="center">
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td align="right"><small><i>����� � ��-4</i></small></td>
			</tr>
			<tr>
				<td style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("COMPANY_NAME"))?></td>
			</tr>
			<tr>
				<td align="center"><small>(������������ ���������� �������)</small></td>
			</tr>
		</table>

		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td style="width:37mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("INN"))."/".(CSalePaySystemAction::GetParamValue("KPP"))?></td>
				<td style="width:9mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("SETTLEMENT_ACCOUNT"))?></td>
			</tr>
			<tr>
				<td align="center"><small>(��� ���������� �������)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(����� ����� ���������� �������)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>�&nbsp;</td>
				<td style="width:73mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_NAME"))?></td>
				<td align="right">���&nbsp;&nbsp;</td>
				<td style="width:33mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_BIC"))?></td>
			</tr>
			<tr>
				<td></td>
				<td align="center"><small>(������������ ����� ���������� �������)</small></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>����� ���./��. ����� ���������� �������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_COR_ACCOUNT"))?></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td style="width:60mm; border-bottom:1pt solid #000000;">������ ������ � 
	<?=(CSalePaySystemAction::GetParamValue("ORDER_ID"))?>
	�� 
	<?=(CSalePaySystemAction::GetParamValue("DATE_INSERT"))?></td>
				<td style="width:2mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center"><small>(������������ �������)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(����� �������� ����� (���) �����������)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>�.�.�. �����������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("PAYER_CONTACT_PERSON"))?></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>����� �����������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?
					//�������� �����������
					$sAddrFact = "";
					(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"))>0)
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_COUNTRY"))>0)
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_COUNTRY"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_CITY"))>0) 
					{
						$g = substr(CSalePaySystemAction::GetParamValue("PAYER_CITY"), 0, 2);
						$sAddrFact .= ($sAddrFact<>""? ", ":"").($g<>"�." && $g<>"�."? "�. ":"").(CSalePaySystemAction::GetParamValue("PAYER_CITY"));
					}
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_ADDRESS_FACT"))>0) 
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_ADDRESS_FACT"));
					echo $sAddrFact;
				?>&nbsp;</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>����� �������&nbsp;<?
				if(strpos(CSalePaySystemAction::GetParamValue("SHOULD_PAY"), ".")!==false)
					$a = explode(".", (CSalePaySystemAction::GetParamValue("SHOULD_PAY")));
				else
					$a = explode(",", (CSalePaySystemAction::GetParamValue("SHOULD_PAY")));

				if ($a[1] <= 9 && $a[1] > 0)
					$a[1] = $a[1]."0";
				elseif ($a[1] == 0)
					$a[1] = "00";
				
				echo "<font style=\"text-decoration:underline;\">&nbsp;".$a[0]."&nbsp;</font>&nbsp;���.&nbsp;<font style=\"text-decoration:underline;\">&nbsp;".$a[1]."&nbsp;</font>&nbsp;���.";
				?></td>
				<td align="right">&nbsp;&nbsp;����� ����� �� ������&nbsp;&nbsp;_____&nbsp;���.&nbsp;____&nbsp;���.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>�����&nbsp;&nbsp;_______&nbsp;���.&nbsp;____&nbsp;���.</td>
				<td align="right">&nbsp;&nbsp;&laquo;______&raquo;________________ 201____ �.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td><small>� ��������� ������ ��������� � ��������� ��������� �����, 
				� �.�. � ������ ��������� ����� �� ������ �����, ���������� � ��������.</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td align="right"><b>������� ����������� _____________________</b></td>
			</tr>
		</table>
	</td>
</tr>



<tr valign="top">
	<td style="width:50mm; height:70mm; border:1pt solid #000000; border-right:none;" align="center">
	<b>���������</b><br>
	<font style="font-size:53mm">&nbsp;<br></font>
	<b>������</b>
	</td>
	<td style="border:1pt solid #000000;" align="center">
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td align="right"><small><i>����� � ��-4</i></small></td>
			</tr>
			<tr>
				<td style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("COMPANY_NAME"))?></td>
			</tr>
			<tr>
				<td align="center"><small>(������������ ���������� �������)</small></td>
			</tr>
		</table>

		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td style="width:37mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("INN"))."/".(CSalePaySystemAction::GetParamValue("KPP"))?></td>
				<td style="width:9mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("SETTLEMENT_ACCOUNT"))?></td>
			</tr>
			<tr>
				<td align="center"><small>(��� ���������� �������)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(����� ����� ���������� �������)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>�&nbsp;</td>
				<td style="width:73mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_NAME"))?></td>
				<td align="right">���&nbsp;&nbsp;</td>
				<td style="width:33mm; border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_BIC"))?></td>
			</tr>
			<tr>
				<td></td>
				<td align="center"><small>(������������ ����� ���������� �������)</small></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>����� ���./��. ����� ���������� �������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("BANK_COR_ACCOUNT"))?></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td style="width:60mm; border-bottom:1pt solid #000000;">������ ������ � 
	<?=(CSalePaySystemAction::GetParamValue("ORDER_ID"))?>
	�� 
	<?=(CSalePaySystemAction::GetParamValue("DATE_INSERT"))?></td>
				<td style="width:2mm;">&nbsp;</td>
				<td style="border-bottom:1pt solid #000000;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center"><small>(������������ �������)</small></td>
				<td><small>&nbsp;</small></td>
				<td align="center"><small>(����� �������� ����� (���) �����������)</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>�.�.�. �����������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?=(CSalePaySystemAction::GetParamValue("PAYER_CONTACT_PERSON"))?></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td width="1%" nowrap>����� �����������&nbsp;&nbsp;</td>
				<td width="100%" style="border-bottom:1pt solid #000000;"><?
					//�������� �����������
					$sAddrFact = "";
					(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"))>0)
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_ZIP_CODE"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_COUNTRY"))>0)
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_COUNTRY"));
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_CITY"))>0) 
					{
						$g = substr(CSalePaySystemAction::GetParamValue("PAYER_CITY"), 0, 2);
						$sAddrFact .= ($sAddrFact<>""? ", ":"").($g<>"�." && $g<>"�."? "�. ":"").(CSalePaySystemAction::GetParamValue("PAYER_CITY"));
					}
					if(strlen(CSalePaySystemAction::GetParamValue("PAYER_ADDRESS_FACT"))>0) 
						$sAddrFact .= ($sAddrFact<>""? ", ":"").(CSalePaySystemAction::GetParamValue("PAYER_ADDRESS_FACT"));
					echo $sAddrFact;
				?>&nbsp;</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>����� �������&nbsp;<?
				if(strpos(CSalePaySystemAction::GetParamValue("SHOULD_PAY"), ".")!==false)
					$a = explode(".", (CSalePaySystemAction::GetParamValue("SHOULD_PAY")));
				else
					$a = explode(",", (CSalePaySystemAction::GetParamValue("SHOULD_PAY")));

				if ($a[1] <= 9 && $a[1] > 0)
					$a[1] = $a[1]."0";
				elseif ($a[1] == 0)
					$a[1] = "00";

				echo "<font style=\"text-decoration:underline;\">&nbsp;".$a[0]."&nbsp;</font>&nbsp;���.&nbsp;<font style=\"text-decoration:underline;\">&nbsp;".$a[1]."&nbsp;</font>&nbsp;���.";
				?></td>
				<td align="right">&nbsp;&nbsp;����� ����� �� ������&nbsp;&nbsp;_____&nbsp;���.&nbsp;____&nbsp;���.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td>�����&nbsp;&nbsp;_______&nbsp;���.&nbsp;____&nbsp;���.</td>
				<td align="right">&nbsp;&nbsp;&laquo;______&raquo;________________ 201____ �.</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td><small>� ��������� ������ ��������� � ��������� ��������� �����, 
				� �.�. � ������ ��������� ����� �� ������ �����, ���������� � ��������.</small></td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="0" style="width:122mm; margin-top:3pt;">
			<tr>
				<td align="right"><b>������� ����������� _____________________</b></td>
			</tr>
		</table>
	</td>
</tr>
</table>
<br />
<h1>��������! � ��������� ������ �� �������� �������� �����.</h1>

<!-- ������� �������� -->
<h1><b>����� ������:</b></h1>
<ol>
	<li>������������ ���������. ���� � ��� ��� ��������, ���������� ������� ����� ��������� � ��������� �� ����� ������� ����������� ����� ��������� � ����� �����.</li>
	<li>�������� �� ������� ���������.</li>
	<li>�������� ��������� � ����� ��������� �����, ������������ ������� �� ������� ���.</li>
	<li>��������� ��������� �� ������������� ���������� ������.</li>
</ol>

<h1><b>������� ��������:</b> </h1>
<ul>
	<li>�������� ����������� ������ ������������ ����� ������������� ����� �������.</li>
	<li>������������� ������� ������������ �� ���������, ����������� � ��� ����.</li>
</ul>


<p><b>����������:</b>
<?=(CSalePaySystemAction::GetParamValue("COMPANY_NAME"))?>
	�� ����� ������������� ���������� ����� ���������� ������ �������. �� �������������� ����������� � ������ �������� ��������� � ���� ����������, ����������� � ���� ����.</p>
</body>
</html>