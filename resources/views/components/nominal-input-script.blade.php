<script>
    document.addEventListener('DOMContentLoaded', () => {
        const nominalInputs = document.querySelectorAll('[data-nominal-input]');

        if (!nominalInputs.length) {
            return;
        }

        const onlyDigits = (value) => value.replace(/\D/g, '');

        const formatNominal = (value) => {
            const digits = onlyDigits(value).replace(/^0+(?=\d)/, '');

            if (!digits) {
                return '';
            }

            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        nominalInputs.forEach((input) => {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('autocomplete', 'off');
            input.value = formatNominal(input.value);

            input.addEventListener('input', () => {
                input.value = formatNominal(input.value);
            });
        });

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', () => {
                nominalInputs.forEach((input) => {
                    input.value = onlyDigits(input.value);
                });
            });
        });
    });
</script>
