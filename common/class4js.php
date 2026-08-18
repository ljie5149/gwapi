<?php
    class CXmemberFields {
        public $label = "";
        public $name = "";
        public $index = "";
        public $editable = false;
        public $search = false;
        public $hidden = false;
    }
    class CXmemberData {
        public $page = 0;
        public $total = 0;
        public $records = 0;
        public $rows = [];
    }
    class CXmemberRecordset {
        public $page = 0;
        public $total_pages = 0;
        public $records = 0;
        public $start = 0;
        public $end = 0;
        public $offset = 0;
    }
?>