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
                if($this->model->hasProperty($attributeName)){
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
                'ondblclick' => "$('#$id_text').autocomplete( 'search', '').focus();",
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

                    if (this.term.length < 1){
                        return $( "<li>" ).append( item.label ).appendTo( ul );
                    }

                    var term = $.trim(this.term.replace(/[\\^$*+?.()|[\]{}]/g, "\\\$&")).replace(/\s+/g, "|");
                    var re = new RegExp( "(" + term + ")", "gi" );
                    var highlightedResult = item.label.replace( re, "<span class=\"found-string\">$1</span>" );

                    return $( "<li></li>" )
                        .data( "item.autocomplete", item ).append( "<a>" + highlightedResult + "</a>" ).appendTo( ul );
                    };
                }'),
                'change' => new JsExpression("
                        function( event, ui ) { 
                            if(ui.item == null){
                                event.preventDefault();
                                $('#$id').val('').change();
                                $('#$id_text').val('');                                
                                return false;
                            }
                            if ($('#$id').val()!=ui.item.id) {
                                $('#$id').val(ui.item.id).change();
                            }
                        }
                    "),
                'select' => new JsExpression("
                        function( event, ui ) {
                            if(ui.item.id == null){
                                event.preventDefault();
                                $('#$id').val('').change();
                                $('#$id_text').val($('#$id_text').val());
                                return false;
                            }
                            if ($('#$id').val()!=ui.item.id) {
                                $('#$id').val(ui.item.id).change();
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
            $input = '';

            $input .= '<div class="input-group">';
            $input .= Html::activeTextInput($this->model, $this->attribute, ArrayHelper::merge(
                $this->options,
                [
                    'onblur' => "$('#{$this->options['id']}').blur();",
                    'value' => $this->textValue,
                    'id' => $this->textInputId,
                ]
            ));
//            $input .= Html::a('<span class="glyphicon glyphicon-remove"></span>', null, [
//                'onclick' => "$('#{$this->textInputId}').val('')",
//                'class'=>'glyphicon glyphicon-remove form-control-feedback',
//            ]);

            // Кнопка отображения списка
            $input .= '<span class="input-group-btn">';
            $input .= Html::button('<span class="glyphicon glyphicon-chevron-down"></span>', [
                'onclick' => "$('#{$this->textInputId}').autocomplete( 'search', '').focus();",
                'class' => 'form-control btn btn-default', 'tabindex' => -1,
                'type' => 'button',
            ]);
            $input .= '</span>';
            $input .= '</div>';
            $input .= Html::activeHiddenInput($this->model, $this->attribute, $this->options);
//            $input .= Html::activeTextInput($this->model, $this->attribute, $this->options);

            return $input;
        } else {
            return Html::textInput($this->name, $this->value, $this->options);
        }
    }
}
