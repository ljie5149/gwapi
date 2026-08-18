<?php
    if ($_SESSION['authority']=="4") {
        $period = isset($_POST['period']) ? $_POST['period'] : '2';
        $sql = "SELECT sum(order_amount) as order_amount FROM orderinfo where order_status='1' and store_id=".$_SESSION['loginsid']."";
        $NowDate = date("Y-m-d",time());
        $y = 1;
        switch ($period) {
            case 1:
                $EDate=date("Y-m-d H:i:s",time());
                $SDate=date("Y-m-d",strtotime($NowDate))." 00:00:00";
                $y = 1;
                break;
            case 2:
                $EDate=date("Y-m-d",strtotime($NowDate));
                $SDate=date("Y-m-d",strtotime("-7 Days",strtotime($NowDate)));
                $y = 7;
                break;
            case 3:
                $EDate=date("Y-m-d",strtotime($NowDate));
                $SDate=date("Y-m-d",strtotime("-30Days",strtotime($NowDate)));
                $y = 30;
                break;
        }
        $tt = ""; $cc = ""; $ss = "";
        if ($period == 1) {
            $tt = "'".$NowDate."'";
            $cc = $cc.getordercount($remote_ip, $member_id, $NowDate);
            $ss = $ss.getordersum($remote_ip, $member_id, $NowDate);
        } else {
            for ($x = 1; $x <= $y; $x++) {
                $tt = $tt."'".date('Y-m-d', strtotime('+'.$x.' Days', strtotime($SDate)))."'";
                $convertedTime = date('Y-m-d', strtotime('+'.$x.' Days', strtotime($SDate)));
                $cc = $cc.getordercount($remote_ip, $member_id, $convertedTime);
                $ss = $ss.getordersum($remote_ip, $member_id, $convertedTime);
                if ($x < $y) {
                    $tt = $tt.","; $cc = $cc.","; $ss = $ss.",";
                }
            }
        }
?>		  
        <form action="main.php" method="Post" name='frm1' id='frm1' >
            <input type="hidden" name="period" id="period"  value=""/>
            <!-- Content Row -->
            <div class="row">
                <div class="col-lg-12 mb-8">
                    <!-- Illustrations -->
                    <div class="col-xl-12 col-lg-7">
                        <div class="card shadow mb-4">
                            <!-- Card Header - Dropdown -->
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">訂單筆數統計</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                        <div class="dropdown-header">選單:</div>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(1);'>日統計</a>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(2);'>週統計</a>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(3);'>月統計</a>
                                        <!--<div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Something else here</a> -->
                                    </div>
                                </div>
                            </div>
                            <!-- Card Body -->
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="myAreaChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-12 col-lg-7">
                        <div class="card shadow mb-4">
                            <!-- Card Header - Dropdown -->
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">訂單金額統計</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                        <div class="dropdown-header">選單:</div>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(1);'>日統計</a>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(2);'>週統計</a>
                                        <a class="dropdown-item" href="#" onclick='SubmitF(3);'>月統計</a>
                                        <!--<div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Something else here</a> -->
                                    </div>
                                </div>
                            </div>
                            <!-- Card Body -->
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="myAreaChart2"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </form>
<?php
    }
?>
<!-- /.container-fluid -->