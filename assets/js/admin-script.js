jQuery(document).ready(function($) {
    // Tab switching functionality in the admin panel
    $('.qc-tab-link').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.qc-tab-link').removeClass('active');
        $(this).addClass('active');
        $('.qc-admin-tab').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Clone a new Question block when "Add Question" is clicked
    $("#add_question").on("click", function(e) {
        e.preventDefault();
        var qCount = $(".qc_question").length;
        var newQ = $(".qc_question:first").clone();
        newQ.attr("data-question-index", qCount);
        newQ.find("h3").html("Question " + (qCount + 1) + ' <a href="#" class="remove-question" onclick="jQuery(this).closest(\'.qc_question\').remove(); return false;">Remove</a>');
        // Clear inputs and update name attributes
        newQ.find("input, textarea, select").each(function() {
            var name = $(this).attr("name");
            if (name) {
                // Replace the digit(s) in the name string with qCount
                name = name.replace(/\d+/, qCount);
                $(this).attr("name", name);
                $(this).val("");
            }
        });
        $("#qc_questions_container").append(newQ);
    });

    // Clone a new Option block dynamically within a question
    $(document).on("click", ".add_option", function(e) {
        e.preventDefault();
        var $question = $(this).closest(".qc_question");
        var qIndex = $question.data("question-index");
        var oCount = $question.find(".qc_options_container p").length;
        var newOption = '<p>Option ' + (oCount + 1) + ': ' +
            '<input type="text" name="questions[' + qIndex + '][options][' + oCount + '][text]" required> ' +
            '&nbsp; Correct? <input type="checkbox" name="questions[' + qIndex + '][options][' + oCount + '][correct]" value="1"> ' +
            '<a href="#" class="remove-option" onclick="jQuery(this).parent().remove(); return false;">Remove</a>' +
            '</p>';
        $question.find(".qc_options_container").append(newOption);
    });
});
