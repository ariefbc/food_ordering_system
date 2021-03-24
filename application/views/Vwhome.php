<!DOCTYPE html>

<html>

  <head>

  	<base href="<?php echo base_url();?>" />

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo $app_title;?></title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/font-awesome.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/ionicons.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/datatables/dataTables.bootstrap.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/datepicker/datepicker3.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/timepicker/bootstrap-timepicker.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/select2/select2.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/dist/css/AdminLTE.min.css">

    <link rel="stylesheet" href="<?php echo base_url();?>assets/dist/css/skins/_all-skins.min.css">

    <script type="text/javascript">

        function btn_response() {       

            setTimeout(function () { $('#div_button_panel').text('<?php if (isset($process_msg)) if ($process_msg !='') echo $process_msg.'....';?>'); }, 0);

        }

    </script>
  </head>

  <body class="skin-blue fixed" data-spy="scroll" data-target="#scrollspy">
    <div class="wrapper">



      <header class="main-header">

        <a href="<?php echo base_url();?>home" class="logo">

          <span class="logo-mini"><b><?php echo $app_title;?></b></span>

          <span class="logo-lg"><b><?php echo $app_title;?></b></span>

        </a>

        <nav class="navbar navbar-static-top" role="navigation">

          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">

            <span class="sr-only">Toggle navigation</span>

          </a>

          <div class="navbar-custom-menu">

            <ul class="nav navbar-nav">

              <li class="dropdown user user-menu">

                <a href="#" class="dropdown-toggle" data-toggle="dropdown">

                  <span class="hidden-xs"><?php echo $fullname;?></span>

                </a>

              </li>

            </ul>

          </div>

        </nav>

      </header>

      <aside class="main-sidebar">

        <section class="sidebar"  id="scrollspy">

          <ul class="nav sidebar-menu">

            <?php echo $menu;?>

        </ul>

        </section>

      </aside>



      <div class="content-wrapper">

        <section class="content-header">

          <?php echo $content_header;?>

          <ol class="breadcrumb">

            <?php echo $breadcrumb;?>

          </ol>

        </section>



        <section class="content">

        <?php echo $content;?>	

		</section>

      </div>

      <footer class="main-footer">

        <div class="pull-right hidden-xs">

          <b><?php echo $app_title;?></b> 

        </div>

      </footer>

    </div>



    <script src="<?php echo base_url();?>assets/plugins/jQuery/jQuery-2.1.4.min.js"></script>

    <script src="<?php echo base_url();?>assets/bootstrap/js/bootstrap.min.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/datatables/jquery.dataTables.min.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/datatables/dataTables.bootstrap.min.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/slimScroll/jquery.slimscroll.min.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/select2/select2.full.min.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/fastclick/fastclick.min.js"></script>

    <script src="<?php echo base_url();?>assets/dist/js/app.min.js"></script>

    <script src="<?php echo base_url();?>assets/dist/js/demo.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>

    <script src="<?php echo base_url();?>assets/plugins/timepicker/bootstrap-timepicker.min.js"></script>

    <script src="<?php echo base_url();?>assets/js/custom.js"></script>

    <script>

      $(function () {

      	$(".select2").select2();

        

        <?php if (isset($java_functions)) if ($java_functions!= '') echo $java_functions;?>

        <?php if (isset($js_script_page)) if ($js_script_page != '') echo $js_script_page; ?>    

        $('#dbgrid').DataTable({

          "paging": false,

          "lengthChange": false,

          "searching": false,

          "ordering": true,

          "info": true,

          "autoWidth": false

        });

      });

    </script>

    <?php 
      if (isset($java_alert)) {
        if (isset($java_alert['msg']) && $java_alert['msg'] != '') {
          echo "<script type='text/javascript'>
                alert('".$java_alert['msg']."');
                document.eform.".$java_alert['form_control_name'].".focus();
                </script>";
        }
      }
    ?>

  </body>

</html>

