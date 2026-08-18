<?php
session_start();

if ($_SESSION['accname']==""){
	header("Location: logout.php"); 
}

$sid = isset($_GET['sid']) ? $_GET['sid'] : '';
$sdate = isset($_GET['sdate']) ? $_GET['sdate'] : '';
$edate = isset($_GET['edate']) ? $_GET['edate'] : '';
$urate = isset($_GET['urate']) ? $_GET['urate'] : '';

//
	if (($sid != '') && ($sdate != '') && ($edate != '') && ($urate != '')) {
		//check 帳號/密碼
		$host = 'localhost';
		$user = 'tours_user';
		$passwd = 'tours0115';
		$database = 'toursdb';

		$conn = mysqli_connect($host, $user, $passwd, $database);
		mysqli_query($conn,"SET NAMES 'utf8'");
			
		//$sql3 = "SELECT * FROM orderinfo ";
		$sql3 = $sql3." SELECT a.*,b.store_name, c.member_id as memberid,c.member_name,d.shopping_area as shoppingarea FROM orderinfo as a ";
		$sql3 = $sql3." inner join ( select sid,store_id,store_name,shopping_area from store) as b ON b.sid= a.store_id ";
		$sql3 = $sql3." inner join ( select aid,shopping_area from shopping_area) as d ON d.aid= b.shopping_area ";
		$sql3 = $sql3." inner join ( select mid,member_id,member_name from member) c on a.member_id = c.mid ";
		
		$sql3 = $sql3." where a.order_date > '".$sdate." 00:00:00' and a.order_date < '".$edate." 23:59:59' and a.store_id=$sid and a.order_status=1 and a.pay_status=1";
		//echo $sql3;
		if ($result2 = mysqli_query($conn, $sql3)){
			if (mysqli_num_rows($result2) > 0){
				$total_amountA = 0;
				$total_amountB = 0;
				$total_amountC = 0;
				$total_amountD = 0;
				$total_amountF = 0;

				$total_amountG = 0;
				$total_amountH = 0;
				$total_amountI = 0;
				$total_amountJ = 0;
				$total_amountK = 0;
				$total_amount = 0;
				$total_order = 0;	
				$store_name = '';
				while($row2 = mysqli_fetch_array($result2)){
					$store_name = $row2['store_name'];
					break;
				}
				mysqli_data_seek($result2 ,0);
				/*
				while($row2 = mysqli_fetch_array($result2)){
					$order_pay = $row2['order_pay'];
					$bonus_point = $row2['bonus_point'];
					$total_amountD = $total_amountD + $order_pay;
					$store_id = $row2['store_id'];
					$bonus = floor(intval($order_pay) * $urate) ;
					$total_amountG = $total_amountG + $bonus;
					//$sys = floor(intval($order_pay) * 0.05) ;
					$sys = intval($order_pay) * 0.05 ;
					$total_amountI = $total_amountI + $sys;
					//$total_amountJ = $total_amountJ + $bonus + $sys;
					$total_amountK = $total_amountK + $bonus_point;
					$total_order = $total_order + 1;
					$total_amount = $total_amount + $order_pay;					
				}
				$total_amountI = floor($total_amountI);
				$total_amountJ = $total_amountG + $total_amountI;
				*/
				//insert profit_list
				//$sql="INSERT INTO `profit` (store_id,profit_month,start_date,end_date,total_amount,total_order, total_amountD,total_amountG,total_amountI,total_amountJ,total_amountK,profit_pdf,billing_date) VALUES ";
				//$sql=$sql." ($store_id,'$profit_month','$sdate','$edate',$total_amount,$total_order,$total_amountD,$total_amountG,$total_amountI,$total_amountJ,$total_amountK,'uploads/profit.pdf',NOW());";
				//echo $sql;
				//mysqli_query($conn,$sql) or die(mysqli_error($conn));
				
			}else {
				echo '無帳務資料';
				exit;
				
			}
		}else {
			echo 'sql 錯誤';
			exit;
		}
	} else {
		echo '參數錯誤';
		exit;
	}
	
$nowdate = new DateTime(date("Y-m-d"));
//echo $nowdate->format('Y-m-d') . "\n";	
?>		
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 15">
<link id=Main-File rel=Main-File href="../profilereport.php">
<link rel=File-List href=filelist.xml>
<!--[if !mso]>
<style>
v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
x\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}
</style>
<![endif]-->
<link rel=Stylesheet href=stylesheet.css>
<style>
<!--table
	{mso-displayed-decimal-separator:"\.";
	mso-displayed-thousand-separator:"\,";}
@page
	{margin:.75in .7in .75in .7in;
	mso-header-margin:.3in;
	mso-footer-margin:.3in;}
ruby
	{ruby-align:left;}
rt
	{color:windowtext;
	font-size:9.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:新細明體, serif;
	mso-font-charset:136;
	mso-char-type:none;
	display:none;}
-->
</style>
<style type="text/css">
      body {
        margin: 0;
        padding: 0;
        background-color: white;
      }
    </style>
<![if !supportTabStrip]><script language="JavaScript">
<!--
function fnUpdateTabs()
 {
  if (parent.window.g_iIEVer>=4) {
   if (parent.document.readyState=="complete"
    && parent.frames['frTabs'].document.readyState=="complete")
   parent.fnSetActiveSheet(0);
  else
   window.setTimeout("fnUpdateTabs();",150);
 }
}

//if (window.name!="frSheet")
 //window.location.replace("../profilereport.php");
//else
 //fnUpdateTabs();
//-->
</script>
<![endif]>
</head>

<body >

<table border=0 cellpadding=0 cellspacing=0 width=1317 style='border-collapse:
 collapse;table-layout:fixed;width:987pt'>
 <col width=64 style='width:48pt'>
 <col width=79 style='mso-width-source:userset;mso-width-alt:2816;width:59pt'>
 <col width=86 style='mso-width-source:userset;mso-width-alt:3043;width:64pt'>
 <col width=82 style='mso-width-source:userset;mso-width-alt:2901;width:61pt'>
 <col width=86 style='mso-width-source:userset;mso-width-alt:3043;width:64pt'>
 <col width=77 style='mso-width-source:userset;mso-width-alt:2730;width:58pt'>
 <col width=74 style='mso-width-source:userset;mso-width-alt:2616;width:55pt'>
 <col width=108 style='mso-width-source:userset;mso-width-alt:3840;width:81pt'>
 <col width=32 style='mso-width-source:userset;mso-width-alt:1137;width:24pt'>
 <col width=64 span=2 style='width:48pt'>
 <col width=73 style='mso-width-source:userset;mso-width-alt:2588;width:55pt'>
 <col width=27 style='mso-width-source:userset;mso-width-alt:967;width:20pt'>
 <col width=81 style='mso-width-source:userset;mso-width-alt:2872;width:61pt'>
 <col width=64 style='width:48pt'>
 <col width=25 style='mso-width-source:userset;mso-width-alt:881;width:19pt'>
 <col width=77 style='mso-width-source:userset;mso-width-alt:2730;width:58pt'>
 <col width=26 style='mso-width-source:userset;mso-width-alt:938;width:20pt'>
 <col width=128 style='mso-width-source:userset;mso-width-alt:4551;width:96pt'>
 <tr height=24 style='height:18.0pt'>
  <td height=24 width=64 style='height:18.0pt;width:48pt'></td>
  <td width=79 style='width:59pt'></td>
  <td width=86 style='width:64pt'></td>
  <td width=82 style='width:61pt'></td>
  <td width=86 style='width:64pt'></td>
  <td width=77 style='width:58pt'></td>
  <td colspan=6 class=xl108 width=415 style='width:311pt'><br/>點點ddot系統<span
  style='mso-spacerun:yes'>&nbsp;&nbsp; </span>店家對帳單</td>
  <td width=27 style='width:20pt'></td>
  <td colspan=6 class=xl111 width=401 style='width:302pt'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=19 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 style='height:16.2pt'></td>
  <td colspan=5 class=xl110>店家名稱 : <?php echo $store_name;?></td>
  <td colspan=6 class=xl112>帳務區間 <?php echo $sdate;?> 至 <?php echo $edate;?></td>
  <td></td>
  <td colspan=6 class=xl111>請款日期 : <?php echo $nowdate->format('Y-m-d');?></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=19 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=75 style='mso-height-source:userset;height:56.4pt'>
  <td height=75 style='height:56.4pt'></td>
  <td colspan=7 class=xl70>會員交易明細</td>
  <td></td>
  <td colspan=8 class=xl68 width=475 style='width:357pt'>本期店家應付費用<br>
    (系統開立發票)</td>
  <td></td>
  <td class=xl67 width=128 style='width:96pt'>本期店家<br>
    應收金額<br>
    (店家開立發票)</td>
 </tr>
 <tr height=30 style='mso-height-source:userset;height:22.2pt'>
  <td height=30 style='height:22.2pt'></td>
  <td rowspan=2 class=xl83 width=79 style='border-bottom:1.5pt solid black;
  width:59pt'>交易<br>
    日期</td>
  <td rowspan=2 class=xl83 width=90 style='border-bottom:1.5pt solid black;
  width:70pt'>訂單<br>
    編號</td>
  <td rowspan=2 class=xl83 width=86 style='border-bottom:1.5pt solid black;
  width:64pt'>會員<br>
    姓名</td>
  <td rowspan=2 class=xl83 width=80 style='border-bottom:1.5pt solid black;
  width:64pt'>訂單<br>
    金額<br>
    (A)</td>
  <td rowspan=2 class=xl83 width=77 style='border-bottom:1.5pt solid black;
  width:58pt'>現金券<br>
    折抵<br>
    (B)</td>
  <td rowspan=2 class=xl78 width=74 style='border-bottom:1.5pt solid black;
  width:55pt'>紅利<br>
    折抵<br>
    (C)</td>
  <td rowspan=2 class=xl85 width=100 style='border-bottom:1.5pt solid black;
  width:81pt'>實際支付<br>
    金額<br>
    (D=A-B-C)</td>
  <td></td>
  <td colspan=3 class=xl84>應付會員紅利費用</td>
  <td class=xl65></td>
  <td colspan=2 class=xl84>應付系統費用</td>
  <td class=xl65></td>
  <td rowspan=2 class=xl80 width=77 style='border-bottom:1.5pt solid black;
  width:58pt'>應付費用<br>
    總額<br>
    (J=G+I)</td>
  <td></td>
  <td rowspan=2 class=xl78 width=128 style='border-bottom:1.5pt solid black;
  width:96pt'>應收紅利<br>
    金額<br>
    (K=C)</td>
 </tr>
 <tr height=66 style='mso-height-source:userset;height:49.8pt'>
  <td height=66 style='height:49.8pt'></td>
  <td></td>
  <td class=xl83 width=64 style='width:48pt'>紅利%<br>
    (E)</td>
  <td class=xl83 width=64 style='width:48pt'>紅利<br>
    金額<br>
    (F=D*E)</td>
  <td class=xl82 width=73 style='width:55pt'>實際支付<br>
    紅利金額<br>
    (Ｇ) 註1</td>
  <td class=xl66></td>
  <td class=xl83 width=81 style='width:61pt'>系統費用<br>
    (H=D*5%)</td>
  <td class=xl82 width=64 style='width:48pt'>實際支付<br>
    系統費用<br>
    (I) 註2</td>
  <td class=xl65></td>
  <td></td>
 </tr>
 <?php
 	while($row2 = mysqli_fetch_array($result2)){
		$order_no = $row2['order_no'];
		$order_date = date('Y-m-d', strtotime($row2['order_date']));
		$store_name = $row2['store_name'];
		$member_id = $row2['member_id'];
		$member_name = $row2['member_name'];
		$order_amount = $row2['order_amount'];			//A
		$total_amountA = $total_amountA + $order_amount;
		
		$discount_amount = $row2['discount_amount'];		//B
		$total_amountB = $total_amountB + $discount_amount;

		$bonus_point = $row2['bonus_point'];			//C
		$total_amountC = $total_amountC + $bonus_point;
		
		$order_pay = $row2['order_pay'];				//D
		$total_amountD = $total_amountD + $order_pay;
		$store_id = $row2['store_id'];
		$bonus = floor(intval($order_pay) * $urate) ;
		$total_amountF = $total_amountF + intval($order_pay) * $urate;
		
		$total_amountG = $total_amountG + $bonus;
		//$sys = floor(intval($order_pay) * 0.05) ;
		$sys = intval($order_pay) * 0.05 ;
		$total_amountI = $total_amountI + $sys;
		//$total_amountJ = $total_amountJ + $bonus + $sys;
		$total_amountK = $total_amountK + $bonus_point;
		$total_order = $total_order + 1;
		$total_amount = $total_amount + $order_pay;					
?>
 <tr height=22 style='height:16.8pt'>
  <td height=22 style='height:16.8pt'></td>
  <td class=xl90 style='border-top:none'><?php echo $order_date;?></td>
  <td class=xl91 style='border-top:none'><?php echo $order_no;?></td>
  <td class=xl91 align=right style='border-top:none'><?php echo $member_name;?></td>
  <td class=xl91 align=right style='border-top:none'><?php echo $order_amount;?></td>
  <td class=xl91 align=right style='border-top:none'><?php echo $discount_amount;?></td>
  <td class=xl92 align=right style='border-top:none'><?php echo $bonus_point;?></td>
  <td class=xl93 align=right style='border-top:none'><?php echo $order_pay;?></td>
  <td></td>
  <td class=xl91 align=right><?php echo 100 * $urate;?></td>
  <td class=xl91 align=right><?php echo intval($order_pay) * $urate; ?></td>
  <td class=xl102 align=right><?php echo $bonus;?></td>
  <td class=xl91>　</td>
  <td class=xl91 align=right><?php echo $sys;?></td>
  <td class=xl102>　</td>
  <td class=xl91>　</td>
  <td class=xl103 style='border-top:none'>　</td>
  <td class=xl95></td>
  <td class=xl92 align=right style='border-top:none'><?php echo $bonus_point;?></td>
 </tr>
 <?php 
 	}
	$total_amountH = $total_amountI;
	$total_amountI = floor($total_amountI);
	$total_amountJ = $total_amountG + $total_amountI;
?>				
 <tr height=31 style='mso-height-source:userset;height:23.4pt'>
  <td height=31 style='height:23.4pt'></td>
  <td colspan=3 class=xl98>合計</td>
  <td class=xl99 align=right><?php echo $total_amountA;?></td>
  <td class=xl99 align=right><?php echo $total_amountB;?></td>
  <td class=xl100 align=right><?php echo $total_amountC;?></td>
  <td class=xl101 align=right><?php echo $total_amountD;?></td>
  <td></td>
  <td class=xl99>　</td>
  <td class=xl99 align=right><?php echo $total_amountF;?></td>
  <td class=xl106 align=right><?php echo $total_amountG;?></td>
  <td class=xl99>　</td>
  <td class=xl99 align=right><?php echo $total_amountH;?></td>
  <td class=xl106 align=right><?php echo $total_amountI;?></td>
  <td class=xl99>　</td>
  <td class=xl107 align=right><?php echo $total_amountJ;?></td>
  <td class=xl95></td>
  <td class=xl100 align=right><?php echo $total_amountK;?></td>
 </tr>
 <tr height=22 style='height:16.8pt'>
  <td height=22 colspan=19 style='height:16.8pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=19 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=31 style='mso-height-source:userset;height:23.4pt'>
  <td height=31 colspan=5 style='height:23.4pt;mso-ignore:colspan'></td>
  <td colspan=3 class=xl72>本期店家應付費用</td>
  <td class=xl73></td>
  <td colspan=2 class=xl72><?php echo $total_amountJ;?></td>
  <td class=xl74 colspan=3 style='mso-ignore:colspan'><span
  style='mso-spacerun:yes'>&nbsp;</span>系統開立發票金額</td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=29 style='mso-height-source:userset;height:21.6pt'>
  <td height=29 colspan=5 style='height:21.6pt;mso-ignore:colspan'></td>
  <td colspan=3 class=xl75>本期店家應收金額</td>
  <td class=xl71>–<ruby><font class="font11"><rt class=font11></rt></font></ruby></td>
  <td colspan=2 class=xl75><?php echo $total_amountK;?></td>
  <td class=xl74 colspan=3 style='mso-ignore:colspan'><span
  style='mso-spacerun:yes'>&nbsp;</span>店家開立發票金額</td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=44 style='mso-height-source:userset;height:33.0pt'>
  <td height=44 colspan=5 style='height:33.0pt;mso-ignore:colspan'></td>
  <td colspan=3 class=xl76>本期結算金額</td>
  <td class=xl77 style='border-top:none'>　</td>
  <td colspan=2 class=xl76><?php echo $total_amountJ - $total_amountK ;?></td>
  <td class=xl74 colspan=3 style='mso-ignore:colspan'><span
  style='mso-spacerun:yes'>&nbsp;</span><?php if ($total_amountJ >= $total_amountK) echo '店家應匯款金額'; else echo '店家應收款金額'; ?></td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.8pt'>
  <td height=22 colspan=11 style='height:16.8pt;mso-ignore:colspan'></td>
  <td colspan=8 rowspan=12 height=276 width=501 style='mso-ignore:colspan-rowspan;
  height:204.0pt;width:377pt'><!--[if gte vml 1]><v:shapetype id="_x0000_t75"
   coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe"
   filled="f" stroked="f">
   <v:stroke joinstyle="miter"/>
   <v:formulas>
    <v:f eqn="if lineDrawn pixelLineWidth 0"/>
    <v:f eqn="sum @0 1 0"/>
    <v:f eqn="sum 0 0 @1"/>
    <v:f eqn="prod @2 1 2"/>
    <v:f eqn="prod @3 21600 pixelWidth"/>
    <v:f eqn="prod @3 21600 pixelHeight"/>
    <v:f eqn="sum @0 0 1"/>
    <v:f eqn="prod @6 1 2"/>
    <v:f eqn="prod @7 21600 pixelWidth"/>
    <v:f eqn="sum @8 21600 0"/>
    <v:f eqn="prod @7 21600 pixelHeight"/>
    <v:f eqn="sum @10 21600 0"/>
   </v:formulas>
   <v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"/>
   <o:lock v:ext="edit" aspectratio="t"/>
  </v:shapetype><v:shape id="圖片_x0020_1" o:spid="_x0000_s1025" type="#_x0000_t75"
   style='position:absolute;margin-left:0;margin-top:3pt;width:355.2pt;
   height:196.8pt;z-index:1;visibility:visible' o:gfxdata="UEsDBBQABgAIAAAAIQD0vmNdDgEAABoCAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbJSRQU7DMBBF
90jcwfIWJQ4sEEJJuiCwhAqVA1j2JDHEY8vjhvb2OEkrQVWQWNoz7//npFzt7MBGCGQcVvw6LzgD
VE4b7Cr+tnnK7jijKFHLwSFUfA/EV/XlRbnZeyCWaKSK9zH6eyFI9WAl5c4DpknrgpUxHUMnvFQf
sgNxUxS3QjmMgDGLUwavywZauR0ie9yl68Xk3UPH2cOyOHVV3NgpYB6Is0yAgU4Y6f1glIzpdWJE
fWKWHazyRM471BtPV0mdn2+YJj+lvhccuJf0OYPRwNYyxGdpk7rQgYQ3Km4DpK3875xJ1FLm2tYo
yJtA64U8iv1WoN0nBhj/m94k7BXGY7qY/2z9BQAA//8DAFBLAwQUAAYACAAAACEACMMYpNQAAACT
AQAACwAAAF9yZWxzLy5yZWxzpJDBasMwDIbvg76D0X1x2sMYo05vg15LC7saW0nMYstIbtq+/UzZ
YBm97ahf6PvEv91d46RmZAmUDKybFhQmRz6kwcDp+P78CkqKTd5OlNDADQV23eppe8DJlnokY8ii
KiWJgbGU/Ka1uBGjlYYyprrpiaMtdeRBZ+s+7YB607Yvmn8zoFsw1d4b4L3fgDrecjX/YcfgmIT6
0jiKmvo+uEdU7emSDjhXiuUBiwHPcg8Z56Y+B/qxd/1Pbw6unBk/qmGh/s6r+ceuF1V2XwAAAP//
AwBQSwMEFAAGAAgAAAAhAHsKDZgVAgAA9gQAABIAAABkcnMvcGljdHVyZXhtbC54bWysVFuO0zAU
/UdiD5b/mcSdtE2iJqNqqkFII6gQLMDj3DQWiR3Zpu2sALEBvtgeYhtcx2mqSvOBKH/2fZxzcu51
VnfHriV7MFZqVVB2E1MCSuhKql1BP396eJNSYh1XFW+1goI+g6V35etXq2Nlcq5Eow1BCGVzDBS0
ca7Po8iKBjpub3QPCrO1Nh13eDW7qDL8gOBdG83ieBHZ3gCvbAPgNiFDywHbHfQ9tO06UEAl3doW
FDX46FhTG92FaqHbkrFV5FX58wCBhw91XcZT2N+GjNGHki1C3J9PQV9wm7J4bMHU0DLAnsmcnvBL
lk7oU9D3ZIskS7Mpd8E8W77MzJIse5H6RNhLEUjUfivF1oyM7/dbQ2RV0Bklinc4pV8/f/z+/o0w
Gp1LQgPPEeRRiy92HBv/h6F1XCqk0vcNVztY2x6Ew+XxbGECqCjQDdcLtU+t7B9kiyPiuT9fLSNs
31/tnq5rKWCjxdcOlAsLaKDlDpffNrK3lJgcuidAL827ilEicPcdGtobqZz/Pp5bZ8CJ5lrdHqpG
Hz6id963CXj08OyT32nb+2nz/Fib7n8woxPkWNDlcr5kC3zzzwVN0vltlsThI+HoiMCCZM7mWZyh
EVgxS7JFitVBrpfiJfXGuregr5ZFPBD6jn7g8+Y53z/a0ZkTxWhNMGNYrelFiFbiSDfc8dMSXvxA
xs7wwyr/AAAA//8DAFBLAwQKAAAAAAAAACEAQlkrR6ujAQCrowEAFQAAAGRycy9tZWRpYS9pbWFn
ZTEuanBlZ//Y/+AAEEpGSUYAAQEBANwA3AAA/9sAQwACAQEBAQECAQEBAgICAgIEAwICAgIFBAQD
BAYFBgYGBQYGBgcJCAYHCQcGBggLCAkKCgoKCgYICwwLCgwJCgoK/9sAQwECAgICAgIFAwMFCgcG
BwoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoK/8AAEQgC
UwQjAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMC
BAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYn
KCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeY
mZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5
+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwAB
AgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpD
REVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ip
qrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMR
AD8A/ff5fX9arX38OPU1DlvWljjV+ZHPFTzGyp8upZgdfJUbu1Sb067qoMCG4NHzYpRB0exYvGBx
g1LAw8pRu/hqrEiSHEhx/dpok8t8Rt937tXcHTWxf3LnGaWqtwG89WVqsrnaM0GTjyi0UUUCCiii
gAooooAKKKKACiiigAooooAKKKKACiiigAPSjk80VHPKI0z3oH5EmRRnPSqJlupDuQn8OlSRXUit
tloL9m7aFqigHIyKKDMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKQketI8io
NxqvPO0nybTtPp/FQUk2PNzJnMUe5R1NPhnE4/3e1V1vSAF8sbemKdIptX82MfK3DCgpx7lrANFN
VwVB9adQZhRQTijOBk0AFFAORkUhYAZNAC0UUZ5xigAooooAKKKKACiiigAoooPIoAa74Qle1U2n
kkG0tUlzPIrbFGAR6VBwKhnRTjoKR6UDinRQPKN7HatOa2bblG3UiuaJHQTTd/oDU1nH5km89FoH
KXKrkeMHGKQ7s/LUtyymTCDpUdARd0Sw3MrSrG1WgM9aoLIyNvUcirVtK8q7mHeqiZVI21RNRRRV
GIUUUUAFFFFABRRRQAUUUUAFFFFABRRQTjtQAUVHHdRySeWoP1p7NtG4igLMWio4bmKbhDTy2O1A
C0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAB
RRRQAUHFIWAGTUEl6u/92u71NBSjKWxYwPX9aB04qn9rmwR/kU6K9bdiQcdyaLj9nItUU2OVJRlD
TqCAooooAz6CM96KKzOwKKKI1Mj7VoADyKIo974FSm0k/vL+dKU+yR8n52NOxm5LoJK4a5AB6VbH
Ss9AQ+7Per6ninEzqK0UhaKKKozCiig57UAFFJhqMNQAtFJhqMNQAtFAz3oOe1ABRQM45ooAKKKK
ACiiigAqren7tWqjnh81MUFRdpCW20QjA4qC7AMg29cU3/SID8zYp1vBLK3mE8e9LmNOXl1bLUf3
BTqRdqrgGlyPWmYhRRRQAUUUUAFFJuG7GaXK/wB4UBqFFGUHV1o3J/z0Wi6DUKKNyf8APRaMr/eF
F0GoUUZUdXFG5P8AnotF0GoUU0yIP46duHY0AFFFFABQelFB6UAVQHunYu3yrUYdVkKsOOn0qxLG
8Z82M/73vUbxwOvmlvwoNYy+4jcANuO0d+Kehedsgf4UsVsudznC/wB2rCquPlHFSkOUtBIojGgT
dmn0HPajOBzVGIcd6q3VwzHyo6ddXCn92nWlt4Qo3sPm7UFxjbVj7dDHEENR3cTtGrJ1WrA4GKCM
jFBBXtrkSDY33h+tWAc96q3dueZkFSWtwsi4/iFBcve1RNRSZJ6UozjmggKKKKACiiigApGYKu40
tI5AQk0AUppfNfOKjOKdIymTKDg9Kaqk96zOuPwpFiUkW6BabaFvN5JximxT+UPLYZWnPdgKUjXa
fpQRyytYjdR5xUevFWG220HI6/rUdpEM+c30FNuZPMk2Kfu8fWqTJ+J2G53HdmijyZgu4JTQT3qW
baAc56VYtJsYhx3quCetTWbIJvm6n7tBE/hLlFFFaHMFFFFABRRRQAUUUUAFFFFABRRnHWjcKAEz
zx+NVLud3byYj3xUl1c4Pkxj5jS2tsYv3jDrQUlbVi20AiXJ+8e9S4DDBFJgjjmnc+hoApzRG1fz
IjxnmrEMyzJnvTmVWByKqyIbaXeB8tA/i0LlFNimWVNwFOoICiiigAooooAKKKKACiiigAooooAK
KKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoopHYqpKrn2oArXdwxOIzx3zUCg5+VPl9a
HLOWY/lUpVyVMbjaMZ56VG7Ole7FEWTjgU542iK7+4p6KrO0rfdWo3ZpH8xjRaw73eg+3naNwC3y
1dByMis0jAyB0q9bszxq7L/DTiZVI63JKKKKoyM+jOOtFBz2rM7BDzwD9KuW0QjTlfm71FaQD/WN
+HtVoDHSrSsYVJX0DpwBVe8UMm7H3Wqwar3cvy7O560pEQ+Ir55X/erQrP7r/vVoAg9KImlboGaR
mwM0uO9Q3jlIsiqMVuNe/AbbEN2Kb9tmJ/1VPtIQEDsOcVPtHpQW5QT0RW+2Tf8API0n22X/AJ5V
a6ijaKB80exV+2y/88qDeyj/AJZVaxQcd6A5o9ir9tmP/LL9KkhuVkO1uDUny9qq3MYhkV0OM0Au
WppYuUUinK5zS0GYUZx1ooOO9ABRUEt6qnbHzUD3Ur/eb/vmlzGkacpFxpFAzupr3MSrnzOfaqRy
T8xJpUYRsHZc0uYr2ISStNL87ZXtUhuyqbUQD60Q23m7pPug/wANNuESObYq/LxRa+o3ybAWum+c
O30WpLe6J/dzHntVhVCLhaqzBTc4U+/40cpEXGSehcooHSiqMwprthSQe1OqOX/UN9KA6leKF7h/
Ndql+xL60tj/AMe4+tRzvIJ2UNxtzSZprzWH/Y19aT7Gn96o4pZDG5LHjFOVn/dksfvHNKyH7y0u
P+xr60fYl9aigkkLNzwGH86b5sv2naXbG6jQLTJ/sa/3qT7In96ml3xKcn739aRZH3R9eevvT5UL
3rbj/sKnkOfzptu8iXHkSE+1TW5JUlj3qKT/AI/P8+lMFroyyOlFJ/FS0GYUUUUAIcEYxmo4rWOJ
965+h7VLRQAY9qKKKAI5riOH7xqN7ktAzovtTrqN5B8g6VWg3+cEV9u40m7GkY6XHxbIj5k2WJ+6
KkW8jHRWqCaPFxtJPPrSTxCJ8Ann1pcxaipWuWftif3W/Sj7ano36VBNB5O0gnLUG3CQ+dvo5hct
PQn+2IeNpqBvvedb9uooS33xGRm6U60hLEtvNHMHKo7EwugFUuDuIqSJxIm4VRw7ybWZi3Tmrtuj
RxBW61RMkkh9FFFBmFFFFABTZFDIVPenUHPagDPki8mTYpzikWpZ4ZI23MeM1GPSszrjrFMKasby
ybAKdU9pGAvmHvQKUuWI64ZbaAIv0FRWoHzSOucVHPIZZN3p0p1pKFdkl+7QTytRFN5Jv9ulLdIN
qyAYzThZjO8P8vWm3Mm8hFPC0EqzkrEIqayiLP5rH7rcVFzjIFWLOB0bzN3B7UGk9IlmiiitDlCi
iigAooooAKKKKACiiigBsjYVjmqkV1Jv+eTNXWAYbTVG6jWKTCD+Gg0p66AQsRLGTe5+77UG4mxk
StUlwFFurAfjTf8Aly+7/FU/EVHl5dUR/aLj/ns1H2i4/wCezVNEd0DY96SxwXY47UcpXu9iITzn
/ls1KLiTO2Ull/ip9vj7ScrQeb3gfx0cwe7fYRWEKNsm+992pLKaR3Ku5bjvUd4oWUYXHy1PawpH
86qeV61XUmVuW5NRRRQYhRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRketFRX
DPlY0P3jQBJuX1o3L61A8axnD3En/fVHlqDj7Q//AH1QVyk+5fWjcvrVYqgXcbiTH1p3lDOPPf0+
9QHKWMjrTZfniYK1QiBwNyTseO9SQHzIQT360C8ynnB+9Seyjk/rUlxCyPuEeFpisY33KKj4WdK9
6JLLiONbZO/3qRWj8zyPK9t2ajY7jk96d5sm3GF9M4obuTytEbHqDV61AEK4bNU442kcBRnFX0UI
uAKce5FTsLRRRVGRn0qFRIu/pSUjDPBrM7LX0L67TyvSnZOcYqjBcvCSrcr/ACq35ybNysKpM5pR
kpWFmlESbjVBmZm3mnTzNM+T92sLxl8R/Afw7tBqHjnxbpukwN0k1C8SIH6bjz+FE2krs3o0puSU
VdvotzbVskE+tX06Zx1rxZv26f2R45mib46aL8vDY80j8wmDVlf2+P2Qtu0/HbReP+uv/wARURq0
v5kdNTLcy/58z/8AAX/kew5bONtQ3pPlcivJP+G+P2Qev/C99F/8i/8AxFR3P7en7IUiYX47aL/5
F/8AiKftqP8AMvvIjleZc38GX/gL/wAj2BW2W4b/AGelQpcTON4/75zXk4/b1/ZD8oKPjtou7GP+
Wv8A8RUDft2fshMP+S66OG9vN/8AiKn21P8AmRUcszDm1oy/8Bf+R7LLK6Q+Yw2mm287SHZJXjz/
ALeH7I8kSx/8L40b/vmb/wCIpE/bu/ZGSVXb47aNwMfdm/8AiKX1il/MgWV5ly60Zf8AgL/yPYo5
2NwYSDtANOuJmjQMnXOK8dH7eH7Ia3Bk/wCF66Nz/szf/EUs37eX7Icq4/4Xvo/3s9Jv/iKftqX8
yF/ZeY3/AIMv/AX/AJHroluHI+ZaW9yNua8hT9vD9j0YLfHTR8/Sb/4ii4/by/ZDkA2fHXRvxEv/
AMRR7an/ADIf9m5i5XVGX/gLPZYydgwtLlv7tePJ+3v+yDt5+Oui/j5v/wARR/w3x+yD/wBF20X/
AMi//EVXtqP8y+8z/svM7/wZf+Av/I9hyfSoL2ZlAQd+pryX/hvb9j//AKLvov8A5F/+IqOb9u79
j6Vg3/C+dG4/66//ABFDrUf5kVHLcx5taEv/AAF/5HqgIzgA1JHbTOOmPrXlKft5fsfIMD466L/5
F/8AiKc37fH7ISp8vx10f8pf/iKn2lH+ZGksDmXSjL/wF/5HqkqJF8o+ZqYqmRwgryNv27v2RmbP
/C9tH/75m/8AiKltf27/ANkKNt7fHXRvxEv/AMRS9rR/mRX9m5jGP8KX/gL/AMj2aMFF24qK5g80
ZUfMteSf8N7/ALIHf47aL/5F/wDiKP8Ahvb9j/8A6Lvov/kX/wCIq/bUf5kY/wBm5le/sZf+Av8A
yPVEeeNfL+b/AL5qS3t2J3yD6V5P/wAN7fsf/wDRdtF/OX/4igft7/sgD/mu2i/+Rf8A4ij21H+Z
D/s/M/8AnxL/AMBf+R7Dlv7tGW/u149/w3x+yB/0XXRf/Iv/AMRR/wAN8fsgf9F10X/yL/8AEUe2
o/zL7yP7LzT/AJ8y/wDAX/kew5b+7TJyRCwI7V5D/wAN8fsgf9F10X/yL/8AEUkv7ev7ILoR/wAL
30X/AMi//EUe2o/zII5XmV9aMv8AwF/5HrtiT9nHHc1FcH/SH4/gryW3/b0/ZBhj8s/HbR/++Zf/
AIimS/t4/shtKzD47aMdy4/5a/8AxFS69F/aRpHLcy5taMv/AAF/5HrcJPkyZH8Ip6n5I/8Aerx9
P28P2Q1jZT8ddG54+7L/APEU5f28f2QwEH/C9tG+Xn7s3/xFHtqf8yKlluZc38GX/gL/AMj1yA8s
SP4h/Oo8/wCl9P4q8mh/bw/ZEXdn47aP94H7s3r/ALlIP27f2RPP8wfHbR+ufuy//EUe2pfzIP7N
zG/8GX/gL/yPXsnZL8v8X9aEzmLj1ryP/hvH9kTa6/8AC9dG+Zv7s3/xFC/t3/siho/+L66N8ue0
3/xFHtqX8yF/ZuYf8+Zf+As9ihPy9O5qNzm8HFeSp+3n+yEo/wCS8aN1/wCmv/xFRt+3j+yH9oEh
+O2j/wDfMv8A8RT9tR/mRCy3Mtf3Mv8AwF/5Hs3zE520uW/u149/w3x+yD/0XbRf/Iv/AMRR/wAN
8fsgf9F10X/yL/8AEU/bUf5l95P9l5l/z5l/4C/8j2HLf3aMt/drx7/hvj9kD/ouui/+Rf8A4ij/
AIb4/ZA/6Lrov/kX/wCIo9tR/mX3h/Zeaf8APmX/AIC/8j2HLf3aMt/drx7/AIb4/ZA/6Lrov/kX
/wCIo/4b4/ZA/wCi66L/AORf/iKPbUf5l94f2Xmf/PmX/gL/AMj2HLf3aMmvHv8Ahvj9kD/ouui/
+Rf/AIil/wCG+P2QD1+O+i/+Rf8A4ij21H+ZB/ZeZdaMv/AWevkkjGKpRfLdqT2bFeWf8N7fsf8A
b476L/5F/wDiKrH9u79kbztyfHbR/vZztm/+IqZVqP8AMjSGW5lZ/uZf+Av/ACPXrr/j5/EUt2CX
WvIZv28P2SGfzG+O2j/98y//ABFNf9vL9kiQ5b47aP8A98y//EVPtqP8yKWW5lpajL/wF/5HsV4C
Y0P92iU/6Eox/nNePP8At5fskSjD/HfR/wDvmb/4ilP7eX7I5Tyz8d9Hx/uzf/G6Pb0e6+8P7MzL
T9zL/wABf+R7BGcWrYFFmCUY/jXj5/bv/ZJEfl/8L30bH+7N/wDEUJ+3j+yTEMJ8eNH5/wBmb/4i
j21H+ZfeDy3Mdf3Mv/AX/keuw/Pcge+auEnsteLQft4fsiLKHb47aMMf9df/AIirA/b4/ZAPP/C9
tF/8i/8AxFX7ei/tImplmZX0oy/8Bf8Akew5b+7Rlv7tePf8N8fsgf8ARddF/wDIv/xFH/DfH7IH
/RddF/8AIv8A8RT9tR/mX3mf9l5p/wA+Zf8AgL/yPYct/doy392vHv8Ahvj9kD/ouui/+Rf/AIij
/hvj9kD/AKLrov8A5F/+Io9tR/mX3h/Zeaf8+Zf+Av8AyPYct/doy392vHv+G+P2QP8Aouui/wDk
X/4ij/hvj9kD/ou2i/8AkX/4ij21H+ZfeH9l5n/z5l/4C/8AI9cu0eRPlHSqjbkOGWvLj+3x+yAe
vx30X/yL/wDEVDcft3fshyvvX48aNj/tr/8AEVLq0d+Zfea08vzLZ0Zf+Av/ACPVvM9qBI4G0N8p
7V5If27P2RB/zXXR/wApf/iKT/huz9kT/oumj/lL/wDEVPtqPdfea/2fj/8AnzL/AMBf+R62W9qM
8V5J/wAN2fsif9F00f8AKX/4ij/huz9kT/oumj/lL/8AEUe2o9194f2fmH/PqX/gL/yPXN3FG4Y4
ryP/AIbs/ZE/6Lpo/wCUv/xFOH7dP7IrDI+Ouj/+Rf8A4ij21HuvvBZdjl/y5l/4C/8AI9cVJZBh
Eq9GrKgBH/168bt/28f2Q4o9r/HjRs/9tf8A4ipf+G+P2QMf8l20X/yL/wDEVXtKP8y+8wnl2ZS/
5cy/8Bf+R7Blv7tGW/u149/w3x+yB/0XXRf/ACL/APEUf8N8fsgf9F10X/yL/wDEVXtqP8y+8j+y
8z/58y/8Bf8Akew5b+7Rlv7tePf8N8fsgf8ARddF/wDIv/xFH/DfH7IH/RddF/8AIv8A8RR7aj/M
vvD+y80/58y/8Bf+R7Dlv7tGW/u149/w3x+yB/0XXRf/ACL/APEUf8N8fsgf9F10X/yL/wDEUe2o
/wAy+8P7LzT/AJ8y/wDAX/kew5b+7Rlv7tePf8N8fsgf9F10X/yL/wDEUf8ADfH7IH/RddF/8i//
ABFHtqP8y+8P7LzP/nzL/wABf+R7Dlv7tGW/u149/wAN8fsgf9F10X/yL/8AEUf8N8fsgf8ARddF
/wDIv/xFHtqP8y+8P7LzT/nzL/wF/wCR7Dlv7tVb7PmA4/hryj/hvj9kD/ouui/+Rf8A4iobr9vH
9kKU7h8dtG49PN/+IpOtR/mX3l08szJS1oy/8Bf+R65LIGtAg6igEfZcE85zivH/APhur9kQjP8A
wvfR/wApf/iKD+3R+yLjn47aP+Uv/wARUe3pfzL7zX+y8w29jL/wF/5HsFs6rCwY84PFFkQpbccc
V4+P26v2RSf+S7aN+Uv/AMRSj9un9kcf8130c/8Af3/4in9Yp9194v7MzD/n1L/wF/5Hr9s224aU
9KNy/a85/jzXj/8Aw3V+yR/0XfR//Iv/AMRTT+3V+yLnP/C99F/KX/4il7ak/tL7x/2XmG/spf8A
gL/yPYb11kfKH2q3ECsajH8NeKH9uz9kU/8ANd9F/KX/AOIq2P2+P2QQoU/HbRc/9tf/AIinGtS6
yX3mc8rzLlSVGX/gL/yPYct/doy392vHv+G+P2QP+i66L/5F/wDiKP8Ahvj9kD/ouui/+Rf/AIir
9tR/mX3mf9l5p/z5l/4C/wDI9hy392jLf3a8e/4b4/ZA/wCi66L/AORf/iKP+G+P2QP+i66L/wCR
f/iKPbUf5l94f2Xmf/PmX/gL/wAj2HLf3aMt/drx7/hvj9kD/ouui/8AkX/4ij/hvj9kD/ouui/+
Rf8A4ij21H+ZfeH9l5n/AM+Zf+Av/I9hy392jLf3a8e/4b4/ZA/6Lrov/kX/AOIo/wCG+P2QP+i6
6L/5F/8AiKPbUf5l94f2Xmn/AD5l/wCAv/I9hy392jLf3a8e/wCG+P2QP+i66L/5F/8AiKP+G+P2
QP8Aouui/wDkX/4ij21H+ZfeH9l5n/z5l/4C/wDI9hy392jLf3a8e/4b4/ZA/wCi66L/AORf/iKP
+G+P2QO3x30X/wAi/wDxFHtqP8y+8P7LzP8A58y/8Bf+R7FuAGSaaHJ5C14zcft6fsizME/4Xtow
H0m/+IqZf29f2P1QKPjvov8A5F/+Io9tR/mX3lf2XmS/5cy/8Bf+R7AZMHBFKGJ7V4zP+3h+yE7b
4/jvo+f+2v8A8RT4v29/2QwuH+O+i5H/AF1/+Io9tR/mX3h/ZeY2v7GX/gL/AMj2PLf3aMt/drx7
/hvj9kH/AKLtov8A5F/+Io/4b4/ZA/6Lrov/AJF/+Io9tR/mX3k/2Xmf/PmX/gL/AMj2HLf3aMt/
drx7/hvj9kD/AKLrov8A5F/+Io/4b4/ZA/6Lrov/AJF/+Io9tR/mX3h/Zeaf8+Zf+Av/ACPYct/d
qKXIljyP4q8j/wCG+P2QP+i66L/5F/8AiKbJ+3r+yC0iN/wvbReD/wBNf/iKPbUf5kOOV5l/z5l/
4C/8j1i9clsEfwj+dIf9aw2/xL/KvJJ/27/2QpTkfHXRv++Zf/iKD+3h+yGXYn466N1B+7N6f7lT
7al/Mi1luZf8+Zf+Av8AyPWHUfZ1BFSEgOvH8ZryF/27/wBkQxrGPjtovH+zN/8AEU4/t4fsiM2R
8ddH+8f4ZvT/AHKPb0f5kP8As3Mf+fMv/AX/AJHsULYhxt/hH8qLRv3KjHrXj6/t5fsgbMf8L40b
7v8A01/+Iot/29P2QY0UP8d9G7/89f8A4in7ej/MiP7LzL/nzL/wF/5HscqLKuwiqz2rxgsG3e1c
X4H/AGo/2ffiTdJY+B/i5oeoTyHC28d8qyN/wBsN+ld8hVvmAq4yjPVO5zVKeIwsuWrBxfZq35lE
rICB5fWnR28rrnGO3NXdo/yKUDtT5UR7SRHFbpEcqKkoopmYUUUUAZ9BGetGD6H8qMH0P5VmdgYp
DkfKDwaXB9D+VNIP92gTeh4V+29+15b/ALM/g+PS/DcEN14p1dWGl283KW8Y4a4cDqBnhf4j9DX5
r+N/HPjH4k6/N4o8d+JbzVL6dt0lxdzFiPYDoo9AAAOwr0X9uz4haj8Q/wBqTxXcXbt5Ok339lWc
THhI4AEb833t/wACryWvmcdiqlas10Wh+5cK5Hh8uy6FVxvUkrtvdX6LysIVBpSoPWiiuE+rG7BS
lAaWigBAoHSkCADANOooAQKB0NG33paKAuN2Ad6PLUdzTqM0rIeogUAYpPLHqadmij3Q1G7BRsFO
oo0DUbsFGwU6ijQNRuwUu0djS0UaCECgUmwdc06ij3Q1G7BRsFOopgN2CjYKdRRceo3YKNgp1FLQ
NRuwUuwUtFGgagBgYpuwetOoo90Q3YD1Jo2CnUUe6PUTYKCue9LRR7otRvlj1NLt96Wij3QG7BRs
HXNOoo0HqIUWk2CnUUaBqN2CjYKdRRoGo3YKNgp1FGgajdgo2CnUUaBqN2ClK5Ocmloo0EJsFG0U
tFPQeomwUbBS0UtA1E2+5pNgHenUUw1G7Aepo2CnUUtA1G7BRsFOoo0DUbsFGwU6ijQNRuwUbBTq
KNA1G7BRsHrTqKdw1E2ijaKWii4aibRRtFLRRcNRNoo2iloouGoY4xTdgp1FFw1G7BRsFOopaBqN
2CjYKdRRoGo3YKNgp1FGgajdgo2CnUUaBqN2CjYKdRRoGo3YKNgp1FGgajfLWnY96KKdw1DHvRRR
RoIMe9JtFLRQPUQLjvQVz1NLRQGo3YKNgp1FLQNRuwUbBTqKNA1G7BRsFOoo0DUbsFGwU6ijQNRu
wUbBTqKNA1G7BRsFOoo0DUTb7mjYtLRR7ohNo7UmwetOyD0NFMBuwUbBTqKB3Y3YKNgp1FAXY3YK
XYKWigLsbsFGwU6iiwhuwUeWPWnUUWAbsFKFApaKAEBaKRZonKupyrLwR719c/sGft8+J/CfiSx+
Dnxl12a/0XUJVt9J1a8kLS2Mp4WN2PLRn1JJU98cD5H/AApp3I++N2VhyrKcEYIrfD4iph6ilFnl
5tlGDzjCyoVo7rR9U+ln0P3CjbciuGzx971qQZAwa83/AGSfiHefFb9m7wf451J2a6utGjS7dv45
osxSN+LIx/GvSBnHNfXxlzRUu+p/OOIozw+InSlvFtfNaMKKKKoxCiiigBvlj2/75o8se3/fNOoo
Ab5Y9v8Avmk2Dd07+lPpO/4/0oBn47/tUf8AJznxB/7HC/8A/RzVwld3+1R/yc58Qf8AscL/AP8A
RzVwlfGVv4j9X+Z/TWWf8i2j/gj+QUUUVkdwUUUUAFFFFABRRRQAHpTCPbvT6bk9SKGGl7n17+xD
+wX8I/2jfgmvxE8banq8N4dWuLXbYXCJHsj27eCh55r2H/h0h+zn/wBB3xJ/4HR//G60P+CUAb/h
lJcf9DJe/wA0r6Ww/rX1GGwuFlQjJxWx+E51xDnmGzevSp15KKk0kn5nyz/w6Q/Zz/6DviT/AMDo
/wD43R/w6Q/Zz/6DviT/AMDo/wD43X1Nh/WjD+tb/UsL/Ijy/wDWjiD/AKCZfefLP/DpD9nP/oO+
JP8AwOj/APjdH/DpD9nP/oO+JP8AwOj/APjdfU2H9aMP60fUsL/Ig/1o4g/6CZfefLP/AA6Q/Zz/
AOg74k/8Do//AI3R/wAOkP2c/wDoO+JP/A6P/wCN19TYf1ow/rR9Swv8iD/WjiD/AKCZfefLP/Dp
D9nP/oO+JP8AwOj/APjdH/DpD9nP/oO+JP8AwOj/APjdfU2H9aMP60fUsL/Ig/1o4g/6CZfefLP/
AA6Q/Zz/AOg74k/8Do//AI3R/wAOkP2c/wDoO+JP/A6P/wCN19TYf1ow/rR9Swv8iD/WjiD/AKCZ
fefLP/DpD9nP/oO+JP8AwOj/APjdH/DpD9nP/oO+JP8AwOj/APjdfU2H9aMP60fUsL/Ig/1o4g/6
CZfefLP/AA6Q/Zz/AOg74k/8Do//AI3R/wAOkP2c/wDoO+JP/A6P/wCN19TYf1ow/rR9Swv8iD/W
jiD/AKCZfefLP/DpD9nP/oO+JP8AwOj/APjdH/DpD9nP/oO+JP8AwOj/APjdfU2H9aMP60fUsL/I
g/1o4g/6CZfefLP/AA6Q/Zz/AOg74k/8Do//AI3R/wAOkP2c/wDoO+JP/A6P/wCN19TYf1ow/rR9
Swv8iD/WjiD/AKCZfefLP/DpD9nP/oO+JP8AwOj/APjdH/DpD9nP/oO+JP8AwOj/APjdfU2H9aMP
60fUsL/Ig/1o4g/6CZfefLP/AA6Q/Z0/6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/
WjD+tL6lhf5EH+tHEH/QTL7z5Z/4dIfs5/8AQd8Sf+B0f/xuj/h0h+zn/wBB3xJ/4HR//G6+psP6
0Yf1p/UsL/Ig/wBaOIP+gmX3nyz/AMOkP2c/+g74k/8AA6P/AON0f8OkP2c/+g74k/8AA6P/AON1
9TYf1ow/rR9Swv8AIg/1o4g/6CZfefLP/DpD9nP/AKDviT/wOj/+N0f8OkP2c/8AoO+JP/A6P/43
X1Nh/WjD+tH1LC/yIP8AWjiD/oJl958s/wDDpD9nP/oO+JP/AAOj/wDjdH/DpD9nP/oO+JP/AAOj
/wDjdfU2H9aMP60fUsL/ACIP9aOIP+gmX3nyz/w6Q/Zz/wCg74k/8Do//jdH/DpD9nP/AKDviT/w
Oj/+N19TYf1ow/rR9Swv8iD/AFo4g/6CZfefLP8Aw6Q/Zz/6DviT/wADo/8A43R/w6Q/Zz/6DviT
/wADo/8A43X1Nh/WjD+tH1LC/wAiD/WjiD/oJl958s/8OkP2c/8AoO+JP/A6P/43R/w6Q/Z0/wCg
74k/8Do//jdfU2H9aMP60vqWF/kQf60cQf8AQTL7z5Z/4dIfs5/9B3xJ/wCB0f8A8bo/4dIfs5/9
B3xJ/wCB0f8A8br6mw/rRh/Wn9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q
/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f
8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8A
jdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA
6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6Dvi
T/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+
g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9
nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/
8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958
s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8A
oJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/Wji
D/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yI
P9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Sw
v8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+
tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1
ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X
1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A
+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8
Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k
/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/
AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A+N0f8OkP
2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wOj/8AjdH/
AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k/wDA6P8A
+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A6DviT/wO
j/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP2c/+g74k
/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8ADpD9nP8A
6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl958s/8OkP
2c/+g74k/wDA6P8A+N0f8OkP2c/+g74k/wDA6P8A+N19TYf1ow/rR9Swv8iD/WjiD/oJl958s/8A
DpD9nP8A6DviT/wOj/8AjdH/AA6Q/Zz/AOg74k/8Do//AI3X1Nh/WjD+tH1LC/yIP9aOIP8AoJl9
58s/8Okf2cxyde8Sf+B0f/xumn/gkp+zkemv+JPxvI//AI3X1Rh+5pAvoKX1LC3+FB/rPn+/1iX3
n5AftV/Cnw98Evjzrfwz8KXF1LYab5Ige8kDSHdGrHJAHc157XtP/BQzJ/a88VE+tt/6ISvFq+Xx
EYxrSS7n7xk1WpWymhUqO8nGLb76BRRRWJ6QUUUUAFFFFABRRRQAUUUUAFFFFABSN0/ClpH6UnsH
Q/Vf/gnXz+xp4KH/AE73X/pZNXtw6V4j/wAE6v8AkzbwSP8Ap3uv/Syavbug5r7Wj/Bh6I/mjOP+
RviP8cv/AEphRSFgBnNG7I4rQ84Wik3fSigBaKKKACk7/j/SlpO/4/0oEz8d/wBqj/k5z4g/9jhf
/wDo9q4Su7/ao/5Oc+IP/Y4X/wD6OauEr4yt/Efq/wAz+m8s/wCRbR/wR/IKKKKyO4KKKKACiiig
AooooAKafu/gKdTT938BR0A/S/8A4JQ/8mpr/wBjHe/+yV9L180f8Eof+TU1/wCxjvf/AGSvpevs
MH/usPRH848Rf8jzE/43+YUUUV0HihRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFF
ABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUA
FFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAU
UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABTR1/4FTqav/s1L7SA/Kf8A4KGf8nd+Kv8A
etv/AEQleK17V/wUM/5O78Vf71t/6ISvFa+PxX+8S9T+ksi/5EuH/wAEfyCiiisD1gooooAKKKKA
CiiigAooooAKKKKACmv0p1NfpR0YH6r/APBOxlT9jXwWXP8Ay7XX/pZNXs73oBxGm6vEv+CeEUbf
sdeC2kk4+z3Xy5/6e5q9vg+zbcJivssP/u8PRH815vZZtiP8cvzZERdTAsTtFLFdCKPDDcf51aI3
DFUWQJIyMK2OCL5lZlpLhXQMRRUMEqiJQVooJ5C1RRRQQFJ3/H+lLTSwBHPegD8eP2qP+TnPiD/2
OF//AOjmrhK7z9qgf8ZN/EDB/wCZuvv/AEca4Ovi638SXqz+msr/AORbR/wR/IKKKKzO4KKKKACi
iigAooooAKafu/gKdTT938BR0A/S/wD4JQ/8mpr/ANjHe/8AslfS9fNH/BKH/k1Nf+xjvf8A2Svp
evsMH/usPRH848Rf8jzE/wCN/mFFFFdB4oUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFA
BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUABOBk0BlPQ181ftS/8
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
4E+Hfhr4d6Z/ZnhyyK7/APj4uJPmkmPufT2GBXRhsvrSleasjwc842y3C4eUMJJVJtaWWifq9zd8
Nz33g+1tbTwzqFxZCzt44YWtZmjIVVCj7pHYV3Ph79o/4weHdqf8JU14i/8ALO+jWXP4nDfrXDEE
nj+VOGcc19EkoqyPxeUpTk5Sd23v66nu3hb9tJgUt/GfhI7c/Nc6a/T3KN/Q16l4U+K3gL4hwrF4
X8RRzzNy1s4McgPurYP5cV8bk460sV3cWcy3llcyRyRNlJIpCrKfqKCdD7xT7gwKK8P+Hnxq+Ll7
4MsZ5PAU2qny2X+0PO2eeFcqGI9cDk9yM96KBHafE/8AZ68FfExn1G4jax1Jul9ajlj/ALa9HH5H
3rxPxZ+yj8TvDpaXR4oNWgXJVrWTa5H+43f6Zr6qIyMU0pnuavlRNz4X1rQtd8N3H2XxBol1ZP8A
3bqBk/mOaphiV3ba+77rTbK/ha2v7WO4jb70c0YZT+BrjPE37PXwj11Wml8GW9vJ18yzzEf/AB3i
lyjTPkMODTq+hdX/AGQfBV18+k+JNQs/9mQLKv64P61zmt/sa+M7ZTNoHivTrtOqrcRvCx/LcP1q
S3Fx3PHaK7bU/wBnP4x6YefCguF/vWl0j5/UH9K5/UvAHjzSWxqPg7UoR3ZrNsfoKBGTRSzRXFsx
S6t3jYf89EK/zpgcN90j88/yoAdRTd53YxTs84xQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUU
UAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQ
AUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAB
RRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFF
FFABRRRQAUUUUAFFFFABRRRQAUUE4GTSbvQUALRSFsUiPvfavPsvJ/SgB1FXtP8ADHifVMDTvDl9
cZ/542rt+uK3tL+BHxg1cr9k8B3iq3RrjbGP/HmFPlA5Pn0pC2K9T0j9kD4qaiQdUudLsF/6aXDS
MPwRcfrXV6N+xTpqFZfEHjueZv4o7O0VB+bE/wAqfKxcyPAQ2fuiliEk8qwQRNJI3CxxqWY/gOa+
rNE/ZU+EGlFXutInvmX/AJ/LhmH5DArtNC8FeE/DEXk+HfDtnYjGM21uqt+eMmjlFzHyf4Z+AvxW
8VlXsvCc1vC3Se+/crj155/SvWPh3+x5oelzJqXj7V/7RkXn7HbApCPZj1f9BXtXljrk/nShcHNP
lRJWs9H0+wtY7Kyt1hhiQLHFF8qqB2AFFWqKOUAooopgFI21hhqUntSYBoAozRBJSMcfw1JbSqB5
bH6VNcW4lj4+9UH9nyf3qzN4yUo2ZCYwsjDaNval2LjGKm+wSf3hR9gk/vCgvmj3KV1pelXw2X2m
W8w9JoFb+YrE1T4T/DHVh/p/gLS3P95bNUP5qBXU/YZR0akNjJjlqA5oHnV5+y98HdTbEPhma3b+
9b30q/8AoRIrNu/2Nfh7N/x469q1uf8ArokgH5rXrsECwjJPNSZXsKrlMJSu9Dwi+/YptySdN8fS
D2nswf5Gsa+/Yy8ZRt/xLvF+nzDH/LSF1/lmvpEYPOKMD0p8qFzHy1efsi/F23Vjatpdx/uXhX/0
JRWVe/s1fG2yyx8GGZR3t7yFv5uDX11gelGB6UWQcx8WXvwh+K9gf9K+HmrqMZylmzj/AMdzWVc+
GfFFoM3fhjUof+umnyj+a190fhRS5Q5j4MkguoP9fbyJ670IqPed2N2PrX3hPpem3Qxc6dBJ/wBd
IVP8xVC48EeDr4E3PhTT5CeMtZp/hRyhzHxAOv3qWvs25+DvwuuR+/8AAWl/hagfyrOuP2dfg3cu
Wk8C2y7uvls6/wAmo5Q5j5Dor6su/wBlX4L3K4Xw7cQ/7UN/KP5k1Rn/AGQPhLKcpJq8Xosd8D/6
Epo5Q5j5hor6MuP2LPAzoRaeLdYjb+EuYmx/44P5iqE/7Eun7f8ARPiJcBv+mmnK38nFHKHMeBUV
7fP+xNrCvm0+IVuy/wDTTT2H8nNU5v2LfGkbN5PjHTXX+HdFIuf0P86kdzxyivVp/wBjz4noWEOp
6XJjpi4cf+y1Wm/ZI+L0a7kh0+T/AGVvBk/pQO55lRXoE37LnxmjQk+HoWx/cvE/xqnJ+zf8aIV3
nwXI3+7cR5/9CoC5xdFdXN8BvjDCu9/AN9/wHaf/AGY1UuPhD8VYRmT4fasB/wBebH+VAHP0VsP8
OviDEf3ngbVx9dPl/wAKqSeGPFMTlJfDGorjqPsMnH6UAUqKkmsdRgbbPptxGfSS3YfzFRtuj/1i
lf8AeGKACimieI8+av8A30KPPhzjzV/76FADqKAwPQ0Z5xQAUUe1GR60AFFFFABRRRmgAooooAKK
KM0AFFFFABRRnHWigAoozRQAUUZ7UZoAKKM56UZHrQAUUUUAFFFFABRRRQAUUUUAFFGaKACijOel
FABRRmigAoooyOmaACiiigAoozRQAUUUUAFFFGaACiijNABRRkdM0UAFFB45NGccmgAoppliX70i
/wDfVHnRf89V/wC+qAHUU1ZEc4R1b6GpBBdscJaTN/uwk0ANoq1FoPiCbmLw9ft/u2ch/pVuDwB4
9u132vgnV5F9V02U/wDstAXMqit6D4T/ABPugWg+H+sY/wBqxdcfmKswfBD4u3JxH4A1Ef70e3+Z
FAXOYorsrf8AZ3+M1wMp4GuF/wCukyL/AOzVat/2Y/jROMt4WWPH/PS6jH9TQFzg6K9Eg/ZX+L8z
Yewsoj/00vF/oKvRfsgfFWQZlutMj+tyx/ktAXPLaK9eg/Yy8fSIrT+JtLjPp+8bH/jtXIv2KPEr
Aef49sV/vbLF2/8AZhVcpPMeK0V7zb/sRoGH2z4jtt/i8nTP8ZDV61/Yo8Jo3+m+OdUkHbyoYk/m
Go5Q5j54or6Ys/2NvhdFHtudV1qY7s7vtSL+HCVoWn7J3wbgKtLpd9KR2l1B+fyxRyhzHyvQxOOt
fW8H7NXwXgH/ACJUbf8AXS4kb+bVo2/wQ+EtmVSHwBp/y9C0G7+dHKHMfGpcj+L+VLGHkO1FZj/d
Va+2LX4a+ALQYtvBmmKP+vNP8K0bbQdCtv8Aj20W1jx0226D+Qo5Q5j4ft9G1u7O210e8l3fd8u1
ds/kK0LL4cfEbUlzYeAtZk5xxpsg/mor7aSOOJdscaqPRVxTse1HKHMfHdj+z58adQH7nwBeJ/13
kij/APQmFbFl+yh8Z7o5m0uxtx/021BT/wCg5r6sowOuKOUOY+Z7X9jf4iSc3niDS4v91nb+grXs
/wBiq863/j6P6Q2R/qa+gcDGMUdeop8qDmPFrL9i7wfEQdR8YalN/eEMcaf0NasH7JXwcsEElzaa
hdMOP3182D/3xivVKhvE3x/Tmk0EfiOJ0v4E/CDTP3lt4CsmYdHnVpdv/fZNblr4W8L6TGo0/wAO
afbt1/c2aLj8hWggG75h+FBIPLBeetSdFiaxVAnlBFH4Va2iqtgnO/2q1mtDnn8QAAcgUY70UUEh
RRRQAUUUUAFFFFABRRRQBHPFJJ9xytV3jvF58yT8DVyjBzmgqMuUzzJcDgyyUedN/wA/Df8AfVXm
jVxgqPyqH7CP79S4mkakeqK/nTf8/Df99UedN/z8N/31Vj7CP79H2Ef36VmVz0+xX82c9J2/76oM
s4GTO3/fVWDYIerUf2en96izDnp9iDzJiu4SOadGt2/KsfxNW44ljXaKdVmbmuiI4I5Ix+8bcako
ooMwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAJxzVO7aN2Vk5Perh5GKz2XZI
yj86mRpTV5DWBCnbViMmK087dzULdKkgmQIYZvu1JrUXujYriYSBWbr60y/srWWXdNbRuSM/PGD/
ADFTiK3iIkZ6jctczYHQ9PagXxO6IIfD+j3KH7Vo9o6nja1sjZ/Sql14N8GyvsHhPTMD/qHx/wDx
NbUjrbRbV+92qnk54psUPek2Yk3wz+Hlw2+bwLpLH30+P/CoZfhJ8MZxiT4f6T+Fmo/lXRg9qKRr
ZHLp8E/hHvzJ8PNN/wCA29Pf9n/4N3OD/wAIJZj/AHC6/wAmFdLVu3j8pP8AeqomNRHGP+zp8GmG
0eCLce4kk/8AiqrSfsv/AAZkbd/wizL7LdSAfzr0GiqMTzl/2Wfg0x+Xw1Iv+7eP/jUE/wCyf8IG
+dNIu19lvm5/WvTaD0oHc8vH7KPwcnTKadfD/t9biq8n7JPwnlby7ZNR69ftnT9K9Qe1mDt5TbVb
71TRxLEoVRU8pVzykfsefC/qbvU//Aof/E02X9jn4Zt/q9S1RfpcKf8A2WvWqKOUnmZ5Af2Mvhye
f7e1b/v5H/8AE0h/Yx+HfbxDqw/7aR//ABFewUUcoXZ47J+xf4CK/u/E+rKfdo//AImq7/sV+EW+
54w1IfWOP/CvaqKOUfMzxP8A4Yo8Lf8AQ7akP+2Mf+FMn/Yq8LRR708baj/34jr2+myp5ibKOUOY
8J/4Y38OMMDxtqH42sf+NNb9jPQs5Xx1fD/tzT/GvcvsCDo5o+wj+/SszTmgeGP+xno55Xx7d/jY
J/8AFU+H9izTZ1Yr8Q7hceunr/8AF17h9hH9+pIYBCCFOc+tOxMnHoeG/wDDEVl/0Ue4/wDBcv8A
8XR/wxFZf9FGuP8AwXL/APF17sKKfKTzM8J/4Yisv+ijXH/guX/4uj/hiKy/6KNcf+C5f/i692oo
5Q5meE/8MRWX/RRrj/wXL/8AF0f8MRWX/RRrj/wXL/8AF17tRRyhzM8J/wCGIrL/AKKNcf8AguX/
AOLo/wCGIrL/AKKNcf8AguX/AOLr3aijlDmZ4SP2I7HOG+I1x/4Ll/8Ai6cP2JNL7/EO6/8AABf/
AIqvdKKXKK7PDR+xLowPzeP7w/8Abmn/AMVTh+xPoGfn8d3x/wC3SP8Axr3CijlC7PEh+xP4YPLe
N9Q/C3jpR+xP4VHTxvqX/fmOvbKKOULs8TX9ijwoD83jPUW/7ZR/4VPH+xb4IB/e+KtUb6eWP/Za
9loo5Q5mePD9jD4e5+bxFqx/4FH/APEUv/DGPw7/AOg/q3/fyP8A+Ir2CijlC7PIY/2Nfhwh+fWN
Wf8A7bIP5LUo/Y7+F44+06n/AOBQ/wAK9Zop2DmZ5TH+x/8ACxfvyak3/b0P8Kni/ZI+EUYw9nfP
/vXhr0+imHMzzNf2Tfg4D/yCrz/wOapo/wBlb4MqMN4fnb/evpP8a9GooEeeD9lz4MD/AJlh/wDw
Mk/xqzD+zb8GoRt/4QyJv96aQ/8As1d1RQBxI/Z3+Da8jwLan6yP/wDFVYi+A3wfRdv/AArzTf8A
v1muuooA5WH4JfCWA5i+HmlD3+yqf51Z/wCFT/DJTuT4f6Pnt/xL4/8ACuhooAyIfAXgmBdkPg3S
lHtp8f8A8TU9v4U8N2jZtPD1hF6+XZoufyFaFFAFddK05G3R6dbj0xCtTKu1NoUe2KdRQA0qxPWl
wcYJpaKAAZHWiiigBMN/epaKKAGmKMncUp1FFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQA
UUUUAFBAYYNFFAFaWyYvujP4UiWUpPzsMVaoqeUv2khiRBF2qMU/vnFFFUQFFFFABRRRQAUUUUAF
FFFABRRRQAUYyaKKCoiEcUtFFASCiiigkKKKKACjoaKKCvshRRRQSFFFFABRRRQAUUUUAFFFFABR
RRQAUUUUAFFFFABRRRQAUUUUAFZ7f61/96iipkbUeoUmAetFFSdERj8HAqxY8sxNFFBnU+FhqDN5
i89qLeNHj3MtFFBmvhQ2RVU8Cm0UUGwVZsiWT5jRRVRMquxPRRRVGAUUUUAFFFFABRRRQAUUUUAF
FFFABQelFFABijFFFBoGKB0oooJkFFFFBIUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFA
BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAF
FFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAH//ZUEsD
BBQABgAIAAAAIQBCFppzFQEAAIcBAAAPAAAAZHJzL2Rvd25yZXYueG1sXFDLTsMwELwj8Q/WInGj
TlIIJdSpqko81ANSW5A4WonTRI3Xke02oV/PhlIFcfPMemZnZzrrdM0OyrrKoIBwFABTmJm8wq2A
983TzQSY8xJzWRtUAr6Ug1l6eTGVSW5aXKnD2m8ZmaBLpIDS+ybh3GWl0tKNTKOQZoWxWnqCdstz
K1sy1zWPgiDmWlZIG0rZqEWpst16rwU08273vMhxiXJ8+NgcP7HZ1y9CXF9180dgXnV++Pyrfs0F
RNCfQmdASvm6eo5ZaSwrVspVRwp/4gtrNLOmJRwDy0xNjxB65q0onPICxpMwoCJodGYC4L2lNydh
dH8WUj1/hOFddPtP+RAHYfQj50OkdEpg6C/9BgAA//8DAFBLAwQUAAYACAAAACEAWGCzG7oAAAAi
AQAAHQAAAGRycy9fcmVscy9waWN0dXJleG1sLnhtbC5yZWxzhI/LCsIwEEX3gv8QZm/TuhCRpm5E
cCv1A4ZkmkabB0kU+/cG3CgILude7jlMu3/aiT0oJuOdgKaqgZGTXhmnBVz642oLLGV0CifvSMBM
CfbdctGeacJcRmk0IbFCcUnAmHPYcZ7kSBZT5QO50gw+WszljJoHlDfUxNd1veHxkwHdF5OdlIB4
Ug2wfg7F/J/th8FIOnh5t+TyDwU3trgLEKOmLMCSMvgOm+oaSAPvWv71WfcCAAD//wMAUEsBAi0A
FAAGAAgAAAAhAPS+Y10OAQAAGgIAABMAAAAAAAAAAAAAAAAAAAAAAFtDb250ZW50X1R5cGVzXS54
bWxQSwECLQAUAAYACAAAACEACMMYpNQAAACTAQAACwAAAAAAAAAAAAAAAAA/AQAAX3JlbHMvLnJl
bHNQSwECLQAUAAYACAAAACEAewoNmBUCAAD2BAAAEgAAAAAAAAAAAAAAAAA8AgAAZHJzL3BpY3R1
cmV4bWwueG1sUEsBAi0ACgAAAAAAAAAhAEJZK0erowEAq6MBABUAAAAAAAAAAAAAAAAAgQQAAGRy
cy9tZWRpYS9pbWFnZTEuanBlZ1BLAQItABQABgAIAAAAIQBCFppzFQEAAIcBAAAPAAAAAAAAAAAA
AAAAAF+oAQBkcnMvZG93bnJldi54bWxQSwECLQAUAAYACAAAACEAWGCzG7oAAAAiAQAAHQAAAAAA
AAAAAAAAAAChqQEAZHJzL19yZWxzL3BpY3R1cmV4bWwueG1sLnJlbHNQSwUGAAAAAAYABgCFAQAA
lqoBAAAA
">
   <v:imagedata src="image001.png" o:title=""/>
   <x:ClientData ObjectType="Pict">
    <x:SizeWithCells/>
    <x:CF>Bitmap</x:CF>
    <x:AutoPict/>
   </x:ClientData>
  </v:shape><![endif]--><![if !vml]><span style='mso-ignore:vglayout'>
  <table cellpadding=0 cellspacing=0 >
   <tr>
    <td width=0 height=5></td>
   </tr>
   <tr>
    <td></td>
    <td><img width=474 height=262 src=image002.png v:shapes="圖片_x0020_1"></td>
    <td width=27></td>
   </tr>
   <tr>
    <td height=9></td>
   </tr>
  </table>
  </span><![endif]><!--[if !mso & vml]><span style='width:375.6pt;height:204.0pt'></span><![endif]--></td>
 </tr>
 <tr height=26 style='mso-height-source:userset;height:19.2pt'>
  <td height=26 style='height:19.2pt'></td>
  <td class=xl109 colspan=5 style='mso-ignore:colspan'>註1：應付會員紅利金額計算：單筆金額之小數點無條件捨去。<ruby><font
  class="font11"><rt class=font11></rt></font></ruby></td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=26 style='mso-height-source:userset;height:19.2pt'>
  <td height=26 style='height:19.2pt'></td>
  <td class=xl109 colspan=5 style='mso-ignore:colspan'>註2：應付系統費用金額計算：合計金額之小數點無條件捨去。<ruby><font
  class="font11"><rt class=font11></rt></font></ruby></td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=26 style='mso-height-source:userset;height:19.2pt'>
  <td height=26 style='height:19.2pt'></td>
  <td class=xl109 colspan=7 style='mso-ignore:colspan'>註3：若對本對帳單內容有疑異，可掃描右方QRcode聯絡客服人員提供協助，謝謝。<ruby><font
  class="font11"><rt class=font11></rt></font></ruby></td>
  <td colspan=3 style='mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 
 <tr height=26 style='mso-height-source:userset;height:19.2pt'>
  <td height=26 style='height:19.2pt'></td>
  <td class=xl109 colspan=5 style='mso-ignore:colspan'><button id="renderPdf" onclick='writepdf();'>下載 PDF</button>&nbsp;&nbsp;<button id="backprofit" onclick="Goprofit();">返回</button><ruby><font
  class="font11"><rt class=font11></rt></font></ruby></td>
  <td colspan=5 style='mso-ignore:colspan'></td>
 </tr>

 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <tr height=22 style='height:16.2pt'>
  <td height=22 colspan=11 style='height:16.2pt;mso-ignore:colspan'></td>
 </tr>
 <![if supportMisalignedColumns]>
 <tr height=0 style='display:none'>
  <td width=64 style='width:48pt'></td>
  <td width=79 style='width:59pt'></td>
  <td width=86 style='width:64pt'></td>
  <td width=82 style='width:61pt'></td>
  <td width=86 style='width:64pt'></td>
  <td width=77 style='width:58pt'></td>
  <td width=74 style='width:55pt'></td>
  <td width=108 style='width:81pt'></td>
  <td width=32 style='width:24pt'></td>
  <td width=64 style='width:48pt'></td>
  <td width=64 style='width:48pt'></td>
  <td width=73 style='width:55pt'></td>
  <td width=27 style='width:20pt'></td>
  <td width=81 style='width:61pt'></td>
  <td width=64 style='width:48pt'></td>
  <td width=25 style='width:19pt'></td>
  <td width=77 style='width:58pt'></td>
  <td width=26 style='width:20pt'></td>
  <td width=128 style='width:96pt'></td>
 </tr>
 <![endif]>
</table>
    <script type="text/javascript" src="./js/html2canvas.js"></script>
    <script type="text/javascript" src="./js/jsPdf.debug.js"></script>
    <script type="text/javascript">

	  function writepdf()
	  {	  
	  var downPdf = document.getElementById("renderPdf");
	  downPdf.style.display="none";
	  document.getElementById("backprofit").style.display="none";
	  
	  html2canvas(document.body, {
              onrendered:function(canvas) {

                  var contentWidth = canvas.width;
                  var contentHeight = canvas.height;
                  //一页pdf显示html页面生成的canvas高度;
                  var pageHeight = contentWidth / 592.28 * 841.89;
				  //var pageHeight = contentWidth /  841.89 * 592.28;
                  //未生成pdf的html页面高度
                  var leftHeight = contentHeight;
                  //pdf页面偏移
                  var position = 0;
                  //a4纸的尺寸[595.28,841.89]，html页面生成的canvas在pdf中图片的宽高
                  var imgWidth = 595.28;
                  var imgHeight = 592.28/contentWidth * contentHeight;
                  //var imgWidth = 841.89;
                  //var imgHeight = 841.89/contentWidth * contentHeight;

                  var pageData = canvas.toDataURL('image/jpeg', 1.0);

                  var pdf = new jsPDF('', 'pt', 'a4');

                  //有两个高度需要区分，一个是html页面的实际高度，和生成pdf的页面高度(841.89)
                  //当内容未超过pdf一页显示的范围，无需分页
                  if (leftHeight < pageHeight) {
                      pdf.addImage(pageData, 'JPEG', 0, 0, imgWidth, imgHeight );
                  } else {
                      while(leftHeight > 0) {
                          pdf.addImage(pageData, 'JPEG', 0, position, imgWidth, imgHeight)
                          leftHeight -= pageHeight;
                          position -= 841.89;
                          //position -= 592.28;
                          //避免添加空白页
                          if(leftHeight > 0) {
                              pdf.addPage();
                          }
                      }
                  }

                  pdf.save('profitreport.pdf');
              }
          });
		 downPdf.style.display="block";
		 document.getElementById("backprofit").style.display="block";
	}		  
	function Goprofit()
	{
		self.location.replace("../profit.php");
	}		  
    </script>
</body>

</html>
