<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>FOS</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo base_url();?>assets/bootstrap/css/ionicons.min.css">
    <link rel="stylesheet" href="<?php echo base_url();?>assets/dist/css/AdminLTE_login.min.css">
    <link rel="stylesheet" href="<?php echo base_url();?>assets/plugins/iCheck/square/blue.css">

  </head>
  <body class="hold-transition login-page" style="background-image: url('<?php echo base_url();?>assets/images/bg_login.jpg');background-repeat: no-repeat;background-size: cover; background-attachment: fixed">
    <div class="login-box">
      <div class="login-logo">
        <strong><font color="white">Food Ordering System 1.0</font></strong>
      </div>
      <div class="login-box-body">
        <p class="login-box-msg"><?php echo $title; ?></p>
        <?php echo form_open('verifylogin'); ?>
          <div class="form-group has-feedback">
            <input type="username" class="form-control" placeholder="Username" id="username" name="username">
            <span class="glyphicon glyphicon-user form-control-feedback"></span>
          </div>
          <div class="form-group has-feedback">
            <input type="password" class="form-control" placeholder="Password" id="password" name="password">
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
          </div>
          <div class="row">
            <div class="col-xs-8">
            <?php //echo anchor(site_url().'reset_pwd','Forget Password');?>
            </div>
            <div class="col-xs-4">
              <button type="submit" class="btn btn-block btn-flat bg-yellow">Login</button>
            </div>
          </div>
        </form>
		  </div>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <script src="<?php echo base_url();?>assets/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    <script src="<?php echo base_url();?>assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo base_url();?>assets/plugins/iCheck/icheck.min.js"></script>
    <script>
      <?php
        if (isset($session_expired_msg)) {
          if ($session_expired_msg != "") {
            echo $session_expired_msg;
          }
        }
      ?>
    </script>
  </body>
</html>
