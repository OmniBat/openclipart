$.fn.jump = function(options) {
    var settings = $.extend({
        speed: 150
    }, options);
    var self = $(this);
    var init_margin = self.css('margin-right');
    var color = self.css('border-color');
    self.css('border-color', 'red');
    self.animate({'margin-right': '-10px'}, settings.speed, function() {
        self.animate({'margin-right': '10px'}, settings.speed, function() {
            self.animate({'margin-right': '-10px'}, settings.speed, function() {
                self.animate({'margin-right': init_margin}, settings.speed);
                self.css('border-color', color);
            });
        });
    });
};





$(function() {
    var email_regex = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
    function valid_email(email) {
        return email.match(email_regex);
    }
    $('#copyright-violation-alert input').click(function() {
        $(this).parents('#copyright-violation-alert').css('visibility', 'hidden');
    });
    return ;
    $('#register form').live('submit', function() {
        $('.error').html('').hide();
        var valid = true;
        var username = $('#username');
        var password = $('#password');
        var email = $('#email');
        var full_name = $('#full_name');
        var recaptcha = $('#recaptcha_response_field');
        if (username.val() === '') {
            username.jump();
            valid = false;
        }
        if (password.val() === '') {
            password.jump();
            valid = false;
        }
        if (email.val() === '') {
            email.jump();
            valid = false;
        } else if (!valid_email(email.val())) {
            email.jump();
            valid = false;
            $('.error').html("Sorry but it seams that your email is invalid");
        }
        if (full_name.val() === '') {
            full_name.jump();
            valid = false;
        }
        if (recaptcha.val() === '') {
            recaptcha.jump();
            valid = false;
        }
        
        if (valid) {
            var url = $(this).attr('action');
            $.post(url, $("#sign_up").serialize(), function(response) {
                if (!response) {
                    $('.error').html('Ops. Sorry, something goes wrong.').show();
                } else if (response.status === '1') {
                    $('.error').hide();
                    $("#sign_up").remove();
                    $('h2').html('Thank you for signing up. You can now ' +
                                 '<a href="/signin">login</a>');
                } else {
                    $('.error').html(response.message).show();
                }
            }, 'json');
        }
        return false;
    });
});
