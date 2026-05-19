import $ from 'jquery';

$(function () {
    $('.utensils-select').each(function () {
        $(this).select2({
            placeholder: 'Sélectionnez les ustensiles',
            allowClear: true,
            width: '100%',
        });
    });
});
