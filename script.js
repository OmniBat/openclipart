
// plugin that indicate error in a field (move field from left to right with red color)
$.fn.jump = function(options) {
    var settings = $.extend({
        speed: 150,
        color: 'red'
    }, options);
    var self = $(this);
    var init_margin = self.css('margin-right');
    var color = self.css('border-color');
    var position = self.css('position');
    self.css({
        borderColor: settings.color,
        position: 'relative'
    });
    self.animate({'left': '-10px'}, settings.speed, function() {
        self.animate({'left': '10px'}, settings.speed, function() {
            self.animate({'left': '-10px'}, settings.speed, function() {
                self.animate({'left': init_margin}, settings.speed);
                self.css({
                    borderColor: color,
                    position: position
                });
            });
        });
    });
};


$(function() {
    var email_regex = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
    var name_regex = /^[0-9A-Za-z_]+$/;
    function valid_name(name) {
        return name.match(name_regex);
    }
    function valid_email(email) {
        return email.match(email_regex);
    }
    $('#copyright-violation-alert input').click(function() {
        $(this).parents('#copyright-violation-alert').css('visibility', 'hidden');
    });
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
        } else if (!valid_name(username)) {
            username.jump();
            valid = false;
            $('.error').html("Sorry but it seams that the username is is invalid").show();
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
            $('.error').html("Sorry but it seams that your email is invalid").show();
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
    // TODO: favorite clipart function
    $('.favorite-icon .favorite-remove, .favorite-icon .favorite-add').
        live('click', function() {
            var self = $(this);
            var parent = self.parent();
            if (!parent.data('ajax')) {
                var id = parent.data('id');
                if (id) {
                    self.parent().data('ajax', true);
                    var next = self.next();
                    var favs = parseInt(next.text());
                    if (self.has('.favorite-remove')) {
                        next.text(favs-1);
                        self.removeClass('.favorite-remove').
                            addClass('.favorite-add');
                    } else if (self.has('.favorite-add')) {
                        next.text(favs+1);
                        self.removeClass('.favorite-add').
                            addClass('.favorite-remove');
                    }
                    $.get('/toggle-favorite/' + id, function() {
                        /*
                          in handler:

                          SELECT email, notify FROM aiki_users, ocal_files
                          WHERE userid = upload_user AND ocal_files.id = <id>

                          SELECT count(*) FROM ocal_favs WHERE clipart_id = <id> AND
                          user_id = $this->user_id
                        */
                        parent.data('ajax', false);
                    });
                }
            }
            return false;
        });
    window.foo = function() {
        var self = $('#forget-password form');
        var root = self.parent();
        var error = root.find('.error').hide();
        var email = self.find('#email');
        if (!valid_email(email.val())) {
            email.jump();
            error.html('This email is invalid').show();
        } else {
            $.getJSON(self.attr('action'), {email: email.val()}, function(response) {
                root.html('<p>' + response.result + '</p>');
            });
        }
        return false;
    };
    $('#forget-password form').submit(function() {
        var self = $(this);
        var root = self.parent();
        var error = root.find('.error').hide();
        var email = self.find('#email');
        if (!valid_email(email.val())) {
            email.jump();
            error.html('This email is invalid').show();
        } else {
            $.getJSON(self.attr('action'), {email: email.val()}, function(response) {
                if (response.error) {
                    error.html(response.result).show();
                } else {
                    root.html('<p>' + response.result + '</p>');
                }
            });
        }
        return false;
    });
});
