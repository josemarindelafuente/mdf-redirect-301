(function () {
    function getRulesBody() {
        return document.getElementById('mdf-rules-body');
    }

    function nextIndex() {
        var rows = document.querySelectorAll('#mdf-rules-body tr');
        return rows.length;
    }

    function createRow(index) {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="mdf_rules[' + index + '][source]" class="regular-text" placeholder="* o ruta/origen"></td>' +
            '<td><input type="url" name="mdf_rules[' + index + '][destination]" class="large-text" placeholder="https://destino.com"></td>' +
            '<td class="mdf-checkbox-cell"><input type="checkbox" name="mdf_rules[' + index + '][is_active]" value="1" checked></td>' +
            '<td class="mdf-remove-cell"><button type="button" class="button mdf-remove-rule">Quitar</button></td>';
        return tr;
    }

    function renumberRows() {
        var rows = document.querySelectorAll('#mdf-rules-body tr');
        rows.forEach(function (row, idx) {
            var fields = row.querySelectorAll('input');
            fields.forEach(function (field) {
                var name = field.getAttribute('name');
                if (!name) {
                    return;
                }
                field.setAttribute('name', name.replace(/mdf_rules\[\d+\]/, 'mdf_rules[' + idx + ']'));
            });
        });
    }

    document.addEventListener('click', function (event) {
        var addButton = event.target.closest('#mdf-add-rule');
        if (addButton) {
            var body = getRulesBody();
            if (!body) {
                return;
            }
            body.appendChild(createRow(nextIndex()));
        }

        var removeButton = event.target.closest('.mdf-remove-rule');
        if (removeButton) {
            var row = removeButton.closest('tr');
            if (row) {
                row.remove();
                renumberRows();
            }
        }
    });
})();
