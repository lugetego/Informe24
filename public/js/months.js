document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit_curso_form');
    const errorMessage = document.getElementById('error-message');



    form.addEventListener('submit','change', function(event) {
        // Get the month and year values
        const month1 = parseInt(document.getElementById('cursos_fechaInicio').value, 10);
        const month2 = parseInt(document.getElementById('cursos_fechaFin').value, 10);
        const horas = parseInt(document.getElementById('cursos_horas').value, 10);

        // Compare the dates
        if ( month1 > month2) {
            // Prevent form submission

            event.preventDefault();
            errorMessage.textContent = 'El mes de inicio no puede ser mayor al mes de fin.';
            console.log('error');
            debugger

        } else {
            // Clear the error message
            const fechaInicioSelect = document.querySelector('#cursos_fechaInicio');
            const selectedValue = fechaInicioSelect.value;

            console.log('Selected Value:', selectedValue);
            errorMessage.textContent = '';

            debugger

        }

    });
});
