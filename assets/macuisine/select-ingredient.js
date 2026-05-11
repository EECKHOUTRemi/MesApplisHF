import $ from 'jquery';

$(function () {
    $('.ingredients-select').each(function () {
        const $el = $(this);
        $el.select2({
            placeholder: 'Sélectionnez un ingrédient',
            ajax: {
                url: $el.data('ajax-url'),
                dataType: 'json',
                delay: 250,
                processResults(data) {
                    return {
                        results: data.map(i => ({ id: i.id, text: i.name })),
                    };
                },
                cache: true,
            },
        });
    });
});
