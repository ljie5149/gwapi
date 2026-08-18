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
            $header=array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data));
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

		$result = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);

		return $result;
	}
?>