<?php
	function callAPI(&$error, $url, $data, $method="GET", $usedefault_header=false, $header=null)
	{
		$curl = curl_init();

		switch ($method)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, true);

				if (is_array($data))
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
				else
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

				if ($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				break;
			case "GET":
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));		
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);			
				break;
		    case "PUT":
				curl_setopt($curl, CURLOPT_PUT, true);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($usedefault_header) {
			$data_string = is_array($data) ? http_build_query($data) : (string)$data;
            $header=array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string));
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

		$result = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);

		return $result;
	}
	function callAPI4GW(&$error, $url, $data, $method="GET", $header=null, $usedefault_header=false)
	{
		$curl = curl_init();

		// 1. 若資料為陣列，自動轉為規格要求的 JSON 字串
		$post_data = $data;
		if (is_array($data) && ($usedefault_header || (is_array($header) && preg_grep('/content-type:\s*application\/json/i', $header)))) {
			$post_data = json_encode($data, JSON_UNESCAPED_UNICODE);
		}

		switch (strtoupper($method))
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, true);

				// 若已有 JSON 字串則直接傳送，不使用 http_build_query
				if (is_array($post_data)) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
				} else {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
				}

				if ($header != null) {
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				}
				break;

			case "GET":
				if ($data && is_array($data)) {
					$url = sprintf("%s?%s", $url, http_build_query($data));     
				}
				if ($header != null) {
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);            
				}
				break;

			case "PUT":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				if ($post_data) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($post_data) ? http_build_query($post_data) : $post_data);
				}
				break;

			default:
				if ($data && is_array($data)) {
					$url = sprintf("%s?%s", $url, http_build_query($data));
				}
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// ★【新增防護 1】：加上 User-Agent 避免 Cloudflare 直接攔截
		// curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
		// ★【新增 2】：模擬真實 User-Agent 避免 Cloudflare 攔截[cite: 2]
    	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

		// 2. 處理預設 Header 與長度計算
		if ($usedefault_header) {
			$data_string = is_array($post_data) ? http_build_query($post_data) : (string)$post_data;
			$default_header = array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string)
			);

			// ★【新增防護 2】：若外部有帶入 X-Gateway-Token 等額外 Header，進行合併傳送
			if (is_array($header)) {
				$default_header = array_merge($default_header, $header);
			}

			curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $default_header);
		}

		$result = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);

		return $result;
	}
?>