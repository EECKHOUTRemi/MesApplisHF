import $ from 'jquery';

$(function () {
    $('.utensils-select').each(function () {
        const $el = $(this);
        const options = $el.data('options') || [];
        const initial = ($el.data('initial') || []).map(String);

        options.forEach(o => {
            const selected = initial.includes(String(o.id));
            $el.append(new Option(o.name, o.id, selected, selected));
        });

        $el.select2({
            placeholder: 'Sélectionnez ou créez un ustensile',
            allowClear: true,
            width: '100%',
            tags: true,
        });
    });
});
