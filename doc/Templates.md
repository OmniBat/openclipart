# Templates

Class Template is use for templates. The class mustache as template engine. Templates are search in templates directory. First argument to constructor is template name which need to be html file with optional mustache tags in form ./templates/<NAME>.templates.

```php
new Template('main', null);
```

Second argument can be associative array where keys will be interpreted as mustache tags and it's values will be inserted into template file.

```php
new Template('main, array(
    'name' => 'John'
);
```

If template templates/main.template will have {{name}} the value John will be inserted into {{name}} tag.

Instead of array there can be function (closure) that will return the array, so it can contain logic that will pull those value from database directly or use a Model from MVC.

The template need to be rendered. There is render function but the system work with __toString function and modification to Slim that accept object that can be cast to string (have __toString method). So you can return instance of class Template from Slim router.

```php
$app->get('/', function() {
   return new Template('main', function() {
       return array(
           'name' => 'John'
       );
   });
});
```

this will render template ./templates/main.template as home page.


## Nesting Templates

The templates can be nested for this reason mustache partial are used. Dynamic nature of partials are not used because Templates are rendered from other way, from bottom to top. instead of key you need to use name of the partial and the value need to be another template.

```php
$app->get('/page1', function() {
    return new Template('main', function() {
        return array(
            'name' => 'John',
            'list' => new Template('list-items', function() {
                global $app;
                return array(
                    'items' => $app->db->get_array("SELECT * FROM items");
                );
           });
        );
    });
});
```

This more complicated example do couple more things it have 2 templates. main template need to have {{> list}} partial inside and template list-items.template need to have {{#items}} tag and inside tags from items mysql table. like

```html+jinja
<ul>
  {{#items}}
     <li>{{name}} - {{author}}</li>
  {{/items}}
</ul>
```

Assuming that items mysql table have name and author fields.

The main template can be something like

```html+jinja
<!DOCTYPE html>
<html>
  <head>
    <title>Example page</title>
  </head>
<body>
   <h1>Your name is {{name}}</h1>
   <p>This is a list</p>
   {{> list}}
</body>
<html>
```
