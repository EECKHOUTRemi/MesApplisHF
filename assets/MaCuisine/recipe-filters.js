import $ from 'jquery';

/**
 * Initialise les filtres du fil des recettes (recherche par nom + filtres
 * ingrédients / ustensiles / catégories). select2 est enregistré globalement
 * par app.js, on se contente ici de le brancher sur les champs du formulaire.
 */
$(function () {
    // Ingrédients : recherche AJAX (même endpoint que le formulaire de recette).
    $('.filter-ingredients-select').each(function () {
        const $el = $(this);

        (($el.data('initial')) || []).forEach((o) => {
            $el.append(new Option(o.text, o.id, true, true));
        });

        $el.select2({
            placeholder: 'Tous les ingrédients',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            ajax: {
                url: $el.data('ajax-url'),
                dataType: 'json',
                delay: 250,
                processResults: (data) => ({
                    results: data.map((i) => ({ id: i.id, text: i.name })),
                }),
                cache: true,
            },
        });
    });

    // Ustensiles : options fournies par le contrôleur (data-options).
    $('.filter-utensils-select').each(function () {
        const $el = $(this);
        const options = $el.data('options') || [];
        const initial = ($el.data('initial') || []).map(String);

        options.forEach((o) => {
            const selected = initial.includes(String(o.id));
            $el.append(new Option(o.name, o.id, selected, selected));
        });

        $el.select2({ placeholder: 'Tous les ustensiles', allowClear: true, width: '100%' });
    });

    // Catégories : <option> rendues côté serveur.
    $('.filter-categories-select').each(function () {
        $(this).select2({ placeholder: 'Toutes les catégories', allowClear: true, width: '100%' });
    });
});
