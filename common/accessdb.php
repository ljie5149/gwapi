<?php
    class CXDB {
        protected $host     = "";
        protected $user     = "";
        protected $passwd   = "";
        protected $database = "";
        protected $remoteip = "";
        public function __construct($remote_ip) {
            $this->host     = getHost();
            $this->user     = getUser();
            $this->passwd   = getPassword();
            $this->database = getDatabase();
            $this->remoteip = $remote_ip;
        }
        public function query($link, $sql)
        {
            $result = null;
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        public function multi_execute($link, $sql)
        {
            $ret_msg = "";
            if (mysqli_multi_query($link, $sql)) {
                while(mysqli_more_results($link)) {
                    mysqli_next_result($link);
                }
            } else {
                $ret_msg = "執行錯誤: ".mysqli_error($link);
            }
            return $ret_msg;
            // return mysqli_affected_rows($link);
        }
        public function execute($link, $sql, &$ret_msg)
        {
			mysqli_query($link, $sql);// or die(mysqli_error($link));
            $ret_msg = mysqli_error($link);
            return mysqli_affected_rows($link);
        }
        public function executeWithSaveLog($link, $sql
                            , $mid , $title, $sumarry, $content)
        {
			mysqli_query($link, $sql);// or die(mysqli_error($link));
            
            $ret_msg = mysqli_error($link);
            if (!empty($RetMsg))
                $this->saveLog($link, $mid, "sql", $title, $sumarry, "error :".$ret_msg);
            return mysqli_affected_rows($link);
        }
        public function queryWithSaveLog($link, $sql
                            , $mid , $title, $sumarry, $content)
        {
            $result = null;
            if ($result = mysqli_query($link, $sql)) {
                if (mysqli_num_rows($result) <= 0)
                    $this->saveLog($link, $mid, "sql", $title, $sumarry, $content);
                return $result;
            }
            $RetMsg = mysqli_error($link);
            if (!empty($RetMsg))
                $this->saveLog($link, $mid, "sql", $title, $sumarry, "error :".$RetMsg);
            return null;
        }
        public function queryWithLog($link, $sql, $remote_ip, $person_id, $who_call)
        {
            $result = null;
            if ($result = mysqli_query($link, $sql)) {
                if (mysqli_num_rows($result) <= 0)
                    wh_log($remote_ip, "not found :".$sql, $person_id, $who_call);

                return $result;
            }
            $RetMsg = mysqli_error($link);
            if (!empty($RetMsg))
                wh_log($remote_ip, "error :".$RetMsg, $person_id, $who_call);
            return null;
        }
        public function getFieldsComment($link, $table, $without_columns="")
        {
            // echo $without_columns;
            $ret = array();
            $out_col = str2array($without_columns);
            $sql = "SHOW FULL COLUMNS FROM $table";
            $result = mysqli_query($link, $sql);
            while ($row = mysqli_fetch_array($result)) {
                unset($retLvl02); // $retLvl02 is gone
                $retLvl02 = array();
                $flag = false;

                $flag = (empty($without_columns) || findStrInArray($out_col, $row['Field']) == -1);
                if ($flag) {
                    array_push($retLvl02, $row['Field']);   // 0: name
                    $common_tmp = $row['Comment'];
                    if (stripos($common_tmp, ';;') === false) {
                        array_push($retLvl02, $row['Comment']); // 1: comment
                    } else {
                        $common_array = explode(';;', $common_tmp);
                        array_push($retLvl02, $common_array[0]); // 1: comment
                    }
                    array_push($ret, $retLvl02);
                }
            }
            // var_dump($ret);
            return $ret;
        }
        public function getTableColumnComments($link, $table
                                             , $without_columns="", $showbuthide_columns="", $lockedit_columns="", $search_columns="")
        {
            $ret = array();
            $sql = "SHOW FULL COLUMNS FROM $table";
            $result = mysqli_query($link, $sql);
            $out_col = str2array($without_columns);
            $sbh_col = str2array($showbuthide_columns);
            $lke_col = str2array($lockedit_columns);
            $sch_col = str2array($search_columns);
            while ($row = mysqli_fetch_array($result)) {
                unset($retLvl02); // $retLvl02 is gone
                $retLvl02 = array();
                $flag = "";

                array_push($retLvl02, $row['Field']);   // 0: name
                $common_tmp = $row['Comment'];
                if (stripos($common_tmp, ';;') === false) {
                    array_push($retLvl02, $row['Comment']); // 1: comment
                    array_push($retLvl02, '');              // 2: preholder
                    array_push($retLvl02, '');              // 3: field length
                } else {
                    $common_array = explode(';;', $common_tmp);
                    array_push($retLvl02, $common_array[0]); // 1: comment
                    array_push($retLvl02, $common_array[1]); // 2: preholder
                    array_push($retLvl02, $common_array[2]); // 3: field length
                }
                $flag = (empty($without_columns) || findStrInArray($out_col, $row['Field']) == -1) ? 'true' : 'false';
                
                array_push($retLvl02, $flag);

                if ($row['Field'] == "nid") { // 5: show but hide
                    $flag = 'false';
                } else {
                    $flag = (!empty($showbuthide_columns) && findStrInArray($sbh_col, $row['Field']) > -1) ? 'true' : 'false';
                }
                array_push($retLvl02, $flag);

                $flag = (empty($lockedit_columns) || findStrInArray($lke_col, $row['Field']) == -1) ? 'false' : 'true'; // 6: editable
                array_push($retLvl02, $flag);
                    
                $flag = (empty($search_columns) || findStrInArray($sch_col, $row['Field']) == -1) ? 'false' : 'true'; // 7: srch
                array_push($retLvl02, $flag);

                array_push($ret, $retLvl02);
            }
            // var_dump($ret);
            return $ret;
        }
        private function assignFields4Json($label, $name, $editable=false, $search=false, $hidden = false)
        {
            $cxobj = new CXmemberFields();
            $cxobj->label    = $label;
            $cxobj->name     = $name;
            $cxobj->editable = $editable;
            $cxobj->search   = $search;
            $cxobj->hidden   = $hidden;
            return $cxobj;
        }
        public function getTableColumnJson($link, $table, $without_columns="", $editable_columns="") {
            $ret = array();
            $sql = "SHOW FULL COLUMNS FROM $table";
            $result = mysqli_query($link, $sql);
            
            $cxobjhead = $this->assignFields4Json('編號', 'row_num');
            array_push($ret, $cxobjhead);
            $cxobjtmp = $this->assignFields4Json('sql編號', 'id', false, false, true);
            array_push($ret, $cxobjtmp);
            while ($row = mysqli_fetch_array($result)) {
                if (empty($without_columns) || stripos($without_columns, $row['Field']) == false) {
                    if (empty($editable_columns) || stripos($editable_columns, $row['Field']) == false) {
                        $cxobj = $this->assignFields4Json($row['Comment'], $row['Field'], false, false);
                    } else {
                        $cxobj = $this->assignFields4Json($row['Comment'], $row['Field'], true, true);
                    }
                    array_push($ret, $cxobj);
                }
            }
            $cxobjtail = $this->assignFields4Json('Action', 'button');
            array_push($ret, $cxobjtail);
            return json_encode($ret);
        }

        function saveBusinessLog($link, $tableLog, 
                $noColumn, $nameColumn, 
                $action_type, $change_data, 
                $action_user, $action_ip, 
                $action_note = ''
        ) {
            // 1. 安全轉義欄位名稱與數值
            $tableLog_esc     = mysqli_real_escape_string($link, $tableLog);
            
            $no_col           = mysqli_real_escape_string($link, $noColumn['col_name']);
            $no_val           = isset($noColumn['val']) && $noColumn['val'] !== '' ? "'" . mysqli_real_escape_string($link, $noColumn['val']) . "'" : "NULL";
            
            $name_col         = mysqli_real_escape_string($link, $nameColumn['col_name']);
            $name_val         = isset($nameColumn['val']) && $nameColumn['val'] !== '' ? "'" . mysqli_real_escape_string($link, $nameColumn['val']) . "'" : "NULL";

            $action_type_esc  = mysqli_real_escape_string($link, strtoupper($action_type));
            
            // JSON 轉換與轉義
            $json_str         = is_string($change_data) ? $change_data : json_encode($change_data, JSON_UNESCAPED_UNICODE);
            $change_data_esc  = "'" . mysqli_real_escape_string($link, $json_str) . "'";

            $action_user_esc  = "'" . mysqli_real_escape_string($link, $action_user) . "'";
            $action_ip_esc    = "'" . mysqli_real_escape_string($link, $action_ip) . "'";
            $action_note_esc  = "'" . mysqli_real_escape_string($link, $action_note) . "'";

            // 2. 動態組合 SQL
            $sql = "INSERT INTO {$tableLog_esc} (
                        {$no_col}, 
                        {$name_col}, 
                        action_type, 
                        change_data, 
                        action_user, 
                        action_ip, 
                        action_note, 
                        created_at
                    ) VALUES (
                        {$no_val}, 
                        {$name_val}, 
                        '{$action_type_esc}', 
                        {$change_data_esc}, 
                        {$action_user_esc}, 
                        {$action_ip_esc}, 
                        {$action_note_esc}, 
                        NOW()
                    )";

            return mysqli_query($link, $sql);
        }

        public function saveLog($link, $member_sid, $message_kind, $title, $summary, $content, $script="", $remark="") {
            //member_id	member_name	store_id	function_name	log_date	action_type
            global $g_db_table;
            $table = $g_db_table['logmessage'];
            
            $sql = "INSERT INTO $table (create_date, member_sid, message_kind, title, summary, content";
            $sql.= (!empty($script)) ? ", script" : "";
            $sql.= (!empty($remark)) ? ", remark" : "";
            $sql.= ") VALUES (NOW(), '$member_sid', '$message_kind', '$title', '$summary', '$content'";
            $sql.= (!empty($script)) ? ", '$script'" : "";
            $sql.= (!empty($remark)) ? ", '$remark'" : "";
            $sql.= ");";
            mysqli_query($link, $sql) or die(mysqli_error($link));
        }
        // 通用資料庫函式 - 基本參數 public
        public function connect(&$link, $Person_id, $file_header="Api")
        {
            global $g_exit_symbol;
            global $g_db_ip, $g_db_user, $g_db_pwd, $g_db_name;
            
            $data = array();
            if (is_null($link))
            {
                $link = mysqli_connect($this->host, $this->user, $this->passwd, $this->database);
                $data = result_connect_error($link);
                if ($data["status"] == "false")
                {
                    wh_log($this->remoteip, get_error_symbol($data["code"])." connect mysql result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"." exit ->"."\r\n", $Person_id, $file_header);
                    header('Content-Type: application/json');
                    echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                    return $data;
                }
                mysqli_query($link,"SET NAMES 'utf8'");
            }
            else
                $data = result_message("true", "0x0200", "資料庫已連線", "");
            return $data;
        }
        // 通用資料庫函式 - 新增會員資料
        public function insertMember($link          , $mid              , $pwd
                                   , $name          , $identity         , $mail         , $mobile
                                   , $gender        , $isforeign        , $birthday     , $start_date
                                   , $priority=1    , $avalible="N"       , $authorization_page=1
                                   , $cur_coupon=0  , $cur_point=0
                                   , $eng_name=""   , $advertising_id="", $device_id="" , $blood_type="", $tel=""
                                   , $script=""     , $remark="")
        {
            // $input_data = ['sid'        => $sid         , 'pwd'                 => $pwd
            //              , 'name'       => $name        , 'eng_name'            => $eng_name
            //              , 'identity'   => $identity    , 'mail'                => $mail
            //              , 'mobile'     => $mobile      , 'advertising_id'      => $advertising_id, 'device_id' => $device_id
            //              , 'gender'     => $gender      , 'isforeign'           => $isforeign, 'birthday'       => $birthday
            //              , 'blood_type' => $blood_type  , 'tel'                 => $tel
            //              , 'start_date' => $start_date  , 'priority'            => $priority
            //              , 'avalible'   => $avalible    , 'authorization_page'  => $authorization_page
            //              , 'cur_coupon' => $cur_coupon  , 'cur_point'           => $cur_point
            //              , 'script'     => $script      , 'remark'              => $remark
            //               ];
            global $g_db_table;
			$table = $g_db_table['datamember'];

			$sql ="INSERT INTO $table (create_date, sid, mid, pwd, name, identity, mail, mobile
                                     , gender, isforeign, birthday, start_date";
            $sql.= ($priority   > -1        ) ? ", priority"            : "";
            $sql.= ($avalible   > -1        ) ? ", avalible"            : "";
            $sql.= ($authorization_page > -1) ? ", authorization_page"  : "";
            $sql.= (!empty($eng_name)       ) ? ", eng_name"            : "";
            $sql.= ($cur_coupon > -1        ) ? ", cur_coupon"          : "";
            $sql.= ($cur_point  > -1        ) ? ", cur_point"           : "";
            $sql.= (!empty($eng_name)       ) ? ", eng_name"            : "";
            $sql.= (!empty($advertising_id) ) ? ", advertising_id"      : "";
            $sql.= (!empty($device_id)      ) ? ", device_id"           : "";
            $sql.= (!empty($blood_type)     ) ? ", blood_type"          : "";
            $sql.= (!empty($tel)            ) ? ", tel"                 : "";
            $sql.= (!empty($script)         ) ? ", script"              : "";
            $sql.= (!empty($remark)         ) ? ", remark"              : "";
            $sql.= ") VALUES (";
            
            $sql.= "NOW(), '".getUniqueId()."', '".$mid."', '".$pwd."', '".$name."', '".$identity."', '".$mail."', '".$mobile
                    ."', '".$gender."', '".$isforeign."', '".$birthday."', '".$start_date."'";
            
            $sql.= (!empty($start_date)     ) ? ", '".$start_date."'"           : ", NOW()";
            $sql.= ($priority   > -1        ) ? ", '".$priority."'"             : "";
            $sql.= ($avalible   > -1        ) ? ", '".$avalible."'"             : "";
            $sql.= ($authorization_page > -1) ? ", '".$authorization_page."'"   : "";
            $sql.= (!empty($eng_name)       ) ? ", '".$eng_name."'"             : "";
            $sql.= ($cur_coupon > -1        ) ? ", '".$cur_coupon."'"           : "";
            $sql.= ($cur_point  > -1        ) ? ", '".$cur_point."'"            : "";
            $sql.= (!empty($eng_name)       ) ? ", '".$eng_name."'"             : "";
            $sql.= (!empty($advertising_id) ) ? ", '".$advertising_id."'"       : "";
            $sql.= (!empty($device_id)      ) ? ", '".$device_id."'"            : "";
            $sql.= (!empty($blood_type)     ) ? ", '".$blood_type."'"           : "";
            $sql.= (!empty($tel)            ) ? ", '".$tel."'"                  : "";
            $sql.= (!empty($script)         ) ? ", '".$script."'"               : "";
            $sql.= (!empty($remark)         ) ? ", '".$remark."'"               : "";
            $sql.= ");";

			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 取得會員是否存在
        public function existsSysuser($link, $mid, $pwd, $select_str = "*", $avalible = "", $sort_str = "", $limit_str = "")
        {
            $table = 'data_user';

            // 1. 白名單過濾 select_str
            $select_str = preg_replace('/[^a-zA-Z0-9_,*\s`]/', '', $select_str);

            // 2. 建立動態 SQL 語句與綁定參數
            $sql = "SELECT $select_str FROM `$table` WHERE mid = ? AND pwd = ?";
            $types = "ss";
            $bind_values = [$mid, $pwd];

            // 若未來 Schema 補上 avalible 欄位再啟用此處
            if ($avalible !== "") {
                $sql .= " AND avalible = ?";
                $types .= "s";
                $bind_values[] = $avalible;
            }

            // 3. 安全處理 ORDER BY 與 LIMIT
            if (!empty($sort_str)) {
                $clean_sort = preg_replace('/[^a-zA-Z0-9_,\sASCdesc]/i', '', $sort_str);
                $sql .= " ORDER BY " . $clean_sort;
            }

            if (!empty($limit_str)) {
                $clean_limit = preg_replace('/[^0-9,\s]/', '', $limit_str);
                $sql .= " LIMIT " . $clean_limit;
            }

            // 4. 執行 Prepared Statement
            if ($stmt = mysqli_prepare($link, $sql)) {
                // 正確建立傳參考陣列：第一個參數必須是 $types 變數，後面為動態參數的引用
                $params = [$types];
                foreach ($bind_values as $key => $value) {
                    $params[] = &$bind_values[$key];
                }

                call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $params));

                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    mysqli_stmt_close($stmt);
                    return $result;
                }
                mysqli_stmt_close($stmt);
            }

            return null;
        }
        // 通用資料庫函式 - 取得會員是否存在
        public function existsMember($link, $mid, $pwd, $select_str="*", $where_str="", $avalible="Y")
        {
            return $this->getMember($link, $mid, $select_str, $where_str, $avalible, $pwd, true);
        }
        // 通用資料庫函式 - 取得table資料
        public function getData($link, $table, $mid="", $select_str="*", $where_str="", $avalible="", $sort_str="", $limit_str="")
        {
            $sql = "SELECT $select_str FROM $table where 1=1";
            $sql.= (!empty($where_str)) ? " ".$where_str : "";
            $sql.= merge_sql_string_if_not_empty("avalible", $avalible);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            $sql.= (!empty($limit_str)) ? " ".$limit_str : "";
            // echo $sql;
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得會員資料
        public function getMember($link, $mid="", $select_str="*", $where_str="", $avalible="", $pwd="", $check_exist=false, $sort_str="", $limit_str="")
        {
            global $g_db_table;
			$table = $g_db_table['datamember'];

            $sql = "SELECT $select_str FROM $table WHERE 1=1";
            $sql.= (!empty($where_str)) ? " ".$where_str : "";
            if ($check_exist) {
                $sql.= " AND mid='$mid'";
                $sql.= " AND pwd='$pwd'";
            } else {
                $sql.= merge_sql_string_if_not_empty("mid", $mid);
                $sql.= merge_sql_string_if_not_empty("pwd", $pwd);
            }
            $sql.= merge_sql_string_if_not_empty("avalible", $avalible);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            $sql.= (!empty($limit_str)) ? " ".$limit_str : "";
            // echo $sql;
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        public function getDataViaField($link, $sql, $field = "sid")
        {
            $ret = "";
            try {
                if ($result = mysqli_query($link, $sql)) {
                    if (mysqli_num_rows($result) > 0) {
                        if ($row = mysqli_fetch_array($result)) {
                            $ret = strval($row[$field]);
                        }
                    }
                }
            } catch (Exception $e) { }
            return $ret;
        }
        public function getMemberSid($link, $mid, $avalible = "")
        {
            global $g_db_table;
			$table = $g_db_table['datamember'];

            $sql = "SELECT sid FROM $table WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("mid", $mid);
            $sql.= merge_sql_string_if_not_empty("avalible", $avalible);
            // echo $sql;
			if ($result = mysqli_query($link, $sql)) {
                if (!is_null($result) && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        return $row['sid'];
                    }
                }
            }
            return "";
        }
        // 通用資料庫函式 - 取得會員資料
        public function getCmymember($link, $mid="", $select_str="*", $where_str="", $avalible="", $pwd="", $check_exist=false, $sort_str="", $limit_str="")
        {
            global $g_db_table;
			$table = $g_db_table['datacmymember'];

            $sql = "SELECT $select_str FROM $table where 1=1";
            $sql.= (!empty($where_str)) ? $where_str : "";
            if ($check_exist) {
                $sql.= " AND mid='$mid'";
                $sql.= " AND pwd='$pwd'";
            } else {
                $sql.= merge_sql_string_if_not_empty("mid", $mid);
                $sql.= merge_sql_string_if_not_empty("pwd", $pwd);
            }
            $sql.= merge_sql_string_if_not_empty("avalible", $avalible);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            $sql.= (!empty($limit_str)) ? " ".$limit_str : "";
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 更新會員資料
        public function updateMember($link, $mid
                                   , $member_name, $member_gender, $member_email, $member_birthday, $member_address, $member_phone
                                   , $member_status=-1, $mobile_no="", $member_pwd="", $user_code="", $notification_token="", $resetPwdUsercode=false, $member_trash=-1)
        {
            global $g_db_table;
            $table = $g_db_table['datamember'];
            
            $sql = "UPDATE $table SET ";
            $sql.= merge_sql_string_set_value("member_updated_at", "NOW()", "=", false, true);
            $sql.= ($resetPwdUsercode) ?  "reset_code=".$user_code : merge_sql_string_set_value("reset_code", $user_code);
            $sql.= merge_sql_string_set_value("member_pwd", $member_pwd);

            $sql.= merge_sql_string_set_value("member_name", $member_name);
            $sql.= merge_sql_string_set_value("member_gender", $member_gender);
            $sql.= merge_sql_string_set_value("member_email", $member_email);
            $sql.= merge_sql_string_set_value("member_birthday", $member_birthday);
            $sql.= merge_sql_string_set_value("member_status", $member_status, "=", true);
            $sql.= merge_sql_string_set_value("member_trash" , $member_trash, "=", true);
            
            $sql.= merge_sql_string_set_value("notificationToken", $notification_token);

            $sql.= "where 1=1";
            $sql .= merge_sql_string_if_not_empty("mid", $mid);
            $sql .= merge_sql_string_if_not_empty("member_id", $mobile_no);
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 檢查sid
        public function existsSid($link, $table, $sid="")
        {
            $ret = 0;
            try {
                $sql = "SELECT count(*) AS 'total_count' FROM $table where 1=1";
                $sql.= " AND sid LIKE '%$sid%'";
                if ($result = mysqli_query($link, $sql)) {
                    if (mysqli_num_rows($result) > 0) {
                        if ($row = mysqli_fetch_array($result)) {
                            $ret = strval($row['total_count']);
                        }
                    }
                }
            } catch (Exception $e) {
                $ret = 0;
            }
            return ($ret > 0);
        }
        // 通用資料庫函式 - 取得訂單資料
        public function getOrder($link, $sid, $select_str="*", $where_str="", $avalible="Y")
        {       
            global $g_db_table;
			$table = $g_db_table['dataorder'];

            $sql = "SELECT $select_str FROM $table where 1=1";
            $sql .= (!empty($where_str)) ? $where_str : "";
            $sql .= merge_sql_string_if_not_empty("avalible", ">=".$avalible, "", true);
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        public function getRowcount($link, $sql)
        {
            $ret = 0;
            try {
                if ($result = mysqli_query($link, $sql)) {
                    if (mysqli_num_rows($result) > 0) {
                        if ($row = mysqli_fetch_array($result)) {
                            $ret = strval($row['row_count']);
                        }
                    }
                }
            } catch (Exception $e) { }
            return $ret;
        }
        // 通用資料庫函式 - 新增百分比資料
        public function modifyProgress($link, $mid, $file_name, $percentage, $flag='import')
        {
			$table = 'log_progress';

            $sql = "SELECT count(*) AS row_count FROM $table where member_sid='$mid' AND file_name='$file_name' AND flag='$flag';";
            if ($this->getRowcount($link, $sql) == 0) {
                $sql = "INSERT INTO $table (member_sid, file_name, flag, create_date, percentage
                       ) VALUES (
                            '$mid', '$file_name', '$flag', NOW(), $percentage
                       );";
            } else {
                $sql = "UPDATE $table SET ";
                $sql.= merge_sql_string_set_value("percentage", $percentage, "=", true, true);
                $sql.= merge_sql_string_set_value("modify_date", 'NOW()', "=", true);
                $sql.= " WHERE 1=1";
                $sql.= merge_sql_string_if_not_empty("member_sid", $mid);
                $sql.= merge_sql_string_if_not_empty("file_name", $file_name);
                $sql.= merge_sql_string_if_not_empty("flag", $flag);
            }
            $ret_msg = "";
            return $this->execute($link, $sql, $ret_msg);
        }
        // 通用資料庫函式 - 取得百分比數值
        public function getProgressPercentage($link, $mid, $file_name, $flag='import')
        {
			$table = 'log_progress';

            $ret = 0;
            $sql = "SELECT percentage FROM $table WHERE member_sid='$mid' AND file_name='$file_name' AND flag='$flag';";
            $result = $this->query($link, $sql);
            if (!is_null($result) && mysqli_num_rows($result) > 0) {
                if ($row = mysqli_fetch_array($result)) {
                    $ret = strval($row['percentage']);
                }
            }
            return $ret;
        }
        // 通用資料庫函式 - 刪除百分比資料
        public function deleteProgress($link, $mid, $file_name, $flag='import')
        {
			$table = 'log_progress';

            $ret = 0;
            $sql = "DELETE FROM $table WHERE member_sid='$mid' AND file_name='$file_name' AND flag='$flag';";
            $ret_msg = "";
            $ret = $this->execute($link, $sql, $ret_msg);
            return $ret;
        }
        //--------------------------------------------------------------------------------------------------
        
        // 通用資料庫函式 - 驗證碼通過更新會員狀態
        public function updateMemberStatus($link, $mobile_no, $member_status=1) // 1:手機驗證通過
        {
            return $this->updateMember($link, "", "", "", "", "", "", ""
                                     , $member_status, $mobile_no);
        }
        // 通用資料庫函式 - 驗證碼通過更新會員狀態
        public function updateMemberNotificationToken($link, $mid, $token)
        {
            return $this->updateMember($link, $mid, "", "", "", "", "", ""
                                     , -1, "", "", "", $token);
        }
        // 通用資料庫函式 - 驗證碼通過更新會員密碼
        public function updateMemberPwd($link, $mid, $member_pwd)
        {
            return $this->updateMember($link, $mid, "", "", "", "", "", ""
                                     , -1, "", $member_pwd);
        }
        // 通用資料庫函式 - 驗證碼通過更新會員驗證碼
        public function updateMembertrash($link, $mid, $member_trash)
        {
            return $this->updateMember($link, $mid, "", "", "", "", "", ""
                                     , -1, "", "", "", "", false, $member_trash);
        }
        public function updateUserCode($link, $mid, $user_code)
        {
            return $this->updateMember($link, $mid, "", "", "", "", "", ""
                                     , -1, "", "", $user_code);
        }
        public function resetMemberPwdUsercode($link, $mid, $member_pwd, $user_code)
        {
            return $this->updateMember($link, $mid, "", "", "", "", "", ""
                                     , -1, "", $member_pwd, $user_code, "", true);
        }
        // 通用資料庫函式 - 下單後計算該會員目前總點數
        public function updateTotalPointAfterOrder($link, $mid, $bonus)
        {
            global $g_db_table;
            $table = $g_db_table['datamember'];
            
            $sql="update $table set member_totalpoints=member_totalpoints+$bonus,member_updated_at=NOW() where mid=$mid";
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 下單後更新會員資訊中點數總和
        public function updateAvalibleMemberBonuspoint($link, $mid, $bonus_point)
        {
            global $g_db_table;
            $table = $g_db_table['datamember'];
            
            $sql="update $table set member_usingpoints=member_usingpoints+$bonus_point,member_updated_at=NOW() where mid=$mid";
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 檢查會員是否已為該店會員!
        public function isMemberCardInStore($link, $member_id, $sid)
        {
            $sql = "SELECT * FROM membercard where membercard_trash=0 ";
            $sql .= merge_sql_string_if_not_empty("member_id", $member_id);
            $sql .= merge_sql_string_if_not_empty("store_id", $sid);
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // ui - call by main.php
        public function getMemberCard($link, $store_id, $membercard_trash=-1, $select_str="*")
        {
            $sql = "SELECT $select_str FROM membercard WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("store_id", $store_id);
            $sql.= merge_sql_string_if_not_empty("membercard_trash", $membercard_trash, "=", true);
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得店家有的會員資料
        public function getMemberCardInStore($link, $mid, $member_sid, $order_startdate, $order_enddate, $member_trash=-1, $membercard_trash=-1
                                           , $select_str="a.store_id,a.member_id as mid,a.member_date,c.member_name"
                                           , $join_str=" INNER JOIN (SELECT * FROM store) as b ON a.store_id = b.sid INNER JOIN (SELECT * FROM member) as c ON a.member_id = c.mid"
                                           , $sort_str="a.member_date DESC")
        {
            $sql = "SELECT $select_str FROM membercard AS a $join_str WHERE 1=1 ";
            if (!empty($order_startdate)) $sql .= merge_sql_string_if_not_empty("a.member_date"       , $order_startdate." 00:00:00", ">=", false);
            if (!empty($order_enddate)  ) $sql .= merge_sql_string_if_not_empty("a.member_date"       , $order_enddate." 23:59:59"  , "<=", false);
            $sql .= merge_sql_string_if_not_empty(" c.member_trash"     , $member_trash     , "=", true);
            $sql .= merge_sql_string_if_not_empty("a.membercard_trash"  , $membercard_trash , "=", true);
            $sql .= merge_sql_string_if_not_empty("b.sid", $member_sid);
            $sql .= merge_sql_string_if_not_empty("a.member_id", $mid);
            $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 檢查會員是否已為該店會員!
        public function getCoupon4MemberCardInStore($link, $mid, $sid)
        {
            $result = null;
            $sql = "SELECT pid, mid, coupon_no, using_flag, using_date, coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture from mycoupon where pid>0 ";					
            $sql .= merge_sql_string_if_not_empty("mid", $mid);
            $sql .= merge_sql_string_if_not_empty("coupon_storeid", $sid);
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 -加入會員至某店會員卡!
        public function addStoreMemberCard($link, $mid, $sid)
        {
            $sql="INSERT INTO membercard (store_id, member_id, member_date, card_type,membercard_status) VALUES ($sid, $mid, NOW(),1,0);";
            mysqli_query($link,$sql) or die(mysqli_error($link));
		    return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 加入店家會員禮
        public function getCoupon4InsertPresent($link, $mid, $sid)
        {
            $sql = "SELECT * FROM coupon where coupon_trash=0 and coupon_type=3 and coupon_storeid='".$sid."' and coupon_enddate > NOW()";
			if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 加入店家會員禮
        public function addGiftStoreMemberCard($link, $mid, $sid)
        {
            $ret_code = false;
            $sql="SELECT * FROM coupon where coupon_trash=0 and coupon_type=3 and coupon_status=1 and coupon_storeid='$sid' and coupon_number>0 ";
            if ($result = mysqli_query($link, $sql)) {
                if (mysqli_num_rows($result) > 0) {
                    // coupon_id 取得 ; 發券
                    while ($row = mysqli_fetch_array($result)) {
                        //$coupon_id2 = $row6['coupon_id'];
                        $cid = $row['cid'];
                        $coupon_number_1 = $row['coupon_number']-1;
                        // 店家會員禮
                        $coupon_no = uniqid();
                        $sql ="INSERT INTO mycoupon (mid, coupon_no, cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture) ";
                        $sql.=" select $mid,'$coupon_no',cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture";
                        $sql.=" from coupon where cid = '".$cid."'";

                        mysqli_query($link,$sql) or die(mysqli_error($link));

                        $sql = "update coupon set coupon_number=$coupon_number_1 where cid='$cid'";
                        mysqli_query($link,$sql) or die(mysqli_error($link));
                        $ret_code = true;
                    }
                }
            }
            return $ret_code;
        }
        // 通用資料庫函式 - 取得會員卡擁有的優惠
        public function getCouponByMemberCard($link, $mid, $sid)
        {
            $sql ="SELECT pid, mid, coupon_no, using_flag, using_date, coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture";
            $sql.=" from mycoupon where pid>0 and coupon_type=3 ";
            $sql.= merge_sql_string_if_not_empty("mid", $mid);
            $sql.= merge_sql_string_if_not_empty("coupon_storeid", $sid);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - getCouponByCouponNo從優惠碼取得優惠 call by get_coupon.php
        public function getCouponByCouponNo($link, $coupon_no, $using_flag=0, $pid=-1, $mycoupon_trash=0, $select_str="coupon_no,coupon_storeid")
        {
            $sql = "select $select_str from mycoupon where 1=1";
            $sql .= merge_sql_string_if_not_empty("mycoupon_trash", $mycoupon_trash, "=", true);
            $sql .= merge_sql_string_if_not_empty("using_flag", $using_flag, "=", true);
            $sql .= merge_sql_string_if_not_empty("pid", $pid, ">", true);
            $sql.= merge_sql_string_if_not_empty("coupon_no", $coupon_no);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - getCoupon從優惠碼取得優惠 call by mycoupon_list.php
        public function getCoupon($link, $sid, $mid, $using_flag=0, $pid_min=-1, $pid_pass=-1, $table_as="", $couponid="", $mycoupon_trash=-1
                                , $select_str="pid,mid, coupon_no, using_flag, using_date, coupon_id ,coupon_name, coupon_type, coupon_description, DATE_FORMAT(coupon_startdate, '%Y-%m-%d') as coupon_startdate,DATE_FORMAT(coupon_enddate, '%Y-%m-%d') as coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture"
                                , $join_str=""
                                , $sort_str="mid,using_flag,coupon_no,using_date DESC")
        {
            $sql = "SELECT $select_str FROM mycoupon $table_as ";
            $sql.= $join_str;
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("mycoupon_trash", $mycoupon_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("using_flag", $using_flag, "=", true);
            $sql.= merge_sql_string_if_not_empty("pid", $pid_min, ">", true);
            $sql.= merge_sql_string_if_not_empty("pid", $pid_pass, "<>", true);
            $sql.= merge_sql_string_if_not_empty("mid", $mid);
            $sql.= merge_sql_string_if_not_empty("coupon_id", $couponid);
            $sql.= merge_sql_string_if_not_empty("coupon_storeid", $sid);
            $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - getCoupon從優惠碼取得優惠 call by store_coupon_usestate.php
        public function getCouponExtra($link, $member_sid, $coupon_trash=-1, $table_as=""
                                , $mid="", $pid=-1, $coupon_type=-1, $store_trash=-1, $mycoupon_trash=-1
                                , $group_str="a.cid"
                                , $select_str="a.cid,b.coupon_name,a.coupon_picture"
                                , $join_str="INNER JOIN (SELECT * FROM coupon) AS b on b.cid = a.cid  INNER JOIN (SELECT * FROM store) AS c on c.sid = a.coupon_storeid"
                                , $sort_str="")
        {
            $sql = "SELECT $select_str FROM mycoupon $table_as ";
            $sql.= $join_str;
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("c.sid", $member_sid);
            $sql.= merge_sql_string_if_not_empty("b.coupon_trash", $coupon_trash, "=", true);
            
            $sql.= merge_sql_string_if_not_empty("a.mycoupon_trash", $mycoupon_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.mid", $mid);
            $sql.= merge_sql_string_if_not_empty("c.store_trashh", $store_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.pid", $pid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.coupon_type", $coupon_type, "<>", true);

            $sql .= (!empty($group_str)) ? " GROUP BY ".$group_str : "";
            $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - getCoupon從優惠碼取得優惠 call by mycoupon_list.php
        public function getCoupon4Register($link, $coupon_trash=-1, $coupon_type=-1, $coupon_enddate="")
        {
            $sql = "SELECT * FROM coupon";
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("coupon_trash", $coupon_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("coupon_type", $coupon_type, "=", true);
            $sql.= merge_sql_string_if_not_empty("coupon_enddate", $coupon_enddate);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 從優惠碼取得優惠
        public function getCouponByCouponId($link, $coupon_id, $mid=-1, $mycoupon_trash=-1, $coupon_type=-1)
        {
            $sql = "SELECT * FROM mycoupon WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("mycoupon_trash", $mycoupon_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("coupon_type", $coupon_type, "=", true);
            $sql.= merge_sql_string_if_not_empty("coupon_id", $coupon_id);
            $sql.= merge_sql_string_if_not_empty("mid", $mid, "=", true);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 下單後扣除優惠
        public function updateCouponByCouponNo($link, $coupon_no)
        {
            $sql="update mycoupon set using_flag=1, using_date=NOW() where using_flag=0 and coupon_no='$coupon_no'";
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 取得會員卡擁有的優惠
        public function getCouponList($link, $mid, $sid, $coupon_type, $sort_str="coupon_type,coupon_startdate")
        {
            $sql = "SELECT a.cid,a.coupon_id,a.coupon_name, a.coupon_type, a.coupon_description,
                    DATE_FORMAT(a.coupon_issue_startdate, '%Y-%m-%d') as coupon_issue_startdate,
                    DATE_FORMAT(a.coupon_issue_enddate, '%Y-%m-%d') as coupon_issue_enddate,
                    DATE_FORMAT(a.coupon_startdate, '%Y-%m-%d') as coupon_startdate,
                    DATE_FORMAT(a.coupon_enddate, '%Y-%m-%d') as coupon_enddate,
                    a.coupon_status,a.coupon_rule,a.coupon_discount,a.discount_amount,
                    a.coupon_storeid,coupon_for,a.coupon_picture
                    FROM coupon as a
                    
                    left join (select * from membercard) as b on a.coupon_storeid=b.store_id
                    where a.coupon_trash=0 and a.coupon_status=1
                    and a.coupon_issue_enddate >= '".date("Y-m-d")."'";
            $sql.=" and a.coupon_issue_startdate <= '".date("Y-m-d")."'";
            $sql.=" and a.coupon_enddate >= '".date("Y-m-d")."'";
            $sql.=" and a.coupon_id not in (SELECT coupon_id from mycoupon where mid=$mid) ";
            $sql.=" and a.coupon_number > 0";
            $sql.= merge_sql_string_if_not_empty("b.member_id", $mid);
            $sql.= merge_sql_string_if_not_empty("a.coupon_storeid", $sid, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.coupon_type", $coupon_type, "=", true, 1);
            $sql.=" UNION ALL ";
            // 平台優惠券
            $sql.="SELECT a.cid,a.coupon_id,a.coupon_name, a.coupon_type, a.coupon_description,
                    DATE_FORMAT(a.coupon_issue_startdate, '%Y-%m-%d') as coupon_issue_startdate,
                    DATE_FORMAT(a.coupon_issue_enddate, '%Y-%m-%d') as coupon_issue_enddate,
                    DATE_FORMAT(a.coupon_startdate, '%Y-%m-%d') as coupon_startdate,
                    DATE_FORMAT(a.coupon_enddate, '%Y-%m-%d') as coupon_enddate,
                    a.coupon_status,a.coupon_rule,a.coupon_discount,a.discount_amount,
                    a.coupon_storeid,coupon_for,a.coupon_picture
                    FROM coupon as a";
            $sql.=" where a.coupon_trash=0 and a.coupon_status=1";
            $sql.= merge_sql_string_if_not_empty("a.coupon_issue_enddate", date("Y-m-d"), ">=");
            $sql.= merge_sql_string_if_not_empty("a.coupon_issue_startdate", date("Y-m-d"), "<=");
            $sql.= merge_sql_string_if_not_empty("a.coupon_enddate", date("Y-m-d"), ">=");
            $sql.=" and a.coupon_id not in (SELECT coupon_id from mycoupon where mid=$mid) ";
            $sql.=" and a.coupon_number > 0";
            $sql.=" and a.coupon_type = 4";
            $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - insert Coupon call by coupon_add.php
        public function insertCoupon($link, $coupon_id, $coupon_name, $coupon_type, $coupon_description, $coupon_startdate, $coupon_enddate, $coupon_status, $coupon_rule, $coupon_discount, $discount_amount, $coupon_storeid, $coupon_for, $mid)
        {
            $sql = "INSERT INTO coupon (coupon_id,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_created_at, coupon_created_by) VALUES";
			$sql.="('$coupon_id', '$coupon_name', $coupon_type, '$coupon_description', '$coupon_startdate', '$coupon_enddate', $coupon_status, $coupon_rule, $coupon_discount, $discount_amount, '$coupon_storeid', $coupon_for, NOW(), $mid);";

			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - insert Coupon call by get_coupon.php
        public function copyCoupon($link, $mid, $coupon_no, $coupon_id)
        {
            $sql = "INSERT INTO mycoupon (mid, coupon_no, cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture) ";
			$sql.= " select $mid,'$coupon_no',cid,coupon_id ,coupon_name, coupon_type, coupon_description, coupon_startdate, coupon_enddate, coupon_status, coupon_rule, coupon_discount, discount_amount, coupon_storeid, coupon_for, coupon_picture from coupon where coupon_id = '".$coupon_id."'";
			
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - insert Coupon call by get_coupon.php
        public function updateCouponNumberByCouponId($link, $coupon_id, $coupon_number)
        {
			$sql = "UPDATE coupon SET ";
            $sql.= merge_sql_string_set_value("coupon_number", $coupon_number, "=", false, true);
            $sql.= "WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("coupon_id", $coupon_id);

			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 更新coupon trash status call by coupon_delete.php
        public function updateCouponTrashStatus($link, $mid, $coupon_id, $trash_status)
        {
            $sql = "UPDATE coupon SET ";
            $sql.= merge_sql_string_set_value("coupon_trash", $trash_status, "=", true, true);
            $sql.= ",coupon_updated_at=NOW(),";
            $sql.= merge_sql_string_set_value("coupon_updated_by", $mid);
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("cid", $coupon_id);

			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 更新coupon info call by coupon_edit.php
        public function updateCouponData($link, $mid, $coupon_id, $coupon_name, $coupon_type, $coupon_description
                                                    , $coupon_startdate, $coupon_enddate, $coupon_status, $coupon_rule
                                                    , $coupon_discount , $discount_amount, $coupon_storeid, $coupon_for)
        {
            $sql = "UPDATE coupon SET ";
            $sql.= "coupon_updated_at=NOW()";
            $sql.= merge_sql_string_set_value("coupon_name"         , $coupon_name);
            $sql.= merge_sql_string_set_value("coupon_type"         , $coupon_type          , "=", true );
            $sql.= merge_sql_string_set_value("coupon_description"  , $coupon_description               );
            $sql.= merge_sql_string_set_value("coupon_startdate"    , $coupon_startdate                 );
            $sql.= merge_sql_string_set_value("coupon_enddate"      , $coupon_enddate       , "=", true );
            $sql.= merge_sql_string_set_value("coupon_status"       , $coupon_status        , "=", true );
            $sql.= merge_sql_string_set_value("coupon_rule"         , $coupon_rule          , "=", true );
            $sql.= merge_sql_string_set_value("coupon_discount"     , $coupon_discount      , "=", true );
            $sql.= merge_sql_string_set_value("discount_amount"     , $discount_amount      , "=", true );
            $sql.= merge_sql_string_set_value("coupon_storeid"      , $coupon_storeid                   );
            $sql.= merge_sql_string_set_value("coupon_for"          , $coupon_for           , "=", true );
            $sql.= merge_sql_string_set_value("coupon_updated_by", $mid);
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("cid", $coupon_id);

			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 下單後扣除點數
        public function modifyBonuspoint($link, $mid, $order_no, $bonus_point)
        {
            $sql="INSERT INTO mybonus (member_id,order_no,bonus_date,bonus_type,bonus,bonus_created_at) VALUES ";
            $sql.=" ($mid,'$order_no',NOW(),2,$bonus_point,NOW());";

            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 建立訂單
        public function addOrder($link, $order_no, $membersid, $tour_guide, $mid, $order_amount, $coupon_no, $discount_amount, $pay_type, $order_pay, $bonus_point)
        {
            $sql="INSERT INTO orderinfo (order_no,order_date,store_id,tour_guide,member_id,order_amount,coupon_no,discount_amount,pay_type,order_pay,pay_status,bonus_point,order_status) VALUES ";
            $sql=$sql." ('$order_no',NOW(),$membersid,$tour_guide,$mid,$order_amount,'$coupon_no',$discount_amount,$pay_type,$order_pay,1,$bonus_point,1);";
            
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 取得訂單資訊
        public function getOrderInfo($link, $order_no
                                    , $select_str="a.*,b.bonus_mode,c.*"
                                    , $join_str="INNER JOIN (SELECT store_id, bid,bonus_mode FROM bonus_store) AS b ON a.store_id= b.store_id  INNER JOIN (SELECT * FROM bonus_setting) AS c ON b.bid= c.bid"
                                    , $where_str="c.bonus_status = 0"
                                    , $sort_str=""
                                    , $member_sid="", $mid="", $order_startdate="", $order_enddate="", $store_sid="")
        {
            $sql ="SELECT $select_str FROM orderinfo a $join_str WHERE 1=1";
            $sql.= ($where_str) ? " AND $where_str" : "";
                 
            $sql.= merge_sql_string_if_not_empty("a.order_no", $order_no);

            $sql.= merge_sql_string_if_not_empty("b.sid", $member_sid);
            $sql.= merge_sql_string_if_not_empty("a.member_id", $mid);
            $sql.= merge_sql_string_if_not_empty("a.store_id", $store_sid);
            if (!empty($order_startdate)) $sql .= merge_sql_string_if_not_empty("a.order_date", $order_startdate." 00:00:00", ">=", false);
            if (!empty($order_enddate)  ) $sql .= merge_sql_string_if_not_empty("a.order_date", $order_enddate." 23:59:59"  , "<=", false);
            
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得訂單資訊 for bonus_deadline.php
        public function getBonusEndDateFromOrderInfo($link, $mid)
        {
            $sql = "SELECT bonus_end_date FROM `orderinfo` ";
            $sql.= " WHERE bonus_end_date IS NOT NULL AND bonus_end_date > NOW() and member_id=$mid";
            $sql.= " GROUP BY bonus_end_date";
            $sql.= " ORDER BY bonus_end_date ASC";
            $sql.= " LIMIT 1";
            $result = mysqli_query($link, $sql);
            return mysqli_fetch_array($result);
        }
        // 通用資料庫函式 - 取得訂單資訊 for bonus_deadline.php
        // Bonuspoint => Bpt
        public function getEfficientBptFromOrderInfo($link, $mid)
        {
            $sql = "SELECT member_id, SUM(bonus_point) as bonusPoint FROM `orderinfo`";
            $sql.= " WHERE order_status=1 and pay_status=1 and bonus_end_date >= NOW()";
            $sql.= merge_sql_string_if_not_empty("member_id", $mid);
            $result = mysqli_query($link, $sql);
            return mysqli_fetch_array($result);
        }
        // 通用資料庫函式 - 取得訂單資訊 for bonus_deadline.php
        // Bonuspoint => Bpt
        // Bonuspoint => BroundsEnddate
        public function getBptFromOrderInfoWhereBedate($link, $mid, $bonusPoint, $bonusEndDate)
        {
            $sql = "SELECT bonus_end_date,SUM(bonus_get)-$bonusPoint as totalBonus FROM `orderinfo` ";
            $sql.= " WHERE DATE(bonus_end_date)=DATE('".$bonusEndDate."') and member_id=$mid";
            $sql.= " GROUP BY bonus_end_date";
            $sql.= merge_sql_string_if_not_empty("member_id", $mid);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得訂單資訊 for bonuswillget.php
        // Bonuspoint => Bpt
        // Bonuspoint => Bedate
        public function getWillGetBptFromOrderInfo($link, $mid)
        {
            $cur_date = new DateTime(date("Y-m-d"));
            $sql = "SELECT sum(bonus_get) as bonuswillget FROM orderinfo where order_status=1 and pay_status=1 ";
            $sql.= " and bonus_date > '".$cur_date->format('Y-m-d')." 23:59:59'";
            $sql.= merge_sql_string_if_not_empty("member_id", $mid);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得訂單資訊 for member_info1.php
        // Bonuspoint => Bpt
        public function getBptFromOrderInfoAfterBdate($link, $mid, $bonusDate, $order_status=-1, $pay_status=-1)
        {
            $sql = "SELECT sum(bonus_get) as bonuswillget FROM `orderinfo` ";
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("member_id"    , $mid);
            $sql.= merge_sql_string_if_not_empty("order_status" , $order_status , "=", true);
            $sql.= merge_sql_string_if_not_empty("pay_status"   , $pay_status   , "=", true);
            $sql.= merge_sql_string_if_not_empty("bonus_date"   , $bonusDate    , ">", false);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 取得訂單資訊 for member_info1.php
        // Bonuspoint => Bpt
        public function getTotalBptFromOrderInfoAfterBdate($link, $mid, $bonus_end_date = "", $order_status=-1, $pay_status=-1)
        {
            $sql = "SELECT member_id, SUM(bonus_get)-SUM(bonus_point) as totalBonus FROM `orderinfo` ";
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("member_id"    , $mid);
            $sql.= merge_sql_string_if_not_empty("order_status" , $order_status , "=", true);
            $sql.= merge_sql_string_if_not_empty("pay_status"   , $pay_status   , "=", true);
            $sql.= merge_sql_string_if_not_empty("bonus_end_date", $bonus_end_date, ">=", false);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 分潤共享資訊 call by profit_info.php
        public function getProfit($link, $membersid, $profit_startdate, $profit_enddate, $pid=-1, $profit_trash=-1
                                , $select_str=" a.pid,a.store_id, a.profit_month,a.start_date, a.end_date, a.total_amount,a.total_order,a.total_amountD,a.total_amountG,a.total_amountI,a.total_amountJ,a.profit_pdf,a.billing_date,a.billing_flag,a.pay_date "
        )
        {
            $sql = "SELECT $select_str FROM profit a ";
            $sql.= " where 1=1 ";
            $sql.= merge_sql_string_if_not_empty("a.pid" , $pid , ">", true);
            $sql.= merge_sql_string_if_not_empty("a.profit_trash" , $profit_trash , "=", true);
            $sql.= merge_sql_string_if_not_empty("a.store_id"     , $membersid    , "=", true);
            $sql.= merge_sql_string_if_not_empty("a.profit_month" , getFirstDateOfMonth($profit_startdate), ">=", false);
            $sql.= merge_sql_string_if_not_empty("a.profit_month" , getLastDateOfMonth($profit_startdate) , "<=", false);

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 建立分潤共享資訊
        public function addProfitShare($link, $order_no, $order_amount, $order_pay, $membersid, $bid,
                                       $bonus_name1, $sys_rate1, $marketing_rate1,
                                       $bonus_name2, $sys_rate2, $marketing_rate2,
                                       $bonus_mode, $user_rate, $event_rate,
                                       $group_mode, $groupmode_rate,
                                       $hotel_mode, $hotelmode_rate, $store_service)
        {								
            $sql ="INSERT INTO `profit_share` (order_no,order_amount,order_pay,store_id,bid,bonus_name1,sys_rate1,marketing_rate1,bonus_name2,sys_rate2,marketing_rate2,bonus_mode,user_rate,event_rate,group_mode,groupmode_rate,hotel_mode,hotelmode_rate,store_service,profit_date,profit_status) VALUES ";
			$sql.=" ('$order_no',$order_amount,$order_pay,$membersid,$bid,'$bonus_name1',$sys_rate1,$marketing_rate1,'$bonus_name2','$sys_rate2','$marketing_rate2',$bonus_mode,$user_rate,$event_rate,'$group_mode',$groupmode_rate,'$hotel_mode',$hotelmode_rate,'$store_service',NOW(),0);";
			
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 下單後發放點數優惠給會員
        public function addBonusPointAfterOrder($link, $order_no, $mid, $bonus, $two_week = false)
        {
            $sql="INSERT INTO mybonus (member_id,order_no,bonus_date,bonus_type,bonus,bonus_created_at) VALUES ";
            if ($two_week) {
                $date_twoweeklater = date('Y-m-d', strtotime('+14 day'));
                $sql.=" ($mid,'$order_no',".date('Y-m-d', $date_twoweeklater)." 00:04:00',1,$bonus,NOW());";
            } else {
                $sql.=" ($mid,'$order_no',NOW(),1,$bonus,NOW());";
            }
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - 依起迄日期時間取得會員點數
        public function getBonusPointDuringDate($link, $mid, $bonus_startdate, $bonus_enddate, $group_str="", $sort_str="")
        {
            $sql = "SELECT a.bid,a.member_id, a.order_no, a.bonus_date, a.bonus_type ,a.bonus, c.store_name from mybonus as a";
            $sql = $sql." inner join ( select order_no, store_id from orderinfo ) as b on a.order_no=b.order_no ";
            $sql = $sql." inner join ( select sid, store_name from store ) as c on c.sid=b.store_id ";
            $sql = $sql." where a.bid>0  ";
            
            $sql.= merge_sql_string_if_not_empty("a.member_id", $mid);
            $sql.= merge_sql_string_if_not_empty("a.bonus_date", $bonus_startdate, ">=");
            $sql.= merge_sql_string_if_not_empty("a.bonus_date", $bonus_enddate, "<=");
		    $sql .= ($group_str != "") ? " GROUP BY '".$group_str."'" : "";
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - 下單後更新優惠到期日
        public function updateCouponExpiredDate($link, $order_no, $mid, $user_rate, $bonus, $two_week = false)
        {
            if ($two_week) {
                $date_twoweeklater = date('Y-m-d',strtotime('+14 day'));
                // $month = date('m');
                if (date('m', $date_twoweeklater) >= 1 && date('m', $date_twoweeklater) <= 6){
                    $sql="update orderinfo set urate=$user_rate,bonus_get=$bonus,bonus_date='".date('Y-m-d', $date_twoweeklater)." 00:04:00',bonus_end_date= '".date("Y",strtotime("+0 year",strtotime($date_twoweeklater)))."-12-31 23:59:59' where order_no=$order_no";
                } else {
                    $sql="update orderinfo set urate=$user_rate,bonus_get=$bonus,bonus_date='".date('Y-m-d', $date_twoweeklater)." 00:04:00',bonus_end_date= '".date("Y",strtotime("+1 year",strtotime($date_twoweeklater)))."-06-30 23:59:59' where order_no=$order_no";
                }
            } else {
                $month = date('m');
                if ($month >= 1 && $month <= 6){
                    $sql="update orderinfo set urate=$user_rate,bonus_get=$bonus,bonus_date=NOW(),bonus_end_date= CONCAT(EXTRACT(YEAR FROM NOW()),'-12-31 23:59:59') where order_no=$order_no";
                } else {
                    $sql="update orderinfo set urate=$user_rate,bonus_get=$bonus,bonus_date=NOW(),bonus_end_date= CONCAT(EXTRACT(YEAR FROM NOW())+1,'-06-30 23:59:59') where order_no=$order_no";
                }
            }
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - applycoupon_list
        public function addApplyCouponList($link, $membersid_couponstoreid, $mid, $membername, $coupon_no)
        {
            $sql="INSERT INTO applycoupon_list (store_id,member_id,member_name,apply_date,coupon_no,coupon_name,applycoupon_status) ";
            $sql=$sql." select $membersid_couponstoreid,$mid,'$membername',NOW(),coupon_no,coupon_name,0 from mycoupon where using_flag=0 and coupon_no='$coupon_no' ;";
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - arlist
        public function getArList($link, $aid, $shopping_area, $sort_str="")
        {
            $sql = "SELECT `aid`, `ar_name`, `shopping_area`, `ar_address`, `ar_descript`,"
                  ." `ar_picture`, `ar_name2`, `ar_descript2`, `ar_picture2`, `ar_latitude`,"
                  ." `ar_longitude`, `coupon_id`, `ar_status`"
                  ." FROM arlist where ar_trash=0 ";
            $sql.= merge_sql_string_if_not_empty("aid", $aid);
            $sql.= merge_sql_string_if_not_empty("shopping_area", $shopping_area);
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - arstore
        public function getArStore($link, $aid, $qid, $sort_str="")
        {
            $sql = "SELECT `rid`, `qid`, `aid`, `store_name`, `store_address` FROM arstore where store_trash=0 ";
            $sql.= merge_sql_string_if_not_empty("qid", $qid);
            $sql.= merge_sql_string_if_not_empty("aid", $aid);
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - attraction
        public function getAttraction($link, $shopping_area, $store_type, $sort_str="")
        {
            $sql = "SELECT a.sid, a.store_id, a.store_type, a.store_name, a.shopping_area, a.store_phone, a.store_address, a.store_website,
                    a.store_facebook,a.store_news,a.store_picture, a.store_latitude, a.store_longitude, a.store_status, a.store_opentime, a.store_descript
                    FROM attraction as a where a.store_trash=0 ";
            $sql.= merge_sql_string_if_not_empty("a.shopping_area", $shopping_area);
            $sql.= merge_sql_string_if_not_empty("a.store_type", $store_type);
            $sql.= " and a.store_status=0";
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - banner
        public function getBannerAvalibleData($link, $banner_trash=-1
                                            , $where_str=" AND date(banner_date) <= date(NOW()) AND date(banner_enddate) >= date(NOW()) "
                                            , $select_str="bid,banner_subject, banner_date, banner_enddate, banner_descript,banner_picture,banner_link"
                                            , $sort_str="banner_date DESC")
        {
            $sql = "SELECT $select_str FROM banner where 1=1";
            $sql.= merge_sql_string_if_not_empty("banner_trash", $banner_trash, "=", true);
            $sql.= $where_str;
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - blacklist
        public function getBlacklistData($link, $membersid, $m_id, $blacklist_trash=-1, $blacklist_status=-1, $sid=-1, $bid=-1)
        {
            
						// $sql2 = "SELECT * FROM blacklist ";
			
						// $sql2 = $sql2." where bid > 0 and blacklist_trash=0 ";
						// if ($mid != "") {	
						// 	$sql2 = $sql2." and member_id=".$mid."";
						// }
						// if ($sid != "") {	
						// 	$sql2 = $sql2." and store_id=".$sid."";
						// }

            $sql = "SELECT * FROM blacklist where 1=1 ";
            $sql.= merge_sql_string_if_not_empty("blacklist_trash" , $blacklist_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("blacklist_status", $blacklist_status, "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id", $membersid);
            $sql.= merge_sql_string_if_not_empty("member_id", $m_id);
            $sql.= merge_sql_string_if_not_empty("store_id", $sid, "=", true);
            $sql.= merge_sql_string_if_not_empty("bid", $bid, ">", true);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - blacklist
        public function addBlacklistData($link, $membersid, $m_id, $member_name, $reason)
        {
            $sql ="INSERT INTO blacklist (store_id,member_id,member_name,reason,blacklist_date,blacklist_status,blacklist_created_at) VALUES";
			$sql.="($membersid, $m_id, '$member_name', '$reason',NOW(), 0, NOW());";
			mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - blacklist
        public function updateBlacklistData($link, $membersid, $m_id)
        {
            $sql="update blacklist set blacklist_status=1 ,blacklist_updated_at=NOW()
                    where store_id=$membersid and member_id=$m_id and blacklist_status=0 ;";
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - blacklist
        public function getBlacklist4Show($link, $membersid, $m_id, $sort_str="")
        {
            $sql = "SELECT a.bid,a.store_id,a.member_id, a.member_name, a.reason, a.blacklist_date,a.blacklist_status,b.member_id as m_id,b.member_name as m_name FROM blacklist a 
					 inner join ( select mid,member_id,member_name,member_trash from member) as b ON a.member_id= b.mid 
					 where a.bid>0 and a.blacklist_trash=0 and b.member_trash=0 ";
            $sql.= merge_sql_string_if_not_empty("b.mid", $m_id);
            $sql.= merge_sql_string_if_not_empty("a.store_id", $membersid);
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - reserveinfo
        public function getReserveInfo($link, $member_id, $booking_no="", $rid=-1, $reserve_trash=-1, $hid=-1, $reserve_date=""
                                     , $select_str="rid,booking_no,store_id,hid, reserve_date, reserve_time, mid,member_id, member_name, service_item, reserve_status, reserve_created_at"
                                     , $sort_str="")
        {
            $sql = "SELECT $select_str FROM reserveinfo where 1=1";
            $sql.= merge_sql_string_if_not_empty("rid", $rid, ">", true);
            $sql.= merge_sql_string_if_not_empty("reserve_trash", $reserve_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("member_id", $member_id);
            $sql.= merge_sql_string_if_not_empty("booking_no", $booking_no);
            $sql.= merge_sql_string_if_not_empty("reserve_date", $reserve_date);
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - reserveinfo call by booking_info.php
        public function getReserveInfoByBookingNo($link, $booking_no, $rid=-1, $reserve_trash=-1
                                                , $membersid="", $reserve_status=-1
                                                , $booking_startdate="", $booking_enddate=""
                                                , $select_str="a.rid,a.booking_no,a.store_id,a.hid, a.reserve_date, a.reserve_time, a.mid,a.member_id, a.member_name, a.member_email, a.service_item, a.reserve_remark, a.reserve_status, a.reserve_created_at, c.nick_name"
                                                , $join_str="LEFT JOIN (SELECT hid,nick_name,stylist_pic,hairstylist_status FROM hairstylist WHERE hairstylist_trash = 0 ) AS c ON a.hid= c.hid "
                                                , $sort_str="")
        {
            $sql = "SELECT $select_str FROM reserveinfo a $join_str WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("a.booking_no", $booking_no);
            $sql.= merge_sql_string_if_not_empty("a.store_id"  , $membersid );
            $sql.= merge_sql_string_if_not_empty("a.rid"            , $rid                          , ">" , true );
            $sql.= merge_sql_string_if_not_empty("a.reserve_trash"  , $reserve_trash                , "=" , true );
            $sql.= merge_sql_string_if_not_empty("a.reserve_status" , $reserve_status               , "<=", true );
            if (!empty($booking_startdate)) $sql.= merge_sql_string_if_not_empty("a.reserve_date"   , $booking_startdate." 00:00:00", ">=", false);
            if (!empty($booking_enddate)  ) $sql.= merge_sql_string_if_not_empty("a.reserve_date"   , $booking_enddate." 23:59:59"  , "<=", false);
            
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - reserveinfo
        public function getReserveInfoList4Show($link, $mid, $sid, $booking_startdate, $booking_enddate, $sort_str="")
        {
            $sql = "SELECT a.rid,a.booking_no,a.store_id,a.hid, a.reserve_date, a.reserve_time, a.mid,a.member_id, a.member_name,a.member_email, a.service_item,
                      a.reserve_remark, a.reserve_status, a.reserve_created_at,b.store_name,b.store_picture,c.nick_name from reserveinfo a 
					  inner join ( select sid,store_name,store_picture,store_status from store where store_trash = 0 ) as b ON b.sid= a.store_id
					  left join ( select hid,nick_name,stylist_pic,hairstylist_status from hairstylist where hairstylist_trash = 0 ) as c ON a.hid= c.hid 
                      where a.rid>0 and a.reserve_trash=0 ";
            $sql.= merge_sql_string_if_not_empty("b.mid", $mid);
            $sql.= merge_sql_string_if_not_empty("a.store_id", $sid);
            if (!empty($booking_startdate)) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_startdate." 00:00:00", ">=");
            if (!empty($booking_enddate)  ) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_enddate." 23:59:59", "<=");
            
		    $sql .= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - reserveinfo
        public function updateReserveInfo($link, $rid, $reserve_status=2, $reserve_date="", $reserve_time="", $reserve_remark="")
        {
            $sql = "update reserveinfo set reserve_status=".$reserve_status;
            $sql.= merge_sql_string_set_value("reserve_date"  , $reserve_date   );
            $sql.= merge_sql_string_set_value("reserve_time"  , $reserve_time   );
            $sql.= merge_sql_string_set_value("reserve_remark", $reserve_remark );
            $sql.=" where rid=".$rid;
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - reserveinfo call by member_addorder.php
        public function updateReserveInfoExtra($link, $mid, $membersid, $booking_no, $reserve_status=1, $condiction_reserve_status=0)
        {
            $sql="UPDATE reserveinfo SET ";
            $sql.= merge_sql_string_set_value("reserve_status", $reserve_status, "=", true, true);
            $sql.=" where 1=1 ";
            $sql.= merge_sql_string_if_not_empty("booking_no", $booking_no);
            $sql.= merge_sql_string_if_not_empty("mid"           , $mid                      , "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id"      , $membersid                , "=", true);
            $sql.= merge_sql_string_if_not_empty("reserve_status", $condiction_reserve_status, "=", true);
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - verifymobile call by check_verify.php
        public function getVerifyMobile($link, $mobile_no, $rid=0)
        {
            $sql = "SELECT * FROM verifymobile where 1=1";
            $sql.= merge_sql_string_if_not_empty("rid", $rid, ">", true);
            $sql.= merge_sql_string_if_not_empty("mobile_no", $mobile_no);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - class call by class_list.php
        public function getClass($link, $sort_str="store_id,class_code,class_name asc", $cid=0, $class_trash=0, $store_trash=0)
        {
            $sql = "SELECT cid,store_id,class_code,class_name,class_descript,class_descript2,
                           class_time,class_price,class_picture,class_picture2,class_status,class_trash
                     FROM `class` as a 
                     inner join ( select sid,store_name,store_address,store_phone,store_trash from store) as b ON a.store_id=b.sid 
                     where 1=1 ";

            $sql.= merge_sql_string_if_not_empty("a.cid", $cid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.class_trash", $class_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.store_trash", $store_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - class_reserveinfo call by classbooking_cancel.php
        public function getClassReserveinfo($link, $mid, $booking_no, $rid=-1, $reserve_trash=-1, $store_trash=-1, $pid=-1, $reserve_date=""
                                          , $select_str="rid,booking_no,store_id,pid, reserve_date, reserve_time, mid,member_id, member_name, member_email,program_person, reserve_status, reserve_created_at"
                                          , $sort_str="")
        {
            $sql = "SELECT $select_str FROM class_reserveinfo where 1=1";
            $sql.= merge_sql_string_if_not_empty("rid", $rid, ">", true);
            $sql.= merge_sql_string_if_not_empty("reserve_trash", $reserve_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("booking_no", $booking_no);
            $sql.= merge_sql_string_if_not_empty("mid", $mid);
            $sql.= merge_sql_string_if_not_empty("pid", $pid, "=", true);
            $sql.= merge_sql_string_if_not_empty("reserve_date", $reserve_date);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }        
        // 通用資料庫函式 - class_reserveinfo call by classbooking_info.php
        public function getClassReserveinfoExtra($link, $booking_no, $rid=-1, $reserve_trash=-1
                                               , $membersid="", $reserve_status=-1
                                               , $booking_startdate="", $booking_enddate=""
                                               , $sort_str="")
        {
            $sql = "SELECT a.rid,a.booking_no,a.store_id,a.pid, a.reserve_date, a.reserve_time, a.mid,a.member_id,
                           a.member_name, a.member_email, a.program_person,
                           a.reserve_note, a.reserve_remark, a.reserve_status, a.reserve_created_at,
                           b.store_name,b.store_address,b.store_phone,b.store_picture,c.program_name,c.program_price
                           from class_reserveinfo a 
                           inner join ( select sid,store_name,store_address,store_phone,store_picture,store_status from store where store_trash = 0 ) as b ON b.sid= a.store_id 
                           inner join ( select pid,program_name,program_price,program_status from program where program_trash = 0 ) as c ON a.pid= c.pid 
                           where 1=1";
                        
            $sql.= merge_sql_string_if_not_empty("a.rid", $rid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.reserve_trash", $reserve_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.booking_no", $booking_no);
            $sql.= merge_sql_string_if_not_empty("a.store_id", $membersid);
            $sql.= merge_sql_string_if_not_empty("a.reserve_status", $reserve_status, "<=", true);
            if (!empty($booking_startdate)) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_startdate." 00:00:00", ">=");
            if (!empty($booking_enddate)  ) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_enddate." 23:59:59", "<=");
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - class_reserveinfo call by classbooking_list.php
        public function getClassReserveinfoDuringDate($link, $booking_startdate, $booking_enddate, $mid, $sid, $rid=0, $reserve_trash=0, $sort_str="a.store_id asc,a.reserve_date,a.reserve_time")
        {
            $sql = "SELECT a.rid,a.booking_no,a.store_id,a.pid, a.reserve_date, a.reserve_time, a.mid,a.member_id,
                           a.member_name, a.member_email, a.program_person,
                           a.reserve_note, a.reserve_remark, a.reserve_status, a.reserve_created_at,
                           b.store_name,b.store_address,b.store_phone,b.store_picture,c.program_name,c.program_price
                           from class_reserveinfo a 
                           inner join ( select sid,store_name,store_address,store_phone,store_picture,store_status from store where store_trash = 0 ) as b ON b.sid= a.store_id 
                           inner join ( select pid,program_name,program_price,program_status from program where program_trash = 0 ) as c ON a.pid= c.pid 
                           where 1=1";
                        
            $sql.= merge_sql_string_if_not_empty("a.rid", $rid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.reserve_trash", $reserve_trash, "=", true);
            if (!empty($booking_startdate)) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_startdate." 00:00:00", ">=");
            if (!empty($booking_enddate)  ) $sql.= merge_sql_string_if_not_empty("a.reserve_date", $booking_enddate." 23:59:59", "<=");
            $sql.= merge_sql_string_if_not_empty("a.mid", $mid, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.sid", $sid, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - class_reserveinfo
        public function updateClassReserveInfo($link, $rid, $reserve_status=2, $program_person="0", $reserve_date="", $reserve_time="", $reserve_remark="")
        {
            $sql ="update class_reserveinfo set ";
            $sql.= merge_sql_string_set_value("reserve_status", $reserve_status, "=", true, true);
            if ($program_person != "0") $sql.= merge_sql_string_set_value("program_person", $program_person);
            $sql.= merge_sql_string_set_value("reserve_date", $reserve_date);
            $sql.= merge_sql_string_set_value("reserve_time", $reserve_time);
            $sql.= merge_sql_string_set_value("reserve_remark", $reserve_remark);
            $sql.=" where 1=1";
            $sql.= merge_sql_string_if_not_empty("rid", $rid, "=", true);
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - class_reserveinfo
        public function updateClassReserveInfoStatus($link, $mid, $store_id, $booking_no, $reserve_status=1, $condiction_reserve_status=0)
        {
            $sql ="UPDATE class_reserveinfo SET ";
            $sql.= merge_sql_string_set_value("reserve_status", $reserve_status, "=", true, true);
            $sql.=" where 1=1";
            $sql.= merge_sql_string_if_not_empty("reserve_status", $condiction_reserve_status, "=", true);
            $sql.= merge_sql_string_if_not_empty("mid"           , $mid     , "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id"      , $store_id, "=", true);
            $sql.= merge_sql_string_if_not_empty("booking_no"    , $booking_no);
            mysqli_query($link, $sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - insert notificationlog call by fcm_tomember.php
        public function insertNotificationlog($link, $member_idORmobile_no, $msg, $fcmResult, $role=-1, $title="", $body="")
        {
            $sql = "INSERT INTO notificationlog (Person_id, Role, msg";
            $sql.= (!empty($title)) ? ",title" : "";
            $sql.= (!empty($body)) ? ",body" : "";
            $sql.= ",fcmresult, updatetime) VALUES ('$member_idORmobile_no', '$role', '$msg',";
            $sql.= (!empty($title)) ? ",'$title'" : "";
            $sql.= (!empty($body)) ? ",'$body'" : "";
            $sql.= ",'$fcmResult', NOW())";
            
			mysqli_query($link, $sql);
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - festival call by festival_list.php
        public function getFestival($link, $fid=0, $festival_trash=0, $shoppingarea_trash=0)
        {
            $sql = "SELECT a.fid,b.shopping_area,a.festival_name,a.festival_descript,a.festival_time,a.festival_picture from festival as a 
                     INNER JOIN ( SELECT * FROM shopping_area) as b on b.aid = a.shopping_area  
                     where 1=1 ";

            $sql.= merge_sql_string_if_not_empty("a.fid", $fid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.festival_trash", $festival_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("b.shoppingarea_trash", $shoppingarea_trash, "=", true);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - program call by get_classworkingday.php
        public function getProgram($link, $pid, $program_trash=-1, $cid=-1
                                  , $select_str=" a.*,b.store_id "
                                  , $join_str=" INNER JOIN (SELECT cid,store_id FROM class) AS b on a.cid=b.cid "
                                  , $where_str=""
                                  , $sort_str="")
        {
            $sql = "SELECT $select_str FROM `program` as a $join_str where 1=1 ";
            $sql.= ($where_str) ? " AND $where_str" : "";

            $sql.= merge_sql_string_if_not_empty("a.pid", $pid, ">", true);
            $sql.= merge_sql_string_if_not_empty("a.program_trash", $program_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - timeperiod call by get_classworkingday.php
        public function getTimeperiod($link, $sid)
        {
            $sql = "SELECT * FROM `timeperiod` where 1=1 ";

            $sql.= merge_sql_string_if_not_empty("store_id", $sid, "=", true);
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - hairservice call by get_hairservice.php
        public function getHairservice($link, $sid, $xid=-1, $service_trash=-1, $hid=-1, $hairstylist_trash=-1, $in_service_item=""
                                     , $select_str="xid,store_id,hid,service_code,service_name,service_time,service_price,service_status"
                                     , $sort_str="service_code ASC")
        {
            $sql = "SELECT $select_str FROM `hairservice` WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("xid", $xid, ">", true);
            $sql.= merge_sql_string_if_not_empty("service_trash", $service_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("hid", $hid, ">", true);
            $sql.= merge_sql_string_if_not_empty("hairstylist_trash", $hairstylist_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id", $sid, "=", true);
			$sql.= (empty($in_service_item)) ? " AND service_code in ($in_service_item)" : "";
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - hairstylist call by hairstylist_list.php
        public function getHairstylist($link, $sid, $xid=-1, $service_trash=-1, $hid=-1, $hairstylist_trash=-1
                                     , $select_str="hid,store_id,nick_name,stylist_pic,service_code,hairstylist_date"
                                     , $sort_str="nick_name ASC")
        {
            $sql = "SELECT $select_str FROM `hairstylist` WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("xid", $xid, ">", true);
            $sql.= merge_sql_string_if_not_empty("service_trash", $service_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("hid", $hid, ">", true);
            $sql.= merge_sql_string_if_not_empty("hairstylist_trash", $hairstylist_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id", $sid, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - news call by home_info.php
        public function getNews($link, $news_trash=-1, $news_id=-1
                                     , $select_str="`nid`, `news_subject`, `news_date`, `news_picture`"
                                     , $sort_str="news_date DESC")
        {
            $sql2 = "SELECT `nid`, `news_subject`, `news_date`,`news_descript`,  `news_picture` FROM news where news_trash=0 ";
            $sql2 = $sql2." and nid = $news_id";
            $sql2 = $sql2." order by news_date desc";

            $sql = "SELECT $select_str FROM news WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("and", $news_id, "=", true);
            $sql.= merge_sql_string_if_not_empty("news_trash", $news_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - bonus_store call by isbonusstore.php
        public function getBonusStore($link, $sid, $rid=-1, $bonus_trash=-1
                                     , $select_str="a.*,b.store_service,b.bonus_status,b.contract_startdate,b.contract_enddate,b.bonus_trash"
                                     , $join_str=" inner join (SELECT bid,store_service,bonus_status,bonus_trash,contract_startdate,contract_enddate FROM bonus_setting) as b on a.bid=b.bid "
                                     , $sort_str="bid,store_id")
        {
            $sql = "SELECT $select_str FROM bonus_store";
            $sql.= $join_str;
            $sql.= " WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("a.store_id"   , $sid        , "=", true);
            $sql.= merge_sql_string_if_not_empty("a.rid"        , $rid        , ">", true);
            $sql.= merge_sql_string_if_not_empty("b.bonus_trash", $bonus_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - membercard call by is_storemember.php
        public function getMembercard4isStoremember($link, $mid, $store_id=-1, $membercard_trash=-1
                                     , $select_str="a.member_id,b.sid,b.store_id,a.member_date,a.card_type,b.store_name, b.store_picture"
                                     , $join_str=" inner join ( select sid,store_id,store_name,store_picture from store) as b ON a.store_id= b.sid "
                                     , $sort_str="")
        {
            $sql = "SELECT $select_str FROM membercard  as a";
            $sql.= $join_str;
            $sql.= " WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("a.membercard_trash", $membercard_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.member_id"       , $mid             , "=", true);
            $sql.= merge_sql_string_if_not_empty("b.store_id"        , $store_id);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - membercard call by is_storemember.php
        public function getMyMembercardCount($link, $sid, $membercard_trash=-1, $startdate="", $enddate="", $select_str="count(*) AS member_count", $sort_str="")
        {
			$sql = "SELECT $select_str FROM membercard WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("membercard_trash", $membercard_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id"        , $sid);
            if (!empty($startdate)) $sql .= merge_sql_string_if_not_empty("membercard_created_at", $startdate." 00:00:00", ">=", false);
            if (!empty($enddate)  ) $sql .= merge_sql_string_if_not_empty("membercard_created_at", $enddate." 23:59:59"  , "<=", false);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - store call by is_storemember.php
        public function getStore($link, $store_id="", $store_trash=-1, $sid=""
                                     , $select_str="`sid`, `store_id`, `store_type`, `store_name`, `shopping_area`, `store_phone`, `store_address`, `store_website`, `store_facebook`,`store_news`,`store_descript`, `store_opentime`, `store_picture`, `store_latitude`, `store_longitude`, `store_status`"
                                     , $sort_str="")
        {
            $sql = "SELECT $select_str FROM store ";
            $sql.= " WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("sid"        , $sid);
            $sql.= merge_sql_string_if_not_empty("store_trash", $store_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("store_id"   , $store_id);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - storetype call by store_type.php
        public function getStoreType($link, $storetype_trash=-1
                                     , $select_str="store_type,storetype_name"
                                     , $sort_str="store_type")
        {
            $sql = "SELECT store_type,storetype_name from store_type where storetype_trash = 0 order by store_type ";
            $sql = "SELECT $select_str FROM store_type ";
            $sql.= " WHERE 1=1";

            $sql.= merge_sql_string_if_not_empty("storetype_trash", $storetype_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - store call by is_storemember.php
        public function getStoreExtra($link, $coupon="", $store_trash=-1, $store_type=-1, $store_status=-1, $shopping_area=""
                                     , $table_as="AS a"
                                     , $select_str="a.sid, a.store_id, a.store_type, a.store_name, a.shopping_area, a.store_phone, a.store_address, a.store_website, a.store_facebook,a.store_news,a.store_picture, a.store_latitude, a.store_longitude, a.store_status"
                                     , $join_str="INNER JOIN(SELECT * FROM coupon) as b ON b.coupon_storeid = a.sid"
                                     , $sort_str="a.shopping_area ASC, a.store_type,a.store_id"
                                     , $group_str="a.sid")
        {
            $sql = "SELECT $select_str FROM store $table_as";
			if (!empty($coupon)) $sql.= " ".$join_str;
            $sql.= " WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("store_trash", $store_trash, "=", true);
            if (!empty($coupon)) {
                $sql.=" and b.coupon_trash=0 and b.coupon_status=1";
                $sql.=" and b.coupon_enddate >= NOW() and b.coupon_startdate <= NOW()";
            }
            $sql.= merge_sql_string_if_not_empty("a.shopping_area"        , $shopping_area);
            $sql.= merge_sql_string_if_not_empty("a.store_type", $store_type, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.store_status", $store_status, "=", true);
            $sql.= (!empty($group_str)) ? " GROUP BY ".$group_str : "";
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - notificationlog call by push_list.php
        public function getNotificationlog($link, $member_id="", $role=-1, $pushmsg_startdate="", $pushmsg_enddate=""
                                     , $select_str="id as `pid`, updatetime as `push_date`, role as `member_type`, msg as `push_message`,title,body"
                                     , $sort_str="updatetime DESC")
        {
            $sql = "SELECT $select_str FROM notificationlog ";
            $sql.= " WHERE `fcmresult` like '%\"success\":1%'";

            if (!empty($pushmsg_startdate)) $sql.= merge_sql_string_if_not_empty("updatetime", $pushmsg_startdate." 00:00:00", ">=");
            if (!empty($pushmsg_enddate)) $sql.= merge_sql_string_if_not_empty("updatetime", $pushmsg_enddate." 23:59:59", "<=");
            $sql.= merge_sql_string_if_not_empty("Person_id" , $member_id);
            $sql.= merge_sql_string_if_not_empty("role"      , $role, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - push call by push_tomember.php
        public function updatePush($link, $push_no="", $push_status=-1)
        {
            $sql = "UPDATE push SET ";
            $sql.= merge_sql_string_set_value("push_status", $push_status, "=", true, true);
            $sql.= ",push_updated_at = NOW() WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("push_no", $push_no);

			mysqli_query($link, $sql);
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - question call by question_list.php
        public function getQuestion($link, $question_type="", $question_trash=-1
                                     , $select_str="a.rid, a.qid, b.questiontype_name, a.question_subject, a.question_description"
                                     , $join_str="INNER JOIN questiontype b ON a.qid=b.qid"
                                     , $sort_str=" a.qid,a.rid")
        {
            $sql = "SELECT $select_str FROM question a ";
            $sql.= $join_str;
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("a.question_trash", $question_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("a.qid"           , $question_type);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - questiontype call by question_type.php
        public function getQuestionType($link, $question_trash=-1
                                     , $select_str="qid, questiontype_name"
                                     , $sort_str="questiontype_name, qid")
        {
            $sql = "SELECT $select_str FROM questiontype ";
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("a.question_trash", $question_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - questionnaire call by questionnaire_list.php
        public function getQuestionNaire($link, $sid="", $qid=-1, $questionnaire_trash=-1
                                     , $select_str="qid,store_id,qindex,question,choose_type,q01,q02,q03,q04,q05,q06,q07,q08,q09,q10"
                                     , $sort_str="store_id ,qindex")
        {
            $sql = "SELECT $select_str FROM questionnaire ";
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("store_id", $sid);
            $sql.= merge_sql_string_if_not_empty("qid"                , $qid                , ">", true);
            $sql.= merge_sql_string_if_not_empty("questionnaire_trash", $questionnaire_trash, "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - questionnaire_title call by questionnaire_title.php
        public function getQuestionnaireTitle($link, $tid=-1, $questionnaire_trash=-1
                                     , $select_str="store_id, questionnaire_title, startdate, enddate, description"
                                     , $sort_str="store_id, qindex")
        {
            $sql = "SELECT $select_str FROM questionnaire_title ";
            $sql.= " WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("tid"                , $tid                , ">", true);
            $sql.= merge_sql_string_if_not_empty("questionnaire_trash", $questionnaire_trash, "=", true);
            $sql.= merge_sql_string_if_not_empty("enddate"  , date("Y-m-d"), ">=");
            $sql.= merge_sql_string_if_not_empty("startdate", date("Y-m-d"), "<=");
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - constellation_answer call by save_constellation.php
        public function insertConstellationAnswer($link, $fields, $datas)
        {
            $sql="INSERT INTO constellation_answer ($fields) VALUES";
            $sql=$sql."($datas);";

            mysqli_query($link,$sql) or die(mysqli_error($link));
            return mysqli_affected_rows($link);
        }
        // 通用資料庫函式 - constellation call by save_constellation.php
        public function getConstellationBetweenDate($link, $constellationdate)
        {
            $sql = "SELECT * FROM constellation WHERE endday >= '$constellationdate' and startday <='$constellationdate' ";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - constellation call by save_constellation.php
        public function getQuestionNaireAnswer($link, $mid="", $answer_trash=-1, $sqid=-1, $eqid=-1)
        {
	        $sql = "SELECT * FROM questionnaire_answer where 1=1 ";
            $sql.= merge_sql_string_if_not_empty("member_id"   , $mid);
            $sql.= merge_sql_string_if_not_empty("answer_trash", $answer_trash,  "=", true);
            $sql.= merge_sql_string_if_not_empty("qid"         , $sqid        , ">=", true);
            $sql.= merge_sql_string_if_not_empty("qid"         , $eqid        , "<=", true);

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - shopping_area call by shopping_area.php
        public function getShoppingArea($link, $shoppingarea_trash=-1
                                        , $select_str="`aid`, `shopping_area`, `contact_name`, `contact_phone`"
                                        , $sort_str="aid")
        {
            $sql = "SELECT $select_str FROM shopping_area WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("shoppingarea_trash", $shoppingarea_trash,  "=", true);
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 通用資料庫函式 - getSystemSetting call by system_setting.php
        public function getSystemSetting($link
                                        , $select_str="bonus,ios_version,android_version,about_us"
                                        , $sort_str="")
        {
            $sql = "SELECT $select_str FROM systemsetting";
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 特別資料庫函式 - getMyCoupon call by store_coupon_usestate.php
        public function getMyCoupon($link, $cid=-1, $using_flag=-1
                                  , $select_str="COUNT(cid) as total_coupon")
        {
            $sql = "SELECT $select_str FROM mycoupon WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("cid", $cid,  "=", true);
            $sql.= merge_sql_string_if_not_empty("using_flag", $using_flag,  "=", true);

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        // 特別資料庫函式 - getMyCoupon call by store_coupon_usestate.php
        public function getMyOrderAmount($link, $member_sid="", $membername="", $mid=""
                                  , $select_str="SUM(a.order_amount) as totla_amount"
                                  , $join_str="INNER JOIN (SELECT * FROM store) as b ON a.store_id = b.sid")
        {
            $sql1 = "SELECT SUM(a.order_amount) as totla_amount FROM `orderinfo` as a";
            $sql1 = $sql1." INNER JOIN (SELECT * FROM store) as b ON a.store_id = b.sid";
            $sql1 = $sql1." WHERE b.sid = '".$member_sid."' and a.member_id = $mid";

            $sql = "SELECT $select_str FROM orderinfo AS a";
            $sql.= " ".$join_str." WHERE 1=1 ";
            $sql.= merge_sql_string_if_not_empty("b.sid", $member_sid);
            $sql.= merge_sql_string_if_not_empty("b.store_name", $membername);
            $sql.= merge_sql_string_if_not_empty("b.member_id", $mid);

            if ($result = mysqli_query($link, $sql)) {
                return $result;
            }
            return null;
        }
        public function isHoliday($link, $sid, $check_date, &$ret)
        {
            $ret = "";
            try {
                $sql = "SELECT * FROM `holiday` where wid > 0 ";
                $sql.= merge_sql_string_if_not_empty("store_id", $sid);
                $sql.= merge_sql_string_if_not_empty("holiday", $check_date);
                
                if ($result = mysqli_query($link, $sql)) {
                    $ret = (mysqli_num_rows($result) > 0) ? "H" : "W";
                    return $result;
                } else {
                    $ret="W";
                }
            } catch (Exception $e) {
                $ret="W";
            }
            return null;
        }
        //is_vacation
        public function isVacation($link, $hid, $check_date, &$ret)
        {
            $ret = "";
            try {
                $sql = "SELECT * FROM `vacation` where vid > 0 ";
                $sql.= merge_sql_string_if_not_empty("hid", $hid);
                $sql.= merge_sql_string_if_not_empty("vacation", $check_date);
                if ($result = mysqli_query($link, $sql)) {
                    $ret = (mysqli_num_rows($result) > 0) ? "V" : "";
                    return $result;
                }
            } catch (Exception $e) {
                $ret="";
            }
            return null;
        }
        public function modifyConferenceroom($link, $sid, $remote_ip, $input, &$func, &$ret_msg, &$sql_msg)
        {
            $func = "modifyConferenceroom";
			$table = 'data_conferenceroom';
            $array_field_src = ['sid', 'name', 'msg_title', 'member_sid', 'head_img', 'avalible'];
            $fields = "";
            $values = "";

            $name = getVariant($input, 'name');
            // echo "getVariant ok";
            // Q1 我只留 marker_id 作為更新基礎
            $sql = "SELECT * FROM $table WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("name", $name);

            $ori_sid= $this->getDataViaField($link, $sql, 'nid');
            if (empty($ori_sid)) {
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty($cur_data)) {
                        $fields .= strlen($fields) ? "," : "";
                        $fields .= $array_field_src[$i];
                        $values .= strlen($values) ? "," : "";
                        $values .= "'$cur_data'";
                    }
                }
                $sql = "INSERT INTO $table (create_date, $fields
                       ) VALUES (
                            NOW(), $values
                       );";
                $ret_msg = "新增成功";
            } else {
                $sql = "UPDATE $table SET ";
                $sql.= merge_sql_string_set_value("modify_date", 'NOW()', "=", true, true);
                
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty([$cur_data])) {
                        $sql.= merge_sql_string_set_value($cur_field_name, $cur_data, "=");
                    }
                }
                $sql.= " WHERE 1=1";
                $sql.= merge_sql_string_if_not_empty("nid", $ori_sid);
                $ret_msg = "資料已存在，更新成功";
            }
            $result = $this->execute($link, $sql, $sql_msg);
            return $result;
        }
        public function modifyMemberInConferenceroom($link, $sid, $remote_ip, $input, &$func, &$ret_msg, &$sql_msg)
        {
            $func = "modifyMemberInConferenceroom";
			$table = 'data_memberinconferenceroom';
            $array_field_src = ['sid', 'conferenceroom_sid', 'member_sid', 'edit_sid'];
            $fields = "";
            $values = "";

            $conferenceroom_sid = getVariant($input, 'conferenceroom_sid');
            $member_sid         = getVariant($input, 'member_sid');
            // echo "getVariant ok";
            // Q1 我只留 marker_id 作為更新基礎
            $sql = "SELECT * FROM $table WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("conferenceroom_sid", $conferenceroom_sid);
            $sql.= merge_sql_string_if_not_empty("member_sid", $member_sid);

            $ori_sid= $this->getDataViaField($link, $sql, 'nid');
            if (empty($ori_sid)) {
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty($cur_data)) {
                        $fields .= strlen($fields) ? "," : "";
                        $fields .= $array_field_src[$i];
                        $values .= strlen($values) ? "," : "";
                        $values .= "'$cur_data'";
                    }
                }
                $sql = "INSERT INTO $table (create_date, $fields
                       ) VALUES (
                            NOW(), $values
                       );";
                $ret_msg = "新增成功";
            } else {
                $sql = "UPDATE $table SET ";
                $sql.= merge_sql_string_set_value("modify_date", 'NOW()', "=", true, true);
                
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty([$cur_data])) {
                        $sql.= merge_sql_string_set_value($cur_field_name, $cur_data, "=");
                    }
                }
                $sql.= " WHERE 1=1";
                $sql.= merge_sql_string_if_not_empty("nid", $ori_sid);
                $ret_msg = "資料已存在，更新成功";
            }
            $result = $this->execute($link, $sql, $sql_msg);
            return $result;
        }
        public function modifyNotify($link, $sid, $remote_ip, $input, &$func, &$ret_msg, &$sql_msg)
        {
            $func = "modifyNotify";
			$table = 'log_msginconferenceroom';
            $array_field_src = ['sid', 'conferenceroom_sid', 'member_sid', 'type', 'content', 'avalible'];
            $fields = "";
            $values = "";

            $name = getVariant($sid, 'sid');
            // echo "getVariant ok";
            // Q1 我只留 marker_id 作為更新基礎
            $sql = "SELECT * FROM $table WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("sid", $sid);

            $ori_sid= $this->getDataViaField($link, $sql, 'nid');
            if (empty($ori_sid)) {
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty($cur_data)) {
                        $fields .= strlen($fields) ? "," : "";
                        $fields .= $array_field_src[$i];
                        $values .= strlen($values) ? "," : "";
                        $values .= "'$cur_data'";
                    }
                }
                $sql = "INSERT INTO $table (create_date, $fields
                       ) VALUES (
                            NOW(), $values
                       );";
                $ret_msg = "新增成功";
            } else {
                $sql = "UPDATE $table SET ";
                $sql.= merge_sql_string_set_value("modify_date", 'NOW()', "=", true, true);
                
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty([$cur_data])) {
                        $sql.= merge_sql_string_set_value($cur_field_name, $cur_data, "=");
                    }
                }
                $sql.= " WHERE 1=1";
                $sql.= merge_sql_string_if_not_empty("nid", $ori_sid);
                $ret_msg = "資料已存在，更新成功";
            }
            $result = $this->execute($link, $sql, $sql_msg);
            return $result;
        }
        public function modifyNotifyPush($link, $sid, $remote_ip, $input, &$func, &$ret_msg, &$sql_msg)
        {
            $func = "modifyNotifyPush";
			$table = 'log_pushmsg';
            $array_field_src = ['sid', 'msg_sid', 'member_sid', 'response_json'];
            $fields = "";
            $values = "";

            $msg_sid = getVariant($input, 'msg_sid');
            $member_sid = getVariant($input, 'member_sid');
            // echo "getVariant ok";
            // Q1 我只留 marker_id 作為更新基礎
            $sql = "SELECT * FROM $table WHERE 1=1";
            $sql.= merge_sql_string_if_not_empty("msg_sid", $msg_sid);
            $sql.= merge_sql_string_if_not_empty("member_sid", $member_sid);
            // echo $sql;

            $ori_sid= $this->getDataViaField($link, $sql, 'nid');
            if (empty($ori_sid)) {
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty($cur_data)) {
                        $fields .= strlen($fields) ? "," : "";
                        $fields .= $array_field_src[$i];
                        $values .= strlen($values) ? "," : "";
                        $values .= "'$cur_data'";
                    }
                }
                $sql = "INSERT INTO $table (create_date, $fields
                       ) VALUES (
                            NOW(), $values
                       );";
                $ret_msg = "新增成功";
            } else {
                $sql = "UPDATE $table SET ";
                $sql.= merge_sql_string_set_value("modify_date", 'NOW()', "=", true, true);
                
                for ($i = 0; $i < count($array_field_src); $i++) {
                    $cur_field_name = $array_field_src[$i];
                    $cur_data = getVariant($input, $cur_field_name);
                    if (!empty([$cur_data])) {
                        $sql.= merge_sql_string_set_value($cur_field_name, $cur_data, "=");
                    }
                }
                $sql.= " WHERE 1=1";
                $sql.= merge_sql_string_if_not_empty("nid", $ori_sid);
                $ret_msg = "資料已存在，更新成功";
            }
            $result = $this->execute($link, $sql, $sql_msg);
            return $result;
        }
    }
?>