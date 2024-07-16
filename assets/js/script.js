var $ = jQuery.noConflict();
$(document).ready(function () {


    $('#projectForm').submit(function (event) {
        $('.loading').show(); // Show loading spinner

        // Prevent default form submission to handle with AJAX
        event.preventDefault();

        // Disable the submit button to prevent multiple submissions
        $(this).find('input[type="submit"]').prop('disabled', true);

        // Simulate an AJAX form submission for debugging
        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: $(this).serialize(),
            timeout: 1800000,
            success: function (response) {


                // Replace the current form and other content with the new response
                $('body').html(response);

                // Hide loading spinner
                $('.loading').hide();

                // Re-enable the submit button
                $('#projectForm').find('input[type="submit"]').prop('disabled', false);
            },
            error: function (jqXHR, textStatus, errorThrown) {


                // Hide loading spinner in case of error
                $('.loading').hide();

                // Re-enable the submit button
                $('#projectForm').find('input[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Observer to detect the end of PHP processing
    var observer = new MutationObserver(function (mutations) {

        mutations.forEach(function (mutation) {
            if ($('#loading-complete').children().length > 0) {
                $('.loading').hide(); // Hide loading spinner
            }
        });
    });

    // Ensure that the target element exists before observing
    var target = document.getElementById('loading-complete');
    if (target) {
        observer.observe(target, { childList: true });
    } else {

    }
});



function exportTableToExcel() {
    const fileName = 'report-sheet.xlsx';
    const table = document.getElementById('projectTable');

    // Clone the table and remove elements with id 'no-jobs'
    const clone = table.cloneNode(true);
    const excludeElements = clone.querySelectorAll('#no-jobs');
    excludeElements.forEach(el => el.remove());

    // Log the cloned table to console for inspection
    console.log('Cloned Table:', clone);

    console.log(clone.innerHTML);

    // Remove any unwanted styles or attributes from table rows
    clone.querySelectorAll('tr').forEach(row => {
        row.removeAttribute('style'); // Remove any inline styles
    });

    // Convert the modified table to a workbook and export
    const wb = XLSX.utils.table_to_book(clone);
    console.log(wb);
    XLSX.writeFile(wb, fileName);
}




