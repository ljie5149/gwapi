<?php
    // $g_country_code = ["基隆市" => "01", "台北市" => "02", "新北市" => "03", "桃園縣" => "04", "新竹市" => "05", "新竹縣" => "06", "苗栗縣" => "07"
    //                  , "台中市" => "08", "彰化縣" => "09", "南投縣" => "10", "雲林縣" => "11", "嘉義市" => "12", "嘉義縣" => "13", "台南市" => "14"
    //                  , "高雄市" => "15", "屏東縣" => "16", "台東縣" => "17", "花蓮縣" => "18", "宜蘭縣" => "19", "澎湖縣" => "20", "金門縣" => "21", "連江縣" => "22"];
    class CXcounty {
        protected $country_array = ["基隆市", "台北市", "新北市", "桃園市", "新竹市", "新竹縣", "苗栗縣"
                                  , "台中市", "彰化縣", "南投縣", "雲林縣", "嘉義市", "嘉義縣", "台南市"
                                  , "高雄市", "屏東縣", "台東縣", "花蓮縣", "宜蘭縣", "澎湖縣", "金門縣", "連江縣"];
        private function getCountyJsonTW() {
            $json_str = file_get_contents('./../json/CityCountyData.json'); // 讀取 JSON 文件
            $data = json_decode($json_str, true); // 解碼 JSON

            // print_r($json);
            return $data;
        }
        public function getCountyTW() {
            return $this->country_array;
        }
        public function getTownTW($county) {
            $ret = array();
            $county_json = $this->getCountyJsonTW();
            for ($i = 0; $i < COUNT($county_json); $i++) {
                $cur_county = $county_json[$i];
                if ($cur_county["CityName"] == $county) {
                    $ret = $cur_county["AreaList"];
                    break;
                }
            }
            return $ret;
        }
    }
?>