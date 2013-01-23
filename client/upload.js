
var $ = require('jquery');
//var upload_item = require('templates/upload-item.js')

var upload_item_template = require('./templates/upload-item.js');

$(function(){
  
  var $form = $('form.image-upload');
  var num = 0;
  
  $form.on('submit', function(e){
    if(num === 0){
      e.preventDefault();
      return false;
    }
  })
  
  $('.file-select', $form).on('click', function(e){
    e.preventDefault();
    $('input.file-upload', $form).click();
    return false;
  });
  
  $('input.file-upload').on('change', function(){
    $('.upload-items',$form).empty();
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
      
      $('.upload-items', $form).append($item);
      $('.thumb-container',$item).append($img);
    })
    $img.attr('src', src);
  }
  
});