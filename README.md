Yii2 Jui AutoComplete with Id field widget
==========================================
Yii2 Jui AutoComplete with Id field widget

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pravda1979/yii2-autocomplete-with-id-widget "*"
```

or add

```
"pravda1979/yii2-autocomplete-with-id-widget": "*"
```

to the require section of your `composer.json` file.

test
Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= $form->field($model, 'attribute_id')->widget(AutoCompleteWithId::className()); ?>```
