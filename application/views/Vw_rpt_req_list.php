<!DOCTYPE html>
<html>
  <head>
  	<base href="<?php echo base_url();?>" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $app_title;?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/ionicons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/datatables/dataTables.bootstrap.css">
    <!-- Date Picker -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/datepicker/datepicker3.css">
    <!-- Time Picker -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/timepicker/bootstrap-timepicker.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/select2/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
         folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="<?php echo base_url();?>assets/dist/css/skins/_all-skins.min.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    
     <style>
      .no-js #loader { display: none;  }
      .js #loader { display: block; position: absolute; left: 100px; top: 0; }
      .se-pre-con {
        position: fixed;
        left: 0px;
        top: 0px;
        width: 100%;
        height: 100%;
        z-index: 9999;
        background: url('<?php echo base_url();?>assets/images/Preloader_3.gif') center no-repeat #fff;
      }
    </style>

    <script>
      $(window).load(function() {
        // Animate loader off screen
        $(".se-pre-con").fadeOut("slow");;
      });
    </script>
  </head>
  <body class="skin-blue fixed" data-spy="scroll" data-target="#scrollspy">
    <div class="se-pre-con"></div>
    <div class="wrapper">

      <header class="main-header">
        <!-- Logo -->
        <a href="<?php echo base_url();?>home" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini"><b><?php echo $app_title;?></b></span>
          <!-- logo for regular state and mobile devices -->
          <span class="logo-lg"><!--<img src="<?php echo base_url();?>assets/img/bubble-property.jpg"  style="width: 50px;height: 50px;"/>--> <b><?php echo $app_title;?></b></span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
              <!-- User Account: style can be found in dropdown.less -->
              <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <span class="hidden-xs"><?php echo $fullname;?></span>
                </a>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar"  id="scrollspy">
          <!-- sidebar menu: : style can be found in sidebar.less -->
          <ul class="nav sidebar-menu">
            <li class="header">Menu</li>
            <?php echo $menu;?>
        </ul>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <?php echo $content_header;?>
          <ol class="breadcrumb">
            <?php echo $breadcrumb;?>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">
        <?php echo $content;?>	
		</section>
      </div><!-- /.content-wrapper -->
      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b><?php if (isset($app_title)) {echo $app_title;}?></b>
        </div>
      </footer>
    </div><!-- ./wrapper -->

    <!-- jQuery 2.1.4 -->
    <script src="<?php echo base_url();?>assets/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    <!-- Bootstrap 3.3.5 -->
    <script src="<?php echo base_url();?>assets/bootstrap/js/bootstrap.min.js"></script>
    <!-- DataTables -->
    <script src="<?php echo base_url();?>assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo base_url();?>assets/plugins/datatables/dataTables.bootstrap.min.js"></script>
    <!-- SlimScroll -->
    <script src="<?php echo base_url();?>assets/plugins/slimScroll/jquery.slimscroll.min.js"></script>
    <!-- Select2 -->
    <script src="<?php echo base_url();?>assets/plugins/select2/select2.full.min.js"></script>
    <!-- FastClick -->
    <script src="<?php echo base_url();?>assets/plugins/fastclick/fastclick.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo base_url();?>assets/dist/js/app.min.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo base_url();?>assets/dist/js/demo.js"></script>
    <!-- Date Picker -->
    <script src="<?php echo base_url();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>
    <!-- Time Picker -->
    <script src="<?php echo base_url();?>assets/plugins/timepicker/bootstrap-timepicker.min.js"></script>
    <!-- custom purposes -->
    <script src="<?php echo base_url();?>assets/js/custom.js"></script>
     <!-- page script -->
     
     <script>
      $(function () {
      	//Initialize Select2 Elements
        $(".select2").select2();
        
        $("#request_date_from").datepicker({autoclose: true,format: 'dd-M-yyyy'});
        $("#request_date_until").datepicker({autoclose: true,format: 'dd-M-yyyy'});
        
        <?php echo $java_functions;?>
          
        $('#dbgrid').DataTable({
          "paging": false,
          "lengthChange": false,
          "searching": false,
          "ordering": true,
          "info": true,
          "autoWidth": false
        });
      
      });

      function test(){
        var request_date_from = document.getElementById("request_date_from").value;
        var request_date_until = document.getElementById("request_date_until").value;

        if (request_date_from != "" && request_date_until != "") {
          window.open('rpt_req_list/generate_rpt/'+request_date_from+'/'+request_date_until, '_blank', 'location=yes,scrollbars=yes,status=yes');
        } else {
          alert('Filter Tanggal Tidak Boleh Kosong');
        }
      }
    </script>
  </body>
</html>
