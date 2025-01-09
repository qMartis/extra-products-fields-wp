(function ($) {
  'use strict';
  const optionSetList = YAYE_CLIENT_DATA.OPTION_SET_LIST;
  const yayeSettings = YAYE_CLIENT_DATA.settings;
  $(document).ready(function ($) {

    // Add to cart by ajax
    $( 'form button.add_to_cart_button.ajax_add_to_cart' ).on( 'click', function() {
      if($('.yayextra-option-field-wrap').length > 0) {
        $( 'form.cart' ).trigger('submit');
      }
		} );

    $.each(optionSetList, function (_, optionSet) {
      if( 1 === parseInt(optionSet.status) ) {
        const optionSetId = optionSet.id;
        let referenceObject = {};
        $.each(optionSet.options, function (_, option) {
          $.each(option.logics, function (_, logic) {
            if(logic.option && logic.option.id) {
              if (logic.option.id in referenceObject) {
                referenceObject[logic.option.id].push(option);
              } else {
                Object.assign(referenceObject, { [logic.option.id]: [option] });
              }
            }
          });
        });
        $.each(optionSet.options, function (_, option) {
          if (option?.logics.length < 1) {
            showOption(option);
            return;
          }
  
          if (checkLogic(optionSetId, option)) {
            showOption(option);
          } else {
            hideOption(option);
            hideReferenceOption(option, referenceObject);
          }
  
          $.each(option.logics, function (_, logic) {
            createEvent(optionSetId, option, logic, referenceObject);
          });
        });
      }
    });

    // Show/hide adition description of Select option field.
    const selectOptionFields = $('.yayextra-option-field-wrap select');
    if (selectOptionFields.length > 0) {
      $.each(selectOptionFields, function (idx, el) {
        showHideAdditionDescSelectOpt(el);

        $(el).on('change', function () {
          showHideAdditionDescSelectOpt(this);
          getTotalCost();
        });
      });
    }

    // Catch option swatched Event
    const swatchOptionFields = $('.yayextra-option-field-swatches-label');
    if (swatchOptionFields.length > 0) {
      $.each(swatchOptionFields, function (idx, el) {
        const inputEl = $(el).siblings('input');
        const optId = $(inputEl).attr('data-opt-id');
        const optImg = $(inputEl).attr('data-opt-img');
        const prodImg = $(inputEl).attr('data-product-img');
        const optSetId = $(inputEl).attr('data-optset-id');

        let isMultiSelectable = false;
        const optionFieldWraps = $(el).parents('.yayextra-option-field-wrap');
        if (optionFieldWraps.length > 0) {
          const optFieldWrap = optionFieldWraps[0];
          isMultiSelectable =
            $(optFieldWrap).attr('data-multi-selectable') == 1 ? true : false;
        }

        // Check input is checked init ?
        if ($(inputEl).is(':checked')) {
          $(el).addClass('checked');

          showHideAdditionDescSwatchesButtonOpt(inputEl);

          //Change product image follow swatches image
          const optionData = getOptionData(optSetId, optId);
          const optionValChecked = $(inputEl).val();
          if (null !== optionData && 1 == optionData.isChangeImage) {
            const optionValues = optionData.optionValues;
            $.each(optionValues, function (idx, optVal) {
              if (
                'image' === optVal.swatchesType &&
                optionValChecked === optVal.value &&
                optId === optionData.id
              ) {
                replaceProductImage(optImg, true);
                return false;
              }
            });
          }
        }

        // Event click;
        $(el).on('click', function () {
          if (!$(inputEl).is(':checked')) {
            $(el).addClass('checked');
            $(inputEl).prop('checked', true);

            showHideAdditionDescSwatchesButtonOpt(inputEl);

            //Change product image follow swatches image
            const optionData = getOptionData(optSetId, optId);
            const optionValChecked = $(inputEl).val();
            if (null !== optionData && 1 == optionData.isChangeImage) {
              const optionValues = optionData.optionValues;
              $.each(optionValues, function (idx, optVal) {
                if (
                  'image' === optVal.swatchesType &&
                  optionValChecked === optVal.value &&
                  optId === optionData.id
                ) {
                  replaceProductImage(optImg, false);
                  return false;
                } else {
                  replaceProductImage(prodImg, false);
                }
              });
            }
          } else {
            $(el).removeClass('checked');
            $(inputEl).prop('checked', false);
          }

          // Remove class checked and uncheck other
          if (!isMultiSelectable) {
            const optSwatchesSiblings = $(el)
              .closest('.yayextra-opt-swatches')
              .siblings();
            if (optSwatchesSiblings.length > 0) {
              $.each(optSwatchesSiblings, function (idx, el1) {
                $(el1)
                  .find('.yayextra-option-field-swatches-label')
                  .removeClass('checked');
                if (
                  $(el1)
                    .find('input')
                    .is(':checked')
                ) {
                  $(el1)
                    .find('input')
                    .prop('checked', false);
                }
              });
            }
          }

          inputEl.trigger('change');
        });
      });
    }

    // Catch option button type Event
    const buttonOptions = $('.yayextra-option-button-label');
    if (buttonOptions.length > 0) {
      $.each(buttonOptions, function (idx, el) {
        // const labelEl = $(el).siblings('.yayextra-option-button-label');
        const inputEl = $(el).siblings('input');

        let isMultiSelectable = false;
        const optionFieldWraps = $(el).parents('.yayextra-option-field-wrap');
        if (optionFieldWraps.length > 0) {
          const optFieldWrap = optionFieldWraps[0];
          isMultiSelectable =
            $(optFieldWrap).attr('data-multi-selectable') == 1 ? true : false;
        }

        // Check input is checked init ?
        if ($(inputEl).is(':checked')) {
          $(el).addClass('checked');

          showHideAdditionDescSwatchesButtonOpt(inputEl);
        }

        // Event click;
        $(el).on('click', function () {
          if (!$(inputEl).is(':checked')) {
            $(el).addClass('checked');
            $(inputEl).prop('checked', true);

            showHideAdditionDescSwatchesButtonOpt(inputEl);
          } else {
            $(el).removeClass('checked');
            $(inputEl).prop('checked', false);
          }

          // Remove class checked and uncheck other
          if (!isMultiSelectable) {
            const optButtonSiblings = $(el)
              .closest('.yayextra-opt-button')
              .siblings();
            if (optButtonSiblings.length > 0) {
              $.each(optButtonSiblings, function (idx, el1) {
                $(el1)
                  .find('.yayextra-option-button-label')
                  .removeClass('checked');
                if (
                  $(el1)
                    .find('input')
                    .is(':checked')
                ) {
                  $(el1)
                    .find('input')
                    .prop('checked', false);
                }
              });
            }
          }

          inputEl.trigger('change');
        });
      });
    }

    const datePickerFields = $('.yayextra-date-picker');
    if (datePickerFields.length > 0) {
      $.each(datePickerFields, function (idx, el) {
        const datePickerFieldId = $(el).attr('id');
        $('#' + datePickerFieldId).datepicker();
      });
    }

    const timePickerInputFields = $('.yayextra-time-picker-input');
    if (timePickerInputFields.length > 0) {
      $.each(timePickerInputFields, function (idx, el) {
        $(el).on('click', function () {
          const timePickerFieldInputId = $(this).attr('id');
          const timePickerDivClass = '.' + timePickerFieldInputId;
          $(timePickerDivClass).show();
          $(timePickerDivClass).datetimepicker({
            baseCls: 'yayextra-datetimepicker',
            date: new Date(),
            viewMode: 'HM',
            onDateChange: function () {
              const datetime = new Date(this.getValue()).toLocaleTimeString(
                [],
                {
                  hour: '2-digit',
                  minute: '2-digit',
                }
              );
              $('#' + timePickerFieldInputId).val(datetime);
              $('#' + timePickerFieldInputId).trigger('change');
            },
            onOk: function () {
              $(timePickerDivClass).hide();
            },
          });

          // Hide timepicker icon plus, minus
          const timePickerIconI = $('.yayextra-option-field-wrap').find(
            '.yayextra-datetimepicker i'
          );
          if (timePickerIconI.length > 0) {
            $.each(timePickerIconI, function (idx, el) {
              $(el)
                .parent('td')
                .hide();
            });
          }
        });
      });
    }

    $(document).mousedown(function (e) {
      if ($(e.target).closest('.yayextra-datetimepicker').length === 0) {
        $('.yayextra-time-picker').hide();
      }
    });

    // Change product image - start
    // $('.yayextra-change-product-img-btn').on('click', function() {
    //   $('#yayextra-change-product-image').trigger('click');
    // });

    // // Call Product image form submit when the image had uploaded.
    // if (document.getElementById('yayextra-change-swatches-image') !== null) {
    //   document
    //     .getElementById('yayextra-change-product-image')
    //     .addEventListener('change', (event) => {
    //       $('#yayextra-change-product-image-form').trigger('submit');
    //     });
    // }

    // $('#yayextra-change-product-image-form').on('submit', function(e) {
    //   e.preventDefault();

    //   let fileInputElement = document.getElementById(
    //     'yayextra-change-product-image'
    //   );
    //   let fileName = fileInputElement.files[0].name;

    //   if (fileName == '') {
    //     alert('Upload your image');
    //     return false;
    //   } else {
    //     $.ajax({
    //       url: YAYE_CLIENT_DATA.ajax_url,
    //       type: 'POST',
    //       processData: false,
    //       contentType: false,
    //       data: new FormData(this),
    //       beforeSend: function() {
    //         yayeSpinner('woocommerce-product-gallery', true);
    //       },
    //       success: function(response) {
    //         if (response.success) {
    //           // yayeNotification(response.data.msg, 'site-content');
    //           location.reload();
    //         } else {
    //           alert(response.data.msg);
    //         }
    //         yayeSpinner('woocommerce-product-gallery', false);
    //       }
    //     });
    //     return false;
    //   }
    //   return false;
    // });
    // Change product image - end

    // Change swatches image - start
    // $('.yayextra-change-swatches-image-label').on('click', function() {
    //   let swatchWrapEl = $(this).parents('div.yayextra-opt-swatches');
    //   const optSetId = $(this).attr('data-opt-set-id');
    //   const optId = $(this).attr('data-opt-id');
    //   const optVal = $(this).attr('data-opt-val');

    //   // Clear html if exist after create new.
    //   if ($('.yayextra-change-swatches-image-wrap').length > 0) {
    //     $('.yayextra-change-swatches-image-wrap').remove();
    //   }

    //   var fileEl = document.createElement('div');
    //   fileEl.className = 'yayextra-change-swatches-image-wrap';
    //   fileEl.innerHTML =
    //     '<input type="file" name="yayextra-change-swatches-image" id="yayextra-change-swatches-image" accept="' +
    //     YAYE_CLIENT_DATA.mime_image_types +
    //     '" />';
    //   $('body').append(fileEl);

    //   $('#yayextra-change-swatches-image').trigger('click');

    //   // Call Product image form submit when the image had uploaded.
    //   if (document.getElementById('yayextra-change-swatches-image') !== null) {
    //     document
    //       .getElementById('yayextra-change-swatches-image')
    //       .addEventListener('change', (event) => {
    //         let formData = new FormData();
    //         let fileData = $('#yayextra-change-swatches-image').prop('files');
    //         formData.append('file', fileData[0]);
    //         formData.append('action', 'yaye_handle_image_swatches_upload');
    //         formData.append('nonce', YAYE_CLIENT_DATA.nonce);
    //         formData.append('optSetId', optSetId);
    //         formData.append('optId', optId);
    //         formData.append('optVal', optVal);

    //         let fileName = fileData[0].name;
    //         if (fileName == '') {
    //           alert('Upload your image');
    //           return false;
    //         } else {
    //           $.ajax({
    //             url: YAYE_CLIENT_DATA.ajax_url,
    //             type: 'POST',
    //             processData: false,
    //             contentType: false,
    //             data: formData,
    //             beforeSend: function() {
    //               yayeSpinner(swatchWrapEl[0], true);
    //             },
    //             success: function(response) {
    //               if (response.success) {
    //                 yayeNotification(response.data.msg, 'site-content');
    //                 $(swatchWrapEl[0])
    //                   .find('.yayextra-option-field-swatches-label')
    //                   .removeAttr('style')
    //                   .attr(
    //                     'style',
    //                     'background-image: url(' + response.data.img_url + ')'
    //                   );
    //               } else {
    //                 alert(response.data.msg);
    //               }
    //               yayeSpinner(swatchWrapEl[0], false);
    //             }
    //           });
    //           return false;
    //         }
    //         return false;
    //       });
    //   }
    // });

    // Change swatches image - end

    // Validate value of Text field - start
    const optionTextFields = $('.yayextra-option-field-wrap input.yayextra-text');
    if (optionTextFields.length > 0) {
      $.each(optionTextFields, function (_, el) {
        $(el).on('keyup', function () {
          let elMessage = $(this).siblings('.error-message-text');
          if ($(this).val().length > 0) {
            elMessage.html('').hide();
            const textFormat = $(this).attr('data-text-format');
            if ('url' === textFormat) {
              validateUrl($(this).val(), elMessage);
            } else if ('email' === textFormat) {
              validateEmail($(this).val(), elMessage);
            }
          }
          getTotalCost();
        });
      });
    }
    // Validate value of Text field - end

    // Event checkbox, radio click
    const optionCheckboxRadioFields = $(
      '.yayextra-option-field-wrap input[type="radio"], .yayextra-option-field-wrap input[type="checkbox"]'
    );
    if (optionCheckboxRadioFields.length > 0) {
      $.each(optionCheckboxRadioFields, function (_, el) {
        $(el).on('click', function () {
          getTotalCost();
        });
      });
    }

    // Event swatches change
    const optionSwatchesButtonFields = $(
      '.yayextra-option-field-wrap .yayextra-opt-swatches input,.yayextra-option-field-wrap .yayextra-opt-button input'
    );
    if (optionSwatchesButtonFields.length > 0) {
      $.each(optionSwatchesButtonFields, function (_, el) {
        $(el).on('change', function () {
          getTotalCost();
        });
      });
    }

    // Event Textarea click
    const optionTextareaFields = $('.yayextra-option-field-wrap textarea');
    if (optionTextareaFields.length > 0) {
      $.each(optionTextareaFields, function (_, el) {
        $(el).on('keyup', function () {
          getTotalCost();
        });
      });
    }

    // Event input click
    const optionNumberFields = $('.yayextra-option-field-wrap input[type="number"]');
    if (optionNumberFields.length > 0) {
      $.each(optionNumberFields, function (_, el) {
        $(el).on('keyup', function () {
          getTotalCost();
        });
      });
    }

    // Event input yayextra-date-picker/yayextra-time-picker-input change
    const optionDateTimeFields = $(
      '.yayextra-option-field-wrap input.yayextra-date-picker, .yayextra-option-field-wrap input.yayextra-time-picker-input'
    );
    if (optionDateTimeFields.length > 0) {
      $.each(optionDateTimeFields, function (_, el) {
        $(el).on('change', function () {
          getTotalCost();
        });
      });
    }

    // Event product quantity input
    $('.quantity input.qty').on('change', function () {
      getTotalCost();
    });

    // Init total cost
    getTotalCost();

     // Events of variations product
    if($('.product form.variations_form').length > 0){
      let productVariationForm = $('.product form.variations_form');
      let variationProductsMeta = getVariationProductsMeta();
      // if ($('.yayextra-total-price').length > 0 || $('.yayextra-extra-subtotal-price').length > 0) {
        let prodVariationData = {};
        productVariationForm.find('.variations select').each(function() {
          let attrName = $(this).data('attribute_name') || $(this).attr('name');
          let value = $(this).val() || '';
          prodVariationData[ attrName ] = value;

          // Event select;
          $(this).on('change', function () {
            let valueE = $(this).val() || '';
            if( valueE ){
              prodVariationData[ attrName ] = valueE;

              const variationProductPrice = updateTotalPriceByVariationProduct(variationProductsMeta, prodVariationData);

              // Change option addition price for percetage type
              if ( variationProductPrice ) {
                changeAdditionCostByVariationProduct(variationProductPrice)
              } else {
                changeAdditionCostByVariationProduct(0)
              }
            } else {
              changeAdditionCostByVariationProduct(0)
              $('.yayextra-total-price .total-price').attr('data-total-price', 0);
            }

            getTotalCost();
          })
        });

        if( Object.keys(prodVariationData).length === 0 ) {
          $('.yayextra-total-price .total-price').attr('data-total-price', 0);
          // getTotalCost();
        } else {
          for (const attr in prodVariationData) {
            if( '' === prodVariationData[attr] ){
              $('.yayextra-total-price .total-price').attr('data-total-price', 0);
              // getTotalCost();
              break;
            }
          }
        }

        const variationProductPriceInit = updateTotalPriceByVariationProduct(variationProductsMeta, prodVariationData);

        // Change option addition price for percetage type
        if ( variationProductPriceInit ) {
          changeAdditionCostByVariationProduct(variationProductPriceInit)
        } else {
          changeAdditionCostByVariationProduct(0)
        }

        getTotalCost();
      // }
    }

    // Init set Visibility option id into hidden field
    getVisibilityOption();

  });

  function showOption(option) {
    if (option && option.id) {
      const optionWrapper = $("[data-option-field-id='" + option.id + "']");

      let fieldDataObjs = optionWrapper.find('input');
      if ('dropdown' == option.type.value) {
        fieldDataObjs = optionWrapper.find('select');
      } else if ('textarea' == option.type.value) {
        fieldDataObjs = optionWrapper.find('textarea');
      }
  
      if (optionWrapper.length) {
        optionWrapper.show();
        for (const fieldDataObj of fieldDataObjs) {
          $(fieldDataObj).prop('disabled', false);
        }
      }
  
      getTotalCost();
      getVisibilityOption();
    }
  }
  function showReferenceOption(optionSetId, option, referenceObject) {
    if (option?.id in referenceObject) {
      referenceObject[option.id].forEach((ref) => {
        if (checkLogic(optionSetId, ref)) showOption(ref);
      });
    }
    getVisibilityOption();
  }

  function hideOption(option) {
    if( option && option.id ) {
      const optionWrapper = $("[data-option-field-id='" + option.id + "']");

      let fieldDataObjs = optionWrapper.find('input');
      if ('dropdown' == option.type.value) {
        fieldDataObjs = optionWrapper.find('select');
      } else if ('textarea' == option.type.value) {
        fieldDataObjs = optionWrapper.find('textarea');
      }
  
      if (optionWrapper.length) {
        optionWrapper.hide();
        for (const fieldDataObj of fieldDataObjs) {
          $(fieldDataObj).prop('disabled', true);
        }
      }
      resetElement(option);
  
      getTotalCost();
      getVisibilityOption();
    }
  }
  function hideReferenceOption(option, referenceObject) {
    if (option?.id in referenceObject) {
      referenceObject[option.id].forEach((ref) => {
        const el = $("[data-option-field-id='" + ref.id + "']");
        if (el.css('display') !== 'none') hideOption(ref);
      });
    }
    getVisibilityOption();
  }
  function resetElement(option) {
    const currentField = $("[data-option-field-id='" + option.id + "']");
    const currentFieldType = currentField.attr('data-option-field-type');
    if (
      'text' === currentFieldType ||
      'number' === currentFieldType ||
      'date_picker' === currentFieldType ||
      'time_picker' === currentFieldType
    ) {
      const element = currentField.find('input');
      element.val('');
    } else if ( 'textarea' === currentFieldType ) {
      const element = currentField.find('textarea');
      element.val('');
    } else if (['radio', 'checkbox', 'button', 'swatches', 'button_multi', 'swatches_multi'].includes(currentFieldType)) {
      const elements = currentField.find('input');
      $.each(elements, function (index, element) {
        if(option.optionValues[index] && option.optionValues[index].isDefault) {
          element.checked = option.optionValues[index].isDefault;
        }
      });
    } else if ('dropdown' === currentFieldType) {
      const element = currentField.find('select');
      const findingDefault = option.optionValues.find(
        (option) => option.isDefault
      );
      if (findingDefault !== undefined) element.val(findingDefault.value);
    }
  }
  function checkTextLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    let logicVal = logic.value;
    if( undefined === logicVal|| ( Array.isArray(logicVal) && null === logicVal[0])) {
      logicVal = "";
    }

    const fieldLogic = $(
      "input[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const fieldLogicVal = fieldLogic.val();

    if ('match' === logic.comparation.value) {
      if (fieldLogicVal === logicVal) return true;
    }
    if ('not_match' === logic.comparation.value) {
      if (fieldLogicVal !== logicVal) return true;
    }
    if ('contains' === logic.comparation.value && fieldLogicVal) {
      if (fieldLogicVal.includes(logicVal)) return true;
    }
    return false;
  }
  function checkTextareaLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    let logicVal = logic.value;
    if( undefined === logicVal || ( Array.isArray(logicVal) && null === logicVal[0])) {
      logicVal = "";
    }

    const fieldLogic = $(
      "textarea[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const fieldLogicVal = fieldLogic.val();

    if ('match' === logic.comparation.value) {
      if (fieldLogicVal === logicVal) return true;
    }
    if ('not_match' === logic.comparation.value) {
      if (fieldLogicVal !== logicVal) return true;
    }
    if ('contains' === logic.comparation.value && fieldLogicVal) {
      if (fieldLogicVal.includes(logicVal)) return true;
    }
    return false;
  }
  function checkNumberLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const fieldLogic = $(
      "input[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const fieldLogicVal = parseFloat(fieldLogic.val());

    if ('NaN' == fieldLogicVal) return false;

    if ('equal' === logic.comparation.value) {
      if (fieldLogicVal == parseFloat(logic.value)) return true;
    } else if ('less_than' === logic.comparation.value) {
      if (fieldLogicVal < parseFloat(logic.value)) return true;
    } else if ('greater_than' === logic.comparation.value) {
      if (fieldLogicVal > parseFloat(logic.value)) return true;
    } else if ('less_than_or_equal' === logic.comparation.value) {
      if (fieldLogicVal <= parseFloat(logic.value)) return true;
    } else if ('greater_than_or_equal' === logic.comparation.value) {
      if (fieldLogicVal >= parseFloat(logic.value)) return true;
    }
    return false;
  }
  function checkCheckboxLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const checkboxes = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "][]']:checked"
    );
    if (checkboxes.prop('disabled')) return false;

    let values = [];
    if (Array.isArray(logic.value)) {
      values = logic.value.map((v) => v.value);
    }
    const findingResult = Array.from(checkboxes).find((checkbox) =>
      values.includes($(checkbox).val())
    );
    if (logic.comparation.value === 'is_one_of') {
      if (findingResult === undefined) return false;
      return true;
    } else {
      if (findingResult === undefined) return true;
      return false;
    }
  }
  function checkRadioLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const radio = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "]']:checked"
    );
    if (radio.prop('disabled')) return false;

    const logicValue = Array.isArray(logic.value) ? logic.value[0].value : logic.value.value;
    if (logic.comparation.value === 'is') {
      return logicValue === radio.val();
    }
    return logicValue !== radio.val();
  }
  function checkButtonLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const buttons = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "][]']:checked"
    );

    let buttonVals = [];
    $.each(buttons, function () {
      buttonVals.push($(this).val());
    });

    if (buttons.prop('disabled')) return false;

    const logicValue = Array.isArray(logic.value) ? logic.value[0].value : logic.value.value;
    const findingResult = buttonVals.includes(logicValue);

    if (logic.comparation.value === 'is') {
      if (findingResult) return true;
      return false;
    }

    return !findingResult;
  }
  function checkButtonMultiLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const buttones = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "][]']:checked"
    );
    if (buttones.prop('disabled')) return false;

    let values = [];
    if (Array.isArray(logic.value)) {
      values = logic.value.map((v) => v.value);
    }
    const findingResult = Array.from(buttones).find((button) =>
      values.includes($(button).val())
    );
    if (logic.comparation.value === 'is_one_of') {
      if (findingResult === undefined) return false;
      return true;
    } else {
      if (findingResult === undefined) return true;
      return false;
    }
  }
  function checkSelectLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const fieldLogic = $(
      "select[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const fieldLogicVal = String(fieldLogic.val());
    const logicValue = Array.isArray(logic.value) ? logic.value[0].value : logic.value.value;
    if ('is' === logic.comparation.value) {
      if (fieldLogicVal == String(logicValue)) return true;
    } else if ('is_not' === logic.comparation.value) {
      if (fieldLogicVal != String(logicValue)) return true;
    }
    return false;
  }
  function checkSwatchesLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const swatches = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "][]']:checked"
    );

    let swatchesVals = [];
    $.each(swatches, function () {
      swatchesVals.push($(this).val());
    });

    if (swatches.prop('disabled')) return false;

    const logicValue = Array.isArray(logic.value) ? logic.value[0].value : logic.value.value;
    const findingResult = swatchesVals.includes(logicValue);

    if (logic.comparation.value === 'is') {
      if (findingResult) return true;
      return false;
    }

    return !findingResult;
  }
  function checkSwatchesMultiLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const swatches = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      optionId +
      "][]']:checked"
    );
    if (swatches.prop('disabled')) return false;

    let values = [];
    if (Array.isArray(logic.value)) {
      values = logic.value.map((v) => v.value);
    }
    const findingResult = Array.from(swatches).find((swatch) =>
      values.includes($(swatch).val())
    );
    if (logic.comparation.value === 'is_one_of') {
      if (findingResult === undefined) return false;
      return true;
    } else {
      if (findingResult === undefined) return true;
      return false;
    }
  }
  function checkDatePickerLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const fieldLogic = $(
      "input[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const fieldLogicVal = fieldLogic.val();

    const startDate = new Date(logic.value.from_date).toLocaleDateString();
    const endDate = new Date(logic.value.to_date).toLocaleDateString();
    const currentDate = new Date(fieldLogicVal).toLocaleDateString();

    if ('between' === logic.comparation.value) {
      if (startDate <= currentDate && currentDate <= endDate) return true;
    }
    if ('not_between' === logic.comparation.value) {
      if (startDate > currentDate || currentDate > endDate) return true;
    }
    return false;
  }
  function checkTimePickerLogic(optionSetId, logic) {
    const optionId =
      typeof logic.option !== 'undefined'
        ? logic.option.value
        : logic.optionId.value;

    const fieldLogic = $(
      "input[name='option_field_data[" + optionSetId + '][' + optionId + "]']"
    );

    if (fieldLogic.prop('disabled')) return false;

    const startTime = convertTimeAMPM(
      new Date(logic.value.from_time).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      })
    );
    const endTime = convertTimeAMPM(
      new Date(logic.value.to_time).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
      })
    );
    const currentTime = convertTimeAMPM(fieldLogic.val());

    if (null === currentTime) return false;

    if ('between' === logic.comparation.value) {
      if (startTime <= currentTime && currentTime <= endTime) return true;
    }
    if ('not_between' === logic.comparation.value) {
      if (startTime > currentTime || currentTime > endTime) return true;
    }
    return false;
  }

  function getLogicResult(optionSetId, logic) {
    if( !logic.option || ! logic.option.type || !logic.option.type.value ) return false;

    if (logic.option.type.value === 'text')
      return checkTextLogic(optionSetId, logic);
    if (logic.option.type.value === 'textarea')
      return checkTextareaLogic(optionSetId, logic);
    if (logic.option.type.value === 'number')
      return checkNumberLogic(optionSetId, logic);
    if (logic.option.type.value === 'checkbox')
      return checkCheckboxLogic(optionSetId, logic);
    if (logic.option.type.value === 'radio')
      return checkRadioLogic(optionSetId, logic);
    if (logic.option.type.value === 'button')
      return checkButtonLogic(optionSetId, logic);
    if (logic.option.type.value === 'button_multi')
      return checkButtonMultiLogic(optionSetId, logic);
    if (logic.option.type.value === 'dropdown')
      return checkSelectLogic(optionSetId, logic);
    if (logic.option.type.value === 'swatches')
      return checkSwatchesLogic(optionSetId, logic);
    if (logic.option.type.value === 'swatches_multi')
      return checkSwatchesMultiLogic(optionSetId, logic);
    if (logic.option.type.value === 'date_picker')
      return checkDatePickerLogic(optionSetId, logic);
    if (logic.option.type.value === 'time_picker')
      return checkTimePickerLogic(optionSetId, logic);
  }
  function checkLogic(optionSetId, option) {
    const { matchType, displayType } = option;
    let result = false;
    if (matchType.value === 'any') {
      const check = option.logics.find((logic) => {
        return getLogicResult(optionSetId, logic);
      });
      if (check !== undefined) result = true;
    } else {
      const check = option.logics.find((logic) => {
        return !getLogicResult(optionSetId, logic);
      });
      if (check === undefined) result = true;
    }
    if (displayType.value === 'display') return result;

    return !result;
  }
  function createCheckboxEvent(optionSetId, option, logic, referenceObject) {
    const checkboxes = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "][]']"
    );

    $.each(checkboxes, function (_, checkbox) {
      $(checkbox).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createRadioEvent(optionSetId, option, logic, referenceObject) {
    const radios = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );
    $.each(radios, function (_, radio) {
      $(radio).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createButtonEvent(optionSetId, option, logic, referenceObject) {
    const radios = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "][]']"
    );
    $.each(radios, function (_, radio) {
      $(radio).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createTextEvent(optionSetId, option, logic, referenceObject) {
    const texts = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(texts, function (_, text) {
      $(text).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createTextareaEvent(optionSetId, option, logic, referenceObject) {
    const textareas = $(
      "textarea[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(textareas, function (_, textarea) {
      $(textarea).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createNumberEvent(optionSetId, option, logic, referenceObject) {
    const numbers = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(numbers, function (_, number) {
      $(number).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createSelectEvent(optionSetId, option, logic, referenceObject) {
    const selects = $(
      "select[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(selects, function (_, select) {
      $(select).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createSwatchesEvent(optionSetId, option, logic, referenceObject) {
    const swatches = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "][]']"
    );
    $.each(swatches, function (_, swatch) {
      $(swatch).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createDatePickerEvent(optionSetId, option, logic, referenceObject) {
    const datepickers = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(datepickers, function (_, text) {
      $(text).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createTimePickerEvent(optionSetId, option, logic, referenceObject) {
    const timepickers = $(
      "input[name='option_field_data[" +
      optionSetId +
      '][' +
      logic.option.value +
      "]']"
    );

    $.each(timepickers, function (_, text) {
      $(text).on('change', function () {
        if (checkLogic(optionSetId, option)) {
          showOption(option);
          showReferenceOption(optionSetId, option, referenceObject);
        } else {
          hideOption(option);
          hideReferenceOption(option, referenceObject);
        }
      });
    });
  }
  function createEvent(optionSetId, option, logic, referenceObject) {
    const optionType = logic?.option?.type?.value;
    if (optionType === 'checkbox')
      createCheckboxEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'radio')
      createRadioEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'button' || optionType === 'button_multi')
      createButtonEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'text')
      createTextEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'textarea')
      createTextareaEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'number')
      createNumberEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'dropdown')
      createSelectEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'swatches' || optionType === 'swatches_multi')
      createSwatchesEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'date_picker')
      createDatePickerEvent(optionSetId, option, logic, referenceObject);
    if (optionType === 'time_picker')
      createTimePickerEvent(optionSetId, option, logic, referenceObject);
  }
  function showHideAdditionDescSelectOpt(el) {
    const optionId = $(el).attr('id');
    const value = el.value.replaceAll('"', '\\"');
    const optionDes = $("[data-option-field-id='" + optionId + "']").find(
      '.yayextra-addition-des-dropdown[data-opt-id="' +
      optionId +
      '"][data-opt-val="' +
      value +
      '"]'
    );

    if (optionDes.length > 0) {
      optionDes.show();
      optionDes.siblings('p.yayextra-addition-des-dropdown').hide();
    } else {
      $("[data-option-field-id='" + optionId + "']")
        .find('.yayextra-addition-des-dropdown')
        .hide();
    }
  }

  function showHideAdditionDescSwatchesButtonOpt(el) {
    const optionId = $(el).attr('data-opt-id');
    const value = $(el).val().replaceAll('"', '\\"');

    const optionDes = $("[data-option-field-id='" + optionId + "']").find(
      '.yayextra-addition-des-swatches-button[data-opt-id="' +
      optionId +
      '"][data-opt-val="' +
      value +
      '"]'
    );

    if (optionDes.length > 0) {
      optionDes.show();
      optionDes.siblings('p.yayextra-addition-des-swatches-button').hide();
    } else {
      $("[data-option-field-id='" + optionId + "']")
        .find('.yayextra-addition-des-swatches-button')
        .hide();
    }
  }

  function convertTimeAMPM(time) {
    if (time) {
      var hours = Number(time.match(/^(\d+)/)[1]);
      var minutes = Number(time.match(/:(\d+)/)[1]);
      var AMPM = time.match(/\s(.*)$/)[1];
      if ((AMPM == 'PM' || AMPM == 'pm') && hours < 12) hours = hours + 12;
      if ((AMPM == 'AM' || AMPM == 'am') && hours == 12) hours = hours - 12;
      var sHours = hours.toString();
      var sMinutes = minutes.toString();
      if (hours < 10) sHours = '0' + sHours;
      if (minutes < 10) sMinutes = '0' + sMinutes;
      return sHours + ':' + sMinutes;
    }
    return null;
  }

  function yayeNotification(messages, containerClass) {
    let notifyHtml =
      '<div class="yayextra-notification"><div class="yayextra-notification-content">' +
      messages +
      '</div></div>';

    $('.' + containerClass).after(notifyHtml);
    setTimeout(function () {
      $('.yayextra-notification').addClass('NslideDown');
      $('.yayextra-notification').remove();
    }, 1500);
  }

  function yayeSpinner(containerClass, isShow) {
    let spinnerHtml = '<div class="yayextra-spinner">';
    spinnerHtml +=
      '<svg class="woocommerce-spinner" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">';
    spinnerHtml +=
      '<circle class="woocommerce-spinner__circle" fill="none" stroke-width="5" stroke-linecap="round" cx="50" cy="50" r="30"></circle>';
    spinnerHtml += '/<svg>';
    spinnerHtml += '</div>';
    if (isShow) {
      if (typeof containerClass === 'string') {
        $('.' + containerClass).append(spinnerHtml);
      } else {
        $(containerClass).append(spinnerHtml);
      }
    } else {
      $('.yayextra-spinner').remove();
    }
  }

  function getOptionData(optSetId, optionId) {
    let result = null;
    if (optionSetList.length > 0) {
      $.each(optionSetList, function (_, optionSet) {
        if( optSetId == optionSet.id ) {
          const optionList = optionSet.options;
          if (optionList.length > 0) {
            $.each(optionList, function (_, option) {
              if (optionId == option.id) {
                result = option;
                return false;
              }
            });
            if ( result ) return false;
          }
        } 
      });
    }
    return result;
  }

  function replaceProductImage(imageUrl, setTimeOut) {
    let productGalleryImgList = $('.woocommerce-product-gallery').find(
      '.woocommerce-product-gallery__image'
    );

    if (productGalleryImgList.length > 0) {
      let productGalleryImgFirst = productGalleryImgList[0];
      $(productGalleryImgFirst).attr('data-thumb', imageUrl);
      $(productGalleryImgFirst)
        .find('a')
        .attr('href', imageUrl);

      let productGalleryImgEl = $(productGalleryImgFirst).find(
        'img.wp-post-image'
      );
      $(productGalleryImgEl).attr('src', imageUrl);
      $(productGalleryImgEl).attr('data-src', imageUrl);
      $(productGalleryImgEl).attr('data-large_image', imageUrl);
      $(productGalleryImgEl).attr('srcset', imageUrl);

      if (true === setTimeOut) {
        setTimeout(function () {
          let productGalleryImgZoomEl = $(productGalleryImgFirst).find(
            'img.zoomImg'
          );
          if (productGalleryImgZoomEl.length > 0) {
            $(productGalleryImgZoomEl).attr('src', imageUrl);
          }
        }, 1000);
      } else {
        let productGalleryImgZoomEl = $(productGalleryImgFirst).find(
          'img.zoomImg'
        );
        if (productGalleryImgZoomEl.length > 0) {
          $(productGalleryImgZoomEl).attr('src', imageUrl);
        }
      }
    }

    let productGalleryThumbList = $('.woocommerce-product-gallery').find(
      '.flex-control-thumbs li'
    );

    if (productGalleryThumbList.length > 0) {
      let productGalleryThumbFirst = productGalleryThumbList[0];
      $(productGalleryThumbFirst)
        .find('img')
        .attr('src', imageUrl);
    }
  }

  function validateEmail(mail, elMessage) {
    if (
      /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(
        mail
      )
    ) {
      elMessage.html('').hide();
      return true;
    }
    elMessage.html('You have entered an invalid email address!').show();
    return false;
  }

  function validateUrl(url, elMessage) {
    let pattern = new RegExp(
      '^(https?:\\/\\/)?' + // protocol
      '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
      '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
      '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
      '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
      '(\\#[-a-z\\d_]*)?$',
      'i'
    ); // fragment locator
    if (pattern.test(url)) {
      elMessage.html('').hide();
      return true;
    }
    elMessage.html('You have entered an invalid URL!').show();
    return false;
  }

  function checkLogicAction(optionSetId, action) {
    if( !action || !action.conditions || !action.matchType.value ) return false;

    let conditions = action.conditions;
    let matchType = action.matchType.value;

    if (conditions.length === 0) {
      return false;
    }

    if ('any' === matchType) {
      let result = false;
      $.each(conditions, function (_, condition) {
        const logic = getLogicActionResult(optionSetId, condition);
        if (logic) {
          result = true;
          return;
        }
      });

      return result;
    } else {
      // 'all' === $matchType
      let result = true;
      $.each(conditions, function (_, condition) {
        const logic = getLogicActionResult(optionSetId, condition);
        if (!logic) {
          result = false;
          return;
        }
      });
      return result;
    }
  }

  function getLogicActionResult(optionSetId, condition) {
    if( !condition || !condition.type || !condition.type.value ) return false;

    if ( ! $(".yayextra-option-field-wrap[data-option-field-id=" + condition.optionId.id + "]" ).is(':visible')) return false;
    
    const optionType = condition.type.value;

    if (optionType === 'text') {
      return checkTextLogic(optionSetId, condition);
    }
    if (optionType === 'textarea') {
      return checkTextareaLogic(optionSetId, condition);
    }
    if (optionType === 'number') {
      return checkNumberLogic(optionSetId, condition);
    }
    if (optionType === 'checkbox') {
      return checkCheckboxLogic(optionSetId, condition);
    }
    if (optionType === 'radio') {
      return checkRadioLogic(optionSetId, condition);
    }
    if (optionType === 'button') {
      return checkButtonLogic(optionSetId, condition);
    }
    if (optionType === 'button_multi') {
      return checkButtonMultiLogic(optionSetId, condition);
    }
    if (optionType === 'dropdown') {
      return checkSelectLogic(optionSetId, condition);
    }
    if (optionType === 'swatches') {
      return checkSwatchesLogic(optionSetId, condition);
    }
    if (optionType === 'swatches_multi') {
      return checkSwatchesMultiLogic(optionSetId, condition);
    }
    if (optionType === 'date_picker') {
      return checkDatePickerLogic(optionSetId, condition);
    }
    if (optionType === 'time_picker') {
      return checkTimePickerLogic(optionSetId, condition);
    }
  }

  function getFeeDiscountFromAction(optionSets) {
    let feeDiscountArray = [];
    $.each(optionSets, function (_, optionSet) {
      if( 1 == parseInt(optionSet.status) ){
        const optionSetId = optionSet.id;
        $.each(optionSet.actions, function (_, action) {
          if (checkLogicAction(optionSetId, action)) {
            const subActions = action.subActions;
  
            if (subActions.length > 0) {
              $.each(subActions, function (_, subAction) {
                const actionVal = ( undefined !== subAction.subActionValueYayCurrency ) ? subAction.subActionValueYayCurrency : 0;
                feeDiscountArray.push({
                  type: subAction.subActionType.value,
                  name: subAction.subActionName,
                  value: actionVal
                });
              });
            }
          }
        });
      }
    });

    let feeDiscountSum = 0;

    $.each(feeDiscountArray, function (_, feeDiscount) {
      let price =
        'add_fee' === feeDiscount.type
          ? parseFloat(feeDiscount.value)
          : parseFloat(-feeDiscount.value);

      feeDiscountSum += price;
    });

    return { feeDiscounts: feeDiscountArray, feeDiscountTotal: feeDiscountSum };
  }

  function yayeNumberFormat(number, decimals, decPoint, thousandsSep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number;
    var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    var sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep;
    var dec = typeof decPoint === 'undefined' ? '.' : decPoint;
    var s = '';

    var toFixedFix = function toFixedFix(n, prec) {
      if (('' + n).indexOf('e') === -1) {
        return +(Math.round(n + 'e+' + prec) + 'e-' + prec);
      } else {
        var arr = ('' + n).split('e');
        var sig = '';
        if (+arr[1] + prec > 0) {
          sig = '+';
        }
        return (+(
          Math.round(+arr[0] + 'e' + sig + (+arr[1] + prec)) +
          'e-' +
          prec
        )).toFixed(prec);
      }
    };

    // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
      s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
      s[1] = s[1] || '';
      s[1] += new Array(prec - s[1].length + 1).join('0');
    }

    return s.join(dec);
  }

  function getTotalCost() {
    let additionCostSum = 0;
    let feeDiscounts = 0;

    let quantityProduct = 0;
    if ( $('.quantity input.qty').length > 0 ) {
      quantityProduct = $('.quantity input.qty').val();
    } else if ( $('.quantity select.qty').length > 0 ) {
      quantityProduct = $('.quantity select.qty').val();
    }

    if (
      $('.yayextra-total-price').length > 0 ||
      $('.yayextra-extra-subtotal-price').length > 0
    ) {
      const optionFields = $('body').find('.yayextra-option-field-wrap');
      const optHasVals = [
        'radio',
        'dropdown',
        'checkbox',
        'button',
        'button_multi',
        'swatches',
        'swatches_multi',
      ];

      if (optionFields.length > 0) {
        $.each(optionFields, function (idx, el) {
          if ($(el).is(':visible')) {
            // Sum value of value option field
            const optType = $(el).attr('data-option-field-type');
            if (optHasVals.includes(optType)) {
              if ('dropdown' === optType) {
                let valEls = $(el).find('select');
                if (valEls.length > 0) {
                  $.each(valEls, function (_, valEl) {
                    let selectedOption = $(valEl).find('option:selected');
                    if (selectedOption.length > 0) {
                      additionCostSum += parseFloat(
                        selectedOption.attr('data-addition-cost')
                      );
                    }
                  });
                }
              } else {
                let valEls = $(el).find('input');
                if (valEls.length > 0) {
                  $.each(valEls, function (_, valEl) {
                    if ($(valEl).is(':checked')) {
                      additionCostSum += parseFloat(
                        $(valEl).attr('data-addition-cost')
                      );
                    }
                  });
                }
              }
            }
          }
        });
      }

      feeDiscounts = getFeeDiscountFromAction(optionSetList);
    }

    // Total price - start
    if ($('.yayextra-total-price').length > 0) {
      totalPriceHtml(
        'yayextra-total-price',
        additionCostSum,
        feeDiscounts,
        quantityProduct
      );
    }
    // Total price - end

    // Extra subtotal price - start
    if ($('.yayextra-extra-subtotal-price').length > 0) {
      totalPriceHtml(
        'yayextra-extra-subtotal-price',
        additionCostSum,
        feeDiscounts,
        quantityProduct
      );
    }
    // Extra subtotal price  - end
  }

  function totalPriceHtml(
    elementWrap,
    additionCostSum,
    feeDiscounts,
    quantityProduct
  ) {
    let dataTokenReplace = $('.' + elementWrap + ' .total-price').attr(
      'data-token-replace'
    );

    let currentPrice = $('.' + elementWrap + ' .total-price').attr(
      'data-total-price'
    );

    let totalPrice =
      (additionCostSum + parseFloat(currentPrice)) * parseInt(quantityProduct) + parseFloat(feeDiscounts.feeDiscountTotal);
    let totalPriceFormat = yayeNumberFormat(
      totalPrice,
      YAYE_CLIENT_DATA.wc_currency.decimals,
      YAYE_CLIENT_DATA.wc_currency.decimal_separator,
      YAYE_CLIENT_DATA.wc_currency.thousand_separator
    );

    let priceString = $('.' + elementWrap + ' .total-price').html();
    let stringReplace = yayeNumberFormat(
      parseFloat(dataTokenReplace),
      YAYE_CLIENT_DATA.wc_currency.decimals,
      YAYE_CLIENT_DATA.wc_currency.decimal_separator,
      YAYE_CLIENT_DATA.wc_currency.thousand_separator
    );

    // Update data-token-replace for later
    $('.' + elementWrap + ' .total-price').attr(
      'data-token-replace',
      totalPrice
    );

    let priceStringFinal = priceString.replace(stringReplace, totalPriceFormat);

    $('.' + elementWrap + ' .total-price').html(priceStringFinal);
  }

  function getVariationProductsMeta(){
    if($('.product form.variations_form').length > 0){
      let variationsMeta = $('.product form.variations_form').attr('data-product_variations');
      return JSON.parse(variationsMeta);
    }
    return null;
  }

  // Return price of variable product or null
  function updateTotalPriceByVariationProduct(variationProductsMeta, prodVariationData){
    if(variationProductsMeta !== null && variationProductsMeta.length > 0) {
      for(let variationProduct of variationProductsMeta){
        const findVariationProd = findVariationProduct(variationProduct, prodVariationData);
        if(findVariationProd){
          $('.yayextra-total-price .total-price').attr('data-total-price', parseFloat(variationProduct.display_price));
          // getTotalCost();
          return parseFloat(variationProduct.display_price);
        }
      };
    }
    return null;
  }

  // Find variation product 
  function findVariationProduct(variationProduct, prodVariationData) {
    let result = true;
    let attrs = variationProduct.attributes;
    for (const attr in attrs) {
      if( ! prodVariationData[attr] ) {
        result = false
        break;
      } else {
        if( attrs[attr] && attrs[attr] !== prodVariationData[attr] ) {
          result = false
          break;
        }
      }
    }

    return result;
  }
  
  function changeAdditionCostByVariationProduct( variationProductPrice ) {
    let optionAdditionPercentageCostEls = $('.option-addition-percentage-cost')
    if(optionAdditionPercentageCostEls.length > 0){
      for (const valOptLabel of optionAdditionPercentageCostEls) {
        const optionValId   = $(valOptLabel).attr('data-opt-val-id')
        const optionCostOrg = $(valOptLabel).attr('data-option-org-cost')
        const additionCost  = parseFloat( optionCostOrg ) * variationProductPrice / 100;
      
        // update data-addition-cost for input element
        const inputEl = $('[id="' + optionValId + '"]')
        if(inputEl.length > 0){
          $(inputEl[0]).attr('data-addition-cost', additionCost)
        } else { // Case for Dropdown option
          const selectEl = $('option[data-opt-val-id="' + optionValId + '"]')
          if(selectEl.length > 0){
            $(selectEl[0]).attr('data-addition-cost', additionCost)
          }
        }

        // update data-addition-cost for label element
        let additionCostFormat = yayeNumberFormat(
          additionCost,
          YAYE_CLIENT_DATA.wc_currency.decimals,
          YAYE_CLIENT_DATA.wc_currency.decimal_separator,
          YAYE_CLIENT_DATA.wc_currency.thousand_separator
        );

        let dataTokenReplace = $(valOptLabel).attr(
          'data-option-org-cost-token-replace'
        );

        let priceString = $(valOptLabel).html();
        let stringReplace = yayeNumberFormat(
          parseFloat(dataTokenReplace),
          YAYE_CLIENT_DATA.wc_currency.decimals,
          YAYE_CLIENT_DATA.wc_currency.decimal_separator,
          YAYE_CLIENT_DATA.wc_currency.thousand_separator
        );

        // Update data-token-replace for later
        $(valOptLabel).attr(
          'data-option-org-cost-token-replace',
          additionCost
        );

        let priceStringFinal = priceString.replace(stringReplace, additionCostFormat);

        $(valOptLabel).html(priceStringFinal);

      }
    }
  }

  function getVisibilityOption() {
    let optionFieldList = $('form').find('.yayextra-option-field-wrap:visible'); 
    let optionIdList = [];
    $.each(optionFieldList, function (_, optField) {
      const optId = $(optField).attr('data-option-field-id');
      optionIdList.push(optId);
    })

    // Set option id to hidden field to php handle
    $('input[name="yaye_visibility_option_list"]').val(optionIdList.toString());
  }

})(jQuery);
