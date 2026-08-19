<?php
	define("PHPGRID_DBTYPE","mysqli"); // mysql,oci8(for oracle),mssql,postgres,sybase
	define("PHPGRID_LIBPATH", dirname(__FILE__)."/../".DIRECTORY_SEPARATOR."gridphp_object".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR);
	
	$g_is_online 		= true;
	$g_online 			= ($g_is_online) ? "ON-LINE" : "OFF-LINE"; // ON-LINE(線上版)、OFF-LINE(離線版)
	$g_online_zhtw 		= ($g_is_online) ? "" : "[離線版]";
	$g_backend_title 	= "量測後台管理系統";
	$g_supperuser_all 	= false;
	
	$g_is_remote = false;

	$g_google_map_api_key = 'AIzaSyD_1YB9j-QJE91x2_73S7RA3jtOGIBoMKU';
	
	// 資料庫
	// -----------------------------------------------------------------------------------------------------------------
	$g_db_ip		= '127.0.0.1'; // 當地 mysql ip，不用改
	$g_db_user		= "root";
	$g_db_pwd		= ($g_is_remote) ? "JTG@1qaz@WSX" : "";

	$g_db_name		= "gwapi";
	$g_proj_url 	= ($g_is_remote) ? 'http://3.37.144.108/' : 'http://localhost/醫療/國健/';
	// $g_db_name		= "cdil_b2b_test"; // cdil_b2b_test
	// $g_proj_url 	= 'http://202.5.253.133/'; // 'http://202.5.253.133/'
	$g_proj_name 	= $g_db_name;

	$g_export_max = 30000;
	// 加密金鑰
	$g_jotangiwww = "gwapi";
	// -----------------------------------------------------------------------------------------------------------------
	//$key = "9Dcl8uXVFt/vSYaizaE+KkAgXtYO0807"; //prod
	$key 	= "YcL+NyCRl5FYMWhozdV5V8eu6qv3cLDL";	//uat
	$g_iv  	= "77215989@jotangi";
	$g_token_expire_sec = 3 * 24 * 60 * 60;
	$g_k 	= 'PRJFy9bRrZZbO2CtpMJN6IcOffu5ODscEp8sknwEoRsIr2kPFOu5ru96ovaJFW2d';

	// 系統參數
	// -----------------------------------------------------------------------------------------------------------------
	$g_exit_symbol						= "---------------------------  ";
	$g_test_mode						= true;
	$g_skip_over_12hr_day				= true;
	$g_wjson2file_flag					= true;
	$g_wpdf2file_flag					= true;

	$g_encrypt = [
					'id'       					=> false,
					'mobile'      				=> false,
					'member_name'   			=> false,
					'image'    					=> false,
					'ignor_verify_face'    		=> true
				 ];

	$g_trace_log = [
					'JTG_wh_log'       			=> true,
					'JTG_wh_log_Exception'      => true,
					'wh_log'   					=> true,
					'wh_log_watch_dog'    		=> true,
					'wh_log_Exception'    		=> true,
					'wtask_log'    				=> true,
					'wtask_log_Exception'    	=> true
				   ];

	// 資料表定義-供各頁面使用，未來不需要改許多地方，只需改此即可
	$g_db_table= ['datamember'					=> 'data_member'					, // 會員資料表
				  'logmember'					=> 'log_member'						, // 會員紀錄資料表

				  'logmessage' 					=> 'log_message'					, // 訊息紀錄資料表
				  'logprogress' 				=> 'log_progress'					, // 匯入匯出百分比紀錄資料表
				 ];

	// 路徑
	// -----------------------------------------------------------------------------------------------------------------
	$g_root_url			 				= $g_proj_url.$g_proj_name."/"						;
	$g_download_ios_url			 		= $g_proj_url.$g_proj_name."/lds-install-ios.php"	; // 一般營業員使用ipad，通常為ios系統
	$g_root_dir			 				= $_SERVER["DOCUMENT_ROOT"].'/'.$g_proj_name.'/'	; // 網站根目錄	"/var/www/html/fhpro/"
	$g_log_path		  	 				= $g_root_dir."log/"								; // log directory
	$g_xlsx_out_path		  	 		= $g_root_dir."excel/export/"						; // excel directory
	$g_xlsx_in_path		  	 			= $g_root_dir."excel/import/"						; // excel directory
	$g_json_path	  	 				= $g_root_dir."json/"								; // json directory
	$g_images_dir 						= $g_root_dir."images/"								; // 照片 directory
	$g_live_dir 						= $g_root_dir."live/"								; // 照片 directory
	$g_attachment_dir 					= $g_root_dir."attachment/"							; // 附件照片 directory
	$g_watermark_src_url 				= $g_root_url."watermark.png"						; // 浮水印來源

	$g_newsimg_path 					= "images/upload/news/"								; // 最新消息上傳照片路徑
	$g_bannerimg_path 					= "images/upload/banner/"							; // 最新消息上傳照片路徑
	$g_sightseeingimg_path				= "images/upload/sightseeing/"						; // 最新消息上傳照片路徑
	$g_storeimg_path 					= "images/upload/store/"							; // 最新消息上傳照片路徑
	$g_pdf_path 						= "images/upload/pdf/"								; // 最新消息上傳照片路徑
	$g_signaturepdf_path				= "images/upload/signature/"						; // 最新消息上傳照片路徑
	$g_signature_path					= "images/upload/signature/sign/"					; // 最新消息上傳照片路徑
	$g_attachments_path					= "images/upload/attachments/"						; // 最新消息上傳照片路徑
	$g_fcmimg_path 						= "images/upload/fcm/"								; // 最新消息上傳照片路徑
	$g_productimg_path 					= "images/upload/pdct/"								; // 最新消息上傳照片路徑
	$g_memberimg_path 					= "images/upload/member/"							; // 最新消息上傳照片路徑
	$g_notifyimg_path 					= "images/upload/notify/"							; // 最新消息上傳照片路徑
	$g_conferenceroomimg_path			= "images/upload/conferenceroom/"					; // 會議室上傳照片路徑
	$g_verifyimg_path 					= "images/verify/"									; // 最新消息上傳照片路徑
?>