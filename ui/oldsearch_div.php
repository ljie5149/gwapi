
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 mb-4">
            <!-- Illustrations -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">搜尋條件</h6>
                </div>
                <div class="card-body">
                    <form action="news.php" method="Post" name='frm1' id='frm1' class="card">
                    <input type="hidden" name="act" id="act"  value=""/>
                    <input type="hidden" name="tid" id="tid"  value=""/>
                    <input type="hidden" name="page" id="page" value="1">	
                    <div class="row">
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">發佈日期(起)</label>
                                <!--<input type="text" name="field-name1" class="form-control" data-mask="0000/00/00" data-mask-clearifnotmatch="true" placeholder="yyyy/mm/dd" />-->
                                <input class="text-input small-input" type="date" name="txtSDate" id="txtSDate" value="<?=$SDate;?>" />
                            </div>						
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">發佈日期(迄)</label>
                                <!--<input type="text" name="field-name2" class="form-control" data-mask="0000/00/00" data-mask-clearifnotmatch="true" placeholder="yyyy/mm/dd" />-->
                                <input class="text-input small-input" type="date" name="txtEDate" id="txtEDate" value="<?=$EDate;?>" />
                            </div>		
                        </div>					
                        <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label class="form-label">內容:</label>
                            <div class="row align-items-center">
                            <div class="col-auto">
                                <input type="text" id="news_subject" name="news_subject" class="form-control w-12" value="">
                            </div>
                            </div>
                        </div>	
                        </div>	
                        <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="text-center">
                                <button type="button" class='btn btn-info ml-auto' onclick='SubmitF();'>搜尋</button> &nbsp;<?php if ($_SESSION['authority']=="1"){ ?><button type="button" class="btn btn-success ml-auto" onclick='GoAddUser()'>新增</button><?php } ?> 
                            </div>							
                        </div>
                        </div>						
                    </div>					
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>