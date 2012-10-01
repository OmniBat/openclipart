
// plugin that indicate error in a field (move field from left to right with red color)
$.fn.jump = function(options) {
    var settings = $.extend({
        speed: 600,
        color: 'red'
    }, options);
    var self = $(this);
    var left, color, position;
    if (self.data('animate')) {
        left = self.data('left');
        color = self.data('color');
        position = self.data('position');
        self.stop();
    } else {
        left = self.css('left');
        color = self.css('border-color');
        position = self.css('position');
        self.data('color', color);
        self.data('position', position);
        self.data('left', left);
    }
    self.css({
        borderColor: settings.color,
        position: 'relative'
    });
    var time = settings.speed/4;
    self.data('animate', true);
    self.animate({'left': '-10px'}, time, function() {
        self.animate({'left': '10px'}, time, function() {
            self.animate({'left': '-10px'}, time, function() {
                self.animate({'left': left}, time, function() {
                    self.data('animate', false);
                }).css({
                    borderColor: color,
                    position: position
                });
            });
        });
    });
    return self;
};

$.fn.bg = function() {
    return this.each(function() {
        var self = $(this);
        self.css('background-image', 'url(' + self.attr('src') + ')').attr('src', '');
        return self;
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
    // Fix placeholders
    if (!Modernizr.input.placeholder) {
        $('input[placeholder]').each(function() {
            var input = $(this);
            input.wrap(function() {
                return $('<span/>').addClass('placeholder').css({
                    width: input.width(),
                    height: input.height()
                });
            });
            var placeholder = input.attr('placeholder');
            $('<span/>').text(placeholder).addClass('label').insertBefore(input);
            input.removeAttr('placeholder');
        }).focus(function() {
            $(this).prev().hide();
        }).blur(function() {
            $(this).prev().show();
        });
    }
    // allow only digits
    var change;
    var resolution = $('#resolution').keypress(function(e) {
        var self = $(this);
        var new_val = self.val();
        if (self.data('value') != new_val) {
            self.change();
            self.data('value', new_val);
        }
        if (!String.fromCharCode(e.which).match(/[0-9]/)) {
            return false;
        }
    }).change(function() {
        var self = $(this);
        var lossy = $(this).parents('#lossy');
        var link = lossy.find('li a');
        var href = link.attr('href');
        var resolution = $(this).val();
        if (resolution) {
            if (+resolution > 3840) {
                self.jump().val(3840);
                var error = lossy.find('.error').show();
                setTimeout(function() {
                    error.fadeOut();
                }, 2000);
            }
            link.attr('href', href.replace(/(\image\/)[0-9]+/, '$1' + resolution));
        }
    });

    if ($.browser.chrome || $.browser.webkit) {
        resolution.keyup(function(e) {
            if (!e.charCode) {
                $(this).trigger('keypress', e);
            }
        });
    }
    /*

    (function(button) {
        var url = 'https://plus.google.com/share?url=' + encodeURIComponent(location);
        button.attr('href', url).click(function() {
            window.open(url,'', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
            return false;
        });
    })($('.gplus_button'));
    */



    var img = $('#viewimg img, #shutterstock img').bg();

    rpc({url: '/rpc/main', error: function(e) {
            alert(e.message || e);
    }})(function(main) {

        function editable() {
            var tag_list = $('.tags ul');
            var tags = tag_list.find('li').detach();
            var finish = false;
            var clipart = tag_list.data('clipart');
            tag_list = tag_list.tagit({
                allowSpaces: true,
                triggerKeys: ['enter', 'comma', 'tab'],
                onTagClicked: $.noop,
                animate: false,
                onTagAdded: function(event, tag) {
                    if (finish) {
                        main.add_tag(clipart, $(tag).find('.tagit-label').text())($.noop);
                    }
                },
                onTagRemoved: function(event, tag) {
                    main.remove_tag(clipart, $(tag).find('.tagit-label').text())($.noop);
                }
            });
            tags.each(function() {
                var a = $(this).find('a');
                tag_list.tagit("createTag", a.text()).
                    tagit('widget').find('.tagit-label:last')
                    .attr('href', a.attr('href'));
            });
            finish = true;
            $('dd:eq(2), #view h2').mouseover(function() {
                if (!$(this).find('textarea').length) {
                    $(this).addClass('inline-selected');
                }
            }).mouseout(function() {
                $(this).removeClass('inline-selected');
            }).click(function() {
                var self = $(this);
                if (!self.find('textarea').length) {
                    var content = self.text();
                    self.data('original', content);
                    var inline = '<textarea>' + content + '</textarea><a class="save button">Save</a><a class="cancel">Cancel</a>';
                    self.html(inline).find('.button').button();
                }
                self.removeClass('inline-selected');
            }).filter('dd').wrapInner('<p/>');

            $('dd:eq(2) .cancel, #view h2 .cancel').live('click', function() {
                var parent = $(this).parent();
                parent.html(parent.data('original'));
                return false;
            });
            $('dd:eq(2) .button').live('click', function() {
                var self = $(this);
                var text = self.prev().val();
                var parent = self.parent();
                var original = parent.data('original');
                if (text == original) {
                    parent.html(text);
                } else {
                    main.set_description(clipart, text)(function(result) {
                        parent.html(result ? text : original);
                    });
                }
            });

            $('h2 .button').live('click', function() {
                var self = $(this);
                var text = self.prev().val();
                var parent = self.parent();
                var original = parent.data('original');
                if (text == original) {
                    parent.html(text);
                } else {
                    main.set_title(clipart, text)(function(result) {
                        parent.html(result ? text : original);
                    });
                }
            });
        }

        if ($('.editable').length) {
            editable();
        }


        /*
          var urls = [
          "http://s7.addthis.com/js/250/addthis_widget.js#username=boobalooasync=1&domready=1",
          "//api.flattr.com/js/0.6/load.js?mode=auto&uid=fabricatorz&popout=1&category=Images"];
          $.each(urls, function(_, url) {
          $.getScript(url);
          });
        */

        var login_form = $('#login-dialog');
        $('#login-register-language #login-link').click(function() {
            login_form.fadeIn();
            return false;
        });
        $('#login-dialog .ui-overlay, #register-dialog .ui-overlay').click(function() {
            $(this).parent().hide();
        });
        $('#login-dialog #submit').click(function() {
            var login = $('#login').val();
            var pass = $('#password').val();
            main.login(login, pass)(function(ret) {
                if (ret) {
                    // TODO: detect if user own a clipart or is librarian
                    $('#container').addClass('logged');
                    editable();
                    $('#login-dialog').fadeOut();
                } else {

                }
            });
            return false;
        });
        
        $('#logout').click(function() {
            main.logout()(function() {
                $('#container').removeClass('editable logged');
            });
            return false;
        });

    });
});
