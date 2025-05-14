jQuery(document).ready(function($) {
    // Start the quiz timer
    var startTime = new Date().getTime();
    var $timerDisplay = $('#qc-timer');
    var quizInterval = setInterval(function(){
         var now = new Date().getTime();
         var elapsed = now - startTime;
         $timerDisplay.text('Time: ' + (elapsed/1000).toFixed(3) + ' seconds');
    }, 50);
    
    // Auto next question functionality if enabled from admin settings
    var autoNext = $('#qc-quiz-container').data('auto-next');
    $('.qc-question input').on('change', function(){
         if (autoNext) {
             var $current = $(this).closest('.qc-question');
             $current.hide();
             $current.next('.qc-question').show();
         }
    });
    
    // Handle quiz submission via AJAX
    $('#qc-quiz-form').on('submit', function(e){
         e.preventDefault();
         clearInterval(quizInterval);
         var totalTime = new Date().getTime() - startTime;
         $('#qc_total_time').val(totalTime);
         
         // Serialize form data
         var formData = $(this).serializeArray();
         // Add the required action parameter so WordPress routes the AJAX request properly
         formData.push({name: 'action', value: 'qc_submit_quiz'});
         // Add quiz_id and total_time fields (if they aren't already)
         formData.push({name: 'quiz_id', value: $('#qc-quiz-container').data('quiz-id')});
         formData.push({name: 'total_time', value: totalTime});
         
         // For debugging, you can also log the formData in the console:
         console.log("Submitting quiz with data:", formData);
         
         $.post(qc_ajax_obj.ajaxurl, formData, function(response) {
             console.log("AJAX response:", response);
             if(response.success) {
                 $('#qc-result').html('<p>Correct: ' + response.data.correct + 
                     '<br>Wrong: ' + response.data.wrong + 
                     '<br>Total Time: ' + response.data.time + ' seconds</p>');
                 $('#qc-quiz-form').hide();
             } else {
                 $('#qc-result').html('<p>Error submitting quiz.</p>');
             }
         }).fail(function(jqXHR, textStatus, errorThrown) {
             console.log("AJAX error occurred:", textStatus, errorThrown);
             $('#qc-result').html('<p>AJAX error occurred.</p>');
         });
    });
});
