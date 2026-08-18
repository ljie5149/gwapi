<?php
	//掛在編輯和新增頁面上，即可取得該資料id
    global $g_crud_id_array, $g_crud_zhtw_array;
    $root       	= $g_sidemenu['root'     ];
    $root_id    	= $g_sidemenu['root_id'  ];
	$crud_id_array  = $g_crud_id_array;
	$crud_array 	= $g_crud_zhtw_array;
	
	$html_str = "";
	$xbObject = array(); $m = 0; $n = 0;
	$html_str.= '<div class="form-check">';
	
	$html_str.= '<table><tr><td>
			<input type="checkbox" id="xbAuthorAll" name="xbAuthorAll" value="xbAuthorAll" onchange="xbAllChange()" />
			<label class="form-check-label" for="xbAuthorAll">全選</label>';
			for ($k = 0; $k < count($crud_id_array); $k++) {
				$xb_id = $crud_id_array[$k];
				$html_str.= '	</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td>';
				$html_str.= '<input type="button" id="'.$xb_id.'" class="btn btn-warning" name="'.$xb_id.'" value="'.$crud_array[$k].'" onclick="xbMainCrudClick(\''.$xb_id.'\')" />';
				$html_str.= '</td>';
			}
	$html_str.= '</tr></table></div><br>';
	for ($i = 0; $i < count($root); $i++) {
		if (count($g_sidemenu[$root[$i]]) > 0) {
			$item       = $g_sidemenu[$root[$i]         ];
			$item_id    = $g_sidemenu[$root[$i].'_id'   ];
			$item_href  = $g_sidemenu[$root[$i].'_href' ];
			// if ($item_id[$j] == 'hh') continue;
			$html_str.= '<table>';
				for ($j = 0; $j < count($item); $j++) {
					$n = 0;
					if ($m == 0) $m = 1;
					else $m *=2;
					$html_str.= '<tr>';
					$html_str.= '<div class="form-check">';
					$html_str.= '	<td>';
					$html_str.= '		<input type="checkbox" id="'.$item_id[$j]."-m".'" name="myMainCheckbox" value="'.$m.'" onchange="xbHeadMenuChange(\''.$item_id[$j]."-m".'\')" />';
					$html_str.= '		<label class="form-check-label" for="'.$item_id[$j]."-m".'">'.$item[$j].'</label>';
					$html_str.= '	</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
					array_push($xbObject, ["checkbox_id" =>  $item_id[$j]."-m"]);
					for ($k = 0; $k < count($crud_id_array); $k++) {
						$xb_id = $crud_id_array[$k].'_'.$item_id[$j];
						$xb_caption = $crud_array[$k];
						array_push($xbObject, ["checkbox_id" =>  $xb_id]);
						$html_str.= '	<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
								<td>';
						$html_str.= '		<input type="checkbox" id="'.$xb_id.'" name="xb_'.$crud_id_array[$k].'" value="'.$m.'" onchange="xbCrudChange(\''.$xb_id.'\')" />';
						$html_str.= '		<label class="form-check-label" for="'.$xb_id.'">'.$crud_array[$k].'</label>';
						$html_str.= '	</td>';
					}
					$html_str.= '</div>';
					$html_str.= '</tr>';
				}
			$html_str.= '</table';
		}
	}
?>

<!-- 新 Bootstrap4 核心 CSS 文件 ExtraMenu -->
<!-- <link rel="stylesheet" href="./../css/v4.3.1/bootstrap.min.css"> -->
<!-- import/export dialog 使用 -->
<!-- <link rel="stylesheet" type="text/css" href="./../css/mydialog.css">
<script src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script> -->
<SCRIPT>
	function setChecked(val, xb) {
		xb.forEach(checkbox => {
			checkbox.checked = (checkbox.value & val);
		});
	}
	function initDialog() {
		const myForm 		 = document.querySelector('#author_form');
		const maincheckboxes = myForm.querySelectorAll('input[type="checkbox"][name="myMainCheckbox"]');
		const xb_list 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_list"]'		  );
		const xb_add 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_add"]'		  );
		const xb_edit 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_edit"]'		  );
		const xb_delete 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_delete"]'	  );
		const xb_import 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_import"]'	  );
		const xb_export 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_export"]'	  );
		const str_val = document.getElementById("authorization_page").value;
		const myArray = str_val.split('!!');
		var i = 0;

		if (i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), maincheckboxes);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_list);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_add);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_edit);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_delete);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_import);

		if (++i >= myArray.length) return;
		setChecked(parseInt(myArray[i]), xb_export);
	}
	function getValue(val, xb) {
		xb.forEach(checkbox => {
			if (checkbox.checked) val += parseInt(checkbox.value);
		});
		return val;
	}
    function submitAuthor() {
		const myForm 		 = document.querySelector('#author_form');
		const maincheckboxes = myForm.querySelectorAll('input[type="checkbox"][name="myMainCheckbox"]');
		const xb_list 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_list"]'		  );
		const xb_add 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_add"]'		  );
		const xb_edit 		 = myForm.querySelectorAll('input[type="checkbox"][name="xb_edit"]'		  );
		const xb_delete 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_delete"]'	  );
		const xb_import 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_import"]'	  );
		const xb_export 	 = myForm.querySelectorAll('input[type="checkbox"][name="xb_export"]'	  );

		var main_author = 0; var list_author = 0; var add_author = 0;
		var edit_author = 0; var delete_author = 0; var import_author = 0; var export_author = 0;
		main_author   = getValue(main_author  , maincheckboxes);
		list_author   = getValue(list_author  , xb_list		  );
		add_author 	  = getValue(add_author   , xb_add		  );
		edit_author   = getValue(edit_author  , xb_edit		  );
		delete_author = getValue(delete_author, xb_delete	  );
		import_author = getValue(import_author, xb_import	  );
		export_author = getValue(export_author, xb_export	  );
		ret = main_author + '!!' + list_author + '!!' + add_author + '!!' + edit_author + '!!' + delete_author + '!!' + import_author + '!!' + export_author;
		document.getElementById("authorization_page").value = ret;
		hideAuthorDialog();
    }
    
    function hideAuthorDialog()
    {
        $('.overlay-author-page').css('display', 'none');
    }

	function xbAllChange()
	{
		var js_xbjson = '<?php echo json_encode($xbObject); ?>';
		var js_xbObject = JSON.parse(js_xbjson);
		// console.log(js_xbObject);
		for (var i = 0; i < js_xbObject.length; i++) {
			var menu_xb = js_xbObject[i];
			// console.log(menu_xb["checkbox_id"]);
			document.getElementById(menu_xb["checkbox_id"]).checked = document.getElementById("xbAuthorAll").checked;
		}
	}
	function xbMainCrudClick(id)
	{
		console.log(id);
		var js_xbjson = '<?php echo json_encode($xbObject); ?>';
		var js_xbObject = JSON.parse(js_xbjson);
		console.log(js_xbObject);
		for (var i = 0; i < js_xbObject.length; i++) {
			var menu_xb = js_xbObject[i];
			var menu_item_id = menu_xb["checkbox_id"];
			if (menu_item_id.search(id + "_") > -1 || menu_item_id.search("-m") > -1) {
				document.getElementById(menu_item_id).checked = true;
			}
		}
	}
	function xbHeadMenuChange(id)
	{
		console.log(id);
		var js_xbjson = '<?php echo json_encode($crud_id_array); ?>';
		var js_xbObject = JSON.parse(js_xbjson);
		if (document.getElementById(id).checked == false)
			document.getElementById("xbAuthorAll").checked = false;

		var dst_id = id.split('-');
		for (var i = 0; i < js_xbObject.length; i++) {
			var xb_id = js_xbObject[i] + "_" + dst_id[0];
			console.log(xb_id);
			document.getElementById(xb_id).checked = document.getElementById(id).checked;
		}
	}
	function xbCrudChange(id)
	{
		console.log(id);
		var menu_xb_head = id.split("_");
		console.log(menu_xb_head);
		if (document.getElementById(id).checked == false)
			document.getElementById("xbAuthorAll").checked = false;
		
		if (document.getElementById(id).checked == true)
			document.getElementById(menu_xb_head[1] + "-m").checked = true;
		else {
			if (menu_xb_head[0] == 'list')
				document.getElementById(menu_xb_head[1] + "-m").checked = false;
		}
	}
</SCRIPT>
<div class="overlay-author-page" style="display:none;">
    <div class="author-dlg">
        <div class="dlghead">
            <div class="dlgtitle">權限設定</div>
        </div>
        <div class="dlgcontainer">
            <form method="post" enctype="multipart/form-data" id="author_form">
                <div class="form-group">
					<?php echo $html_str; ?>
                </div> <!-- form-group -->
				<br>
                <div class="form-group">
                    <button id="submit-autor" type="button" class="btn btn-primary" onclick="submitAuthor()">確定</button>
					<?php echo getHtmlSpaceChar(3); ?>
                    <button id="close-autor" type="button" class="btn btn-secondary" onclick="hideAuthorDialog()">取消</button>
                </div> <!-- form-group -->
			</form>
        </div>
    </div>
</div>