
var $ = require('jquery');
//var upload_item = require('templates/upload-item.js')

var upload_item_template = require('./templates/upload-item.js');

$(function(){
  
  var $form = $('form.image-upload');
  var $upload_items = $('.upload-items',$form);
  var num = 0;
  
  $form.on('submit', function(e){
    e.preventDefault();
    // inspired by: http://stackoverflow.com/questions/166221/how-can-i-upload-files-asynchronously-with-jquery
    $.ajax({
        url: '/upload',
        type: 'POST',
        xhr: function() {  // custom xhr
            myXhr = $.ajaxSettings.xhr();
            // check if upload property exists
            if(myXhr.upload)
                myXhr.upload.addEventListener('progress', function(e){
                  var position = e.position || e.loaded;
                  var total = e.totalSize || e.total;
                  var percentage = Math.round( (position / total) * 100) ;
                  $('.progress .bar').css('width', percentage + '%')
                }, false);
            return myXhr;
        },
        //Ajax events
        beforeSend: function(){
          $('.progress').show();
        }
        , success: function(res){
          console.log(res);
          // success handler
          $('.progress').hide();
          $file_input = $('input.file-upload',$form);
          $file_input.replaceWith( $file_input.val('').clone( true ) );
          var num = $upload_items.children().length;
          $upload_items.empty();
          $('.message-center').append(
            '<div class="alert alert-success">'
              + '<strong> Success! </strong> Upload complete.'
            + '</div>')
          setTimeout(function(){
            $('.message-center').remove();
          },5000);
        }
        , error: function(e){
          // error handler
          $('.progress').hide();
          $('.message-center').append(
            '<div class="alert alert-error">'
              + '<strong> Error </strong> Sorry, looks like something went wrong. Try that again and if you still see this error, feel free to report this isue.'
            + '</div>')
          $upload_items.empty();
          console.log(e);
        }
        // Form data
        , data: new FormData($form[0])
        //Options to tell JQuery not to process data or worry about content-type
        , cache: false
        , contentType: false
        , processData: false
    });
    return false;
  })
  
  $('.file-select', $form).on('click', function(e){
    e.preventDefault();
    $('input.file-upload', $form).click();
    return false;
  });
  
  $('input.file-upload', $form).on('change', function(){
    $upload_items.empty();
    num = readFiles(this.files);
    if(num > 0 ) $('.btn-upload').removeClass('disabled');
    else $('.btn-upload').addClass('disabled');
  });
  
  function readFiles(files){
    var num = 0;
    for (var i = 0; i < files.length; i++) {
      var file = files[i];
      var imageType = /image\/svg\+xml/;
      if (!file.type.match(imageType)) continue;
      var reader = new FileReader();
      reader.onloadend = (function(num){
        return function(e) { loadImage(e.target.result, num); };
      })(num);
      reader.readAsDataURL(file);
      num++
    }
    return num;
  }
  
  function loadImage(src, id){
    var $img = $('<img>');
    $img[0].file = file;
    $img.on('load', function(){
      $item = $(upload_item_template);
      // $('input, textarea', $item).each(function(i, input){
      //   var $input = $(input);
      //   $input.attr('name', $input.attr('name') + '_' + id)
      //   console.log($input.attr('name'))
      // });
      
      $upload_items.append($item);
      $('.thumb-container',$item).append($img);
    })
    $img.attr('src', src);
  }
  
});