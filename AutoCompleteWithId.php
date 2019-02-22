<?php

namespace pravda1979\AutoCompleteWithId;

use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * AutoCompleteWithId renders an autocomplete jQuery UI widget.
 *
 * For example:
 *
 * with ajax request
 * ```php
 * echo $form->field($model, 'attribute')->widget(AutoCompleteWithId::className(), [
 * 'clientOptions' => [
 *      'source' => Url::to(['/controller/autocomplete'])
 * ]
 * ])
 * ```
 *
 * or default with ajax request(Url::to(['/attibute/autocomplete']))
 * ```php
 * echo $form->field($model, 'attribute_id')->widget(AutoCompleteWithId::className());
 * ```
 *
 * or with array
 * ```php
 * echo $form->field($model, 'attribute_id')->widget(AutoCompleteWithId::className(), [
 * 'clientOptions' => [
 *      'source' => [
 *          ['id' => 1, 'label' => 'Label 1'],
 *          ['id' => 2, 'label' => 'Label 2'],
 *          ...
 *      ]
 * ]
 * ])
 * ```
 **/
class AutoCompleteWithId extends \yii\jui\AutoComplete
{
    public $textValue;
    public $textInputId;

    /**
     * Initializes the widget.
     * If you override this method, make sure you call the parent implementation first.
     */
    public function init()
    {
        if ($this->hasModel()) {
            if (!isset($this->options['id'])) {
                $this->options['id'] = Html::getInputId($this->model, $this->attribute);
            }

            $modelName = Inflector::id2camel(str_replace('_id', '', $this->attribute), '_');

            if (!isset($this->options['textValue'])) {
                $attributeName = lcfirst($modelName);
                if ($this->model->hasProperty($attributeName)) {
                    $value = $this->model->$attributeName ? $this->model->$attributeName->getFullName() : null;
                    $this->textValue = $value;
                }
            }

            $id = $this->options['id'];
            $id_text = $this->textInputId = "text-" . $this->options['id'];

            $this->options = ArrayHelper::merge([
                'id' => $id,
                'class' => 'form-control',
                'oninput' => new JsExpression("if ($('#$id').val()!='') $('#$id').val('').change();"),
                'ondblclick' => "$('#$id_text').autocomplete( 'search').focus();",
            ], $this->options);

            $route = Inflector::camel2id($modelName);
//            \Yii::trace($route,'$route');

            $this->clientOptions = ArrayHelper::merge([
                'delay' => 100,
                'source' => Url::to(["/$route/autocomplete"]),
                'minLength' => 0,
                'autoFocus' => true,
                'create' => new JsExpression('function( event, ui ) {
                    $("#' . $id_text . '").autocomplete( "instance" )._renderItem = function( ul, item ) {
                    
                    if(item.info !== undefined){
                        return $( "<li class=\"ui-state-disabled\"></li>" ).data( "item.autocomplete", item.info ).append( "<div>" + item.info.label + "</div>" ).appendTo( ul );
                    }
                    
                    if (this.term.length < 1){
                        return $( "<li></li>" ).data( "item.autocomplete", item ).append( "<div>" + item.label + "</div>" ).appendTo( ul );
                    }

                    var term = $.trim(this.term.replace(/[\\^$*+?.()|[\]{}]/g, "\\\$&")).replace(/\s+/g, "|");
                    var re = new RegExp( "(" + term + ")", "gi" );
                    var highlightedResult = item.label.replace( re, "<span class=\"found-string\">$1</span>" );

                    return $( "<li></li>" )
                        .data( "item.autocomplete", item ).append( "<div>" + highlightedResult + "</div>" ).appendTo( ul );
                    };
                }'),
                'change' => new JsExpression("
                        function( event, ui ) {
                            if(ui.item == null){
                                event.preventDefault();
                                $('#$id').val('').change();
                                $('#$id_text').val('');                                
                                $('#".$id_text."_btn_clear').hide('slow');
                                return false;
                            }
                            if ($('#$id').val()!=ui.item.id) {
                                $('#$id').val(ui.item.id).change();
                                $('#".$id_text."_btn_clear').show('slow');
                            }
                        }
                    "),
                'select' => new JsExpression("
                        function( event, ui ) {
                            if(ui.item.id == undefined){
                                event.preventDefault();
                                $('#$id').val('').change();
                                $('#$id_text').val('');
                                $('#".$id_text."_btn_clear').hide('slow');
                                return false;
                            }
                            if ($('#$id').val()!=ui.item.id) {
                                $('#$id').val(ui.item.id).change();
                                $('#".$id_text."_btn_clear').show('slow');
                            }
                        }
                    "),
            ], $this->clientOptions);
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        echo $this->renderWidget();
        $this->registerWidget('autocomplete', $this->textInputId);
    }

    /**
     * Renders the AutoComplete widget.
     * @return string the rendering result.
     */
    public function renderWidget()
    {
        if ($this->hasModel()) {

            $btnText= Html::activeTextInput($this->model, $this->attribute, ArrayHelper::merge(
                $this->options,
                [
                    'onkeyup' => "if($('#{$this->textInputId}').val()==''){ $('#".$this->textInputId."_btn_clear').hide('slow') }else{ $('#".$this->textInputId."_btn_clear').show('slow') };",
                    'onblur' => "$('#{$this->options['id']}').blur();",
                    'value' => $this->textValue,
                    'id' => $this->textInputId,
                ]
            ));

            // Кнопка отображения списка
            $btnSearch = Html::a('<span class="glyphicon glyphicon-chevron-down"></span>', null, [
                'onclick' => "$('#{$this->textInputId}').autocomplete( 'search').focus();",
                'class' => 'btn btn-default', 'tabindex' => -1,
                'type' => 'button',
            ]);

            // Кнопка очистки значения
            $btnClear = Html::a('<span class="glyphicon glyphicon-remove"></span>', null, [
                'id' => $this->textInputId.'_btn_clear',
                'onclick' => "$('#{$this->textInputId}').val('').change().keyup(); $('#{$this->options['id']}').val('').change();",
                'class' => 'btn btn-warning', 'tabindex' => -1,
                'type' => 'button',
                'style'=>'display: none'
            ]);
            $btnId= Html::activeHiddenInput($this->model, $this->attribute, $this->options);

            return " 
            <div class=\"input-group\">
                $btnId
                $btnText
                <span class=\"input-group-btn\">
                    $btnClear
                    $btnSearch
                  </span>
            </div>
        ";

            return $input;
        } else {
            return Html::textInput($this->name, $this->value, $this->options);
        }
    }
}
