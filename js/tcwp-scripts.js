/**
 * TruConversion WordPress Plugin javascript
 */

jQuery(document).ready(function($) { 
    
    $("#tcSignin").click(function(e){
        e.preventDefault();
        var email = $("#tc_email"), pass = $("#tc_password"), status = true;
        $('.error-msg').html('');
        removeErrorList($("#TCsignin"), [email, pass], '.parsley-errors-list');
        if (!$.trim(email.val()).length){
            email.parent().addClass("parsley-error");
            status = false;
        } else {
            if(!validateEmail(email.val())){
                email.parent().addClass("parsley-error");
                status = false;
            }
        }

        if (!$.trim(pass.val()).length){
            pass.parent().addClass("parsley-error");
            status = false;
        } else {
            if(!isValidPassword(pass.val())){
                pass.parent().addClass("parsley-error");
                status = false;
            }
        }
        if (status){
            var $this = $(this);
            $this.val('Please Wait ...');
            $this.attr('disabled',true);
            var data = {
                    '_ajax_nonce': ajax_object.nonce,
                    'action': 'tc_signin',
                    'email': email.val(),
                    'password': pass.val()
            };
            $.post(ajax_object.ajax_url, data, function(response) {
                response = $.trim(response);
                response = JSON.parse(response);
                if(response.statusCode == '200'){
                    if((response.data).length != 0){
                        if(response.data.domain_id === 0){
                            var doHTML = '';
                            $.each(response.data.domains, function(i, k){
                                doHTML += '<option value="' + i + '">' + k + '</option>';
                            });
                            $('.TcSignForm').hide();
                            $('.TcWebsiteForm #tc_websites').html(doHTML);
                            $('.TcWebsiteForm').show();
                        }else{
                            installCodeAction(response.data.domain_id);
                        }
                    } else {
                        $this.val('Sign In');
                        $this.removeAttr('disabled');
                        $('.error-msg').html(response.error.message);
                    }
                } else {
                    $this.val('Sign In');
                    $this.removeAttr('disabled');
                    $('.error-msg').html(response.error.message);
                }
            });
        }
    });
    
    $("#tcSignUp").click(function(e){
        e.preventDefault();
        var email = $("#tc_signup_email"), pass = $("#tc_signup_password"), fullname = $("#tc_full_name"), url = $("#tc_domain"), terms = $("#tc_acceptterms"), company = $("#tc_company_name"), status = true;
        removeErrorList($("#TCsignup"), [email, pass, fullname, url, terms, company], '.parsley-errors-list');
        
        if (!$.trim(fullname.val()).length){
            fullname.parent().addClass("parsley-error");
            status = false;
        }
            
        if (!$.trim(email.val()).length){
            email.parent().addClass("parsley-error");
            status = false;
        } else {
            if (!validateEmail(email.val())){
                email.parent().addClass("parsley-error");
                status = false;
            }
        }

        if (!$.trim(pass.val()).length){
            pass.parent().addClass("parsley-error");
            status = false;
        } else {
            if (!isValidPassword(pass.val())){
                pass.parent().addClass("parsley-error");
                status = false;
            }
        }
        
        if (!$.trim(company.val()).length){
            company.parent().addClass("parsley-error");
            status = false;
        }

        if (!$.trim(url.val()).length){
            url.parent().addClass("parsley-error");
            status = false;
        } else {
            if (!validateUrlMain(url.val())){
                url.parent().addClass("parsley-error");
                status = false;
            }
        }

        if (!terms.is(":checked")){
            terms.parent().addClass("parsley-error");
            status = false;
        }
        
        if (status){
            var $this = $(this);
            $this.val('Please Wait ...');
            $this.attr('disabled',true);
            var data = {
                    '_ajax_nonce': ajax_object.signup_nonce,
                    'action': 'tc_signup',
                    'fullname': fullname.val(),
                    'email': email.val(),
                    'password': pass.val(),
                    'company': company.val(),
                    'url': url.val()
            };
            $.post(ajax_object.ajax_url, data, function(response) {
                response = $.trim(response);
                response = JSON.parse(response);
                if (response.statusCode == '200'){
                    if ((response.data).length != 0){
                        installCodeAction(response.data.domain_id);
                    } else {
                        $('.error-msg').html(response.error.message);
                        $this.val('Sign Up');
                        $this.removeAttr('disabled');
                    }
                } else {
                    $this.val('Sign Up');
                    $this.removeAttr('disabled');
                }
            });
        }
    });
    
    $("#tcDomain").click(function(e){
        e.preventDefault();
        if($('#tc_websites').val()){
            $(this).val('Please Wait ...');
            $(this).attr('disabled',true);
            installCodeAction($('#tc_websites').val());
        }else{
          console.log("Error");  
        }
    });
    
    function installCodeAction(d){
        var data = {
                '_ajax_nonce': ajax_object.install_nonce,
                'action': 'tc_install_code',
                'd': d
        };
        $.post(ajax_object.ajax_url, data, function(response) {
            response = $.trim(response);
            if(response == 'OK'){
                location.reload();
            }
        });
    }
    
    function appendErrorList(e,m){
        e.after('<ul class="parsley-errors-list filled"><li class="parsley-required">' + m + '</li></ul>');
    }
    
    function removeErrorList(f, o, c){
        $.each(o, function(i, k){
            k.parent().removeClass('parsley-error');
        });
        f.find(c).remove();
    }
});

function isValidPassword(value) {
    var validLength = 3;
    if (value.length < validLength) {
        return false;
    }
    return true;
}
function validateEmail(e){
    var filter = /^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/;
    return filter.test(e);
}
function validateUrlMain(toTest) {
    var regex = /[a-zA-Z0-9\-,_,:]+\.[a-zA-Z]{2,3}/;
    return (regex.test(toTest));
}