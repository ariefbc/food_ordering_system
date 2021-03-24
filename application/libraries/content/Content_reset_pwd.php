
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content_reset_pwd {

	//function __construct()
    //{
    //	$this->ci =& get_instance();
    //}

    function content_resetting($control_name,$reset_code,$msg_error=NULL){
      if ($msg_error != NULL){
        $msg_error = "<font color=\"red\">".$msg_error."</font>";
      }

      $contents = "<div class=\"box box-primary\">
                <!-- form start -->
                ".form_open($control_name.'/resetting/'.$reset_code, array('role'=>'form'),array('passcode'=>$reset_code))."
                    <div class=\"form-group has-feedback\">
                      <input type=\"password\" class=\"form-control\" placeholder=\"New Password\" id=\"pwd1\" name=\"pwd1\">
                    </div>
                    <div class=\"form-group has-feedback\">
                      <input type=\"password\" class=\"form-control\" placeholder=\"Retype New Password\" id=\"pwd2\" name=\"pwd2\">
                    </div>
                    <div class=\"row\">
                      <div class=\"col-xs-8\">
                      ".$msg_error."
                      </div><!-- /.col -->
                      <div class=\"col-xs-4\">
                        <button type=\"submit\" name = \"savepwd\" class=\"btn btn-primary btn-block btn-flat bg-orange\">Save</button>
                      </div><!-- /.col -->
                    </div>
                </form>";

    return $contents;
    }

    public function load_content($control_name)
    {	
    	$contents = "<div class=\"box box-primary\">
                <!-- form start -->
                ".form_open($control_name, array('role'=>'form'))."
                  	<div class=\"form-group has-feedback\">
                      <input class=\"form-control\" placeholder=\"Alamat Email\" id=\"email\" name=\"email\">
                    </div>
                    <div class=\"row\">
                      <div class=\"col-xs-8\">
                      </div><!-- /.col -->
                      <div class=\"col-xs-4\">
                        <button type=\"submit\" name = \"reset\" class=\"btn btn-primary btn-block btn-flat bg-orange\">Reset</button>
                      </div><!-- /.col -->
                    </div>
                </form>";

		return $contents;
    }
	
	function content_title(){
		return 'Reset Password';	
	}
}	
