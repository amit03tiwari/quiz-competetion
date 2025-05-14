jQuery(document).ready(function($) {
    // When "Add Question" is clicked, obtain a new question block from the server.
    $("#add_question").on("click", function(e){
         e.preventDefault();
         var questionCount = $(".qc_question").length;
         $.post(qc_admin_obj.ajaxurl, {
             action: 'qc_get_new_question',
             question_index: questionCount,
             _ajax_nonce: qc_admin_obj.ajax_nonce
         }, function(response){
              if(response.success){
                 $("#qc_questions_container").append(response.data.html);
              } else {
                 alert("Error: " + response.data.message);
              }
         });
    });
    
    // When "Add Option" is clicked inside a question block...
    $(document).on("click", ".add_option", function(e){
         e.preventDefault();
         var $questionDiv = $(this).closest('.qc_question');
         var questionIndex = $questionDiv.data("question-index");
         var optionCount = $questionDiv.find(".qc_option").length;
         $.post(qc_admin_obj.ajaxurl, {
             action: 'qc_get_new_option',
             question_index: questionIndex,
             option_index : optionCount,
             _ajax_nonce: qc_admin_obj.ajax_nonce,
         }, function(response){
             if(response.success){
                 $questionDiv.find(".qc_options_container").append(response.data.html);
             } else {
                 alert("Error: " + response.data.message);
             }
         });
    });
});
