import $ from 'jquery';

const UNITS = [
    { value: 'kg', label: 'kilogramme.s' },
    { value: 'g', label: 'gramme.s' },
    { value: 'mg', label: 'miligramme.s' },
    { value: 'l', label: 'litre.s' },
    { value: 'ml', label: 'mililitre.s' },
    { value: 'cc', label: 'cuillère.s à café' },
    { value: 'cs', label: 'cuillère.s à soupe' },
    { value: 'p', label: 'pincée.s' },
    { value: 'u', label: 'unité.s' },
];

function buildTagRow(name, id) {
    const key = String(id);
    const $row = $('<div class="row g-2 align-items-center mt-2 ingredient-tag-row"></div>');
    $row.attr('data-tag-id', key);

    const $label = $('<div class="col-12 col-md-4 fw-semibold"></div>').text(name + ' :');

    const $qtyInput = $('<input type="number" class="form-control" placeholder="Qté">')
        .attr('name', `extras[${key}][qty]`);
    const $qty = $('<div class="col-6 col-md-4"></div>').append($qtyInput);

    const $select = $('<select class="form-select"></select>')
        .attr('name', `extras[${key}][unit]`);
    UNITS.forEach(u => $select.append($('<option></option>').val(u.value).text(u.label)));
    const $unit = $('<div class="col-6 col-md-4"></div>').append($select);

    return $row.append($label, $qty, $unit);
}

$(function () {
    $('.ingredients-select').each(function () {
        const $el = $(this);
        const $extras = $el.closest('.col-12').find('.ingredient-extras');

        $el.select2({
            placeholder: 'Sélectionnez un ingrédient',
            tags: true,
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

        $el.on('select2:select', function (e) {
            const data = e.params.data;

            const exists = $extras.children().filter(function () {
                return String($(this).data('tagId')) === String(data.id);
            }).length > 0;

            if (!exists) {
                $extras.append(buildTagRow(data.text, data.id));
            }
        });

        $el.on('select2:unselect', function (e) {
            const data = e.params.data;
            $extras.children().filter(function () {
                return String($(this).data('tagId')) === String(data.id);
            }).remove();
        });
    });
});
