<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>
<!-- Logout Modal-->
<div class="modal fade" id="pageoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">確定要離開?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">如果確定離開,請按下 "確定" .</div>
            <div class="modal-footer">
                <a class="btn btn-outline-primary" href="../mydatagrid.php<?php echo $url_param_str; ?>" onclick="mydeleteCookie('parent_sid')">確定</a>
                <button class="btn btn-secondary" type="button" data-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>
        <!-- Bootstrap core JavaScript-->
        <script src="./../../vendor/jquery/jquery.min.js"></script>
        <script src="./../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Core plugin JavaScript-->
        <script src="./../../vendor/jquery-easing/jquery.easing.min.js"></script>

        <!-- Custom scripts for all pages-->
        <script src="./../../js/sb-admin-2.min.js"></script>

        <!-- Page level plugins -->
        <script src="./../../vendor/chart.js/Chart.min.js"></script>

        <!-- Page level custom scripts -->
        <!--<script src="./js/demo/chart-area-demo.js"></script>-->
        <!--<script src="./js/demo/chart-pie-demo.js"></script>-->
        
        <!-- Page level plugins -->
        <script src="./../../vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="./../../vendor/datatables/dataTables.bootstrap4.min.js"></script>