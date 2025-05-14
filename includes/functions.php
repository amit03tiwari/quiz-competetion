<?php
if (!defined('ABSPATH')) exit;

function qc_save_quiz() {
    global $wpdb;
    
    if (!isset($_POST['qc_nonce_field']) || !wp_verify_nonce($_POST['qc_nonce_field'], 'qc_save_quiz_nonce')) {
       echo '<div class="error"><p>Security check failed.</p></div>';
       return;
    }
    if (!current_user_can('manage_options')) {
       echo '<div class="error"><p>Insufficient permissions.</p></div>';
       return;
    }
    
    $quiz_title = isset($_POST['quiz_title']) ? sanitize_text_field($_POST['quiz_title']) : '';
    $quiz_desc = isset($_POST['quiz_desc']) ? sanitize_textarea_field($_POST['quiz_desc']) : '';
    $max_time = isset($_POST['max_time']) ? intval($_POST['max_time']) : 60;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $questions_to_show = isset($_POST['questions_to_show']) ? intval($_POST['questions_to_show']) : 0;
    $auto_next = isset($_POST['auto_next']) ? 1 : 0;
    
    // Retrieve raw datetime values from the datetime-local inputs.
    $raw_submission_start = isset($_POST['submission_start']) ? sanitize_text_field($_POST['submission_start']) : '';
    $raw_submission_end = isset($_POST['submission_end']) ? sanitize_text_field($_POST['submission_end']) : '';
    
    // Convert the datetime-local values to a proper MySQL DATETIME format
    // Example: "2023-10-15T14:30" becomes "2023-10-15 14:30:00"
    $submission_start = '';
    $submission_end = '';
    if (!empty($raw_submission_start)) {
        // Replace "T" with a space and append ":00" if not already present.
        $submission_start = str_replace("T", " ", $raw_submission_start);
        if (!preg_match('/:\d{2}$/', $submission_start)) {
            $submission_start .= ":00";
        }
    }
    if (!empty($raw_submission_end)) {
        $submission_end = str_replace("T", " ", $raw_submission_end);
        if (!preg_match('/:\d{2}$/', $submission_end)) {
            $submission_end .= ":00";
        }
    }
    
    if (empty($quiz_title)) {
       echo '<div class="error"><p>Quiz title is required.</p></div>';
       return;
    }
    
    if (!isset($_POST['questions']) || !is_array($_POST['questions']) || count($_POST['questions']) == 0) {
       echo '<div class="error"><p>At least one question is required.</p></div>';
       return;
    }
    
    $table_quizzes = $wpdb->prefix . 'qc_quizzes';
    $editing = (isset($_POST['quiz_id']) && intval($_POST['quiz_id']) > 0);
    if ($editing) {
        $quiz_id = intval($_POST['quiz_id']);
        $result = $wpdb->update($table_quizzes, array(
            'title'              => $quiz_title,
            'description'        => $quiz_desc,
            'max_time'           => $max_time,
            'randomize_questions'=> $randomize_questions,
            'questions_to_show'  => $questions_to_show,
            'auto_next'          => $auto_next,
            'submission_start'   => $submission_start,
            'submission_end'     => $submission_end
        ), array('id' => $quiz_id));
        if ($result === false) {
           echo '<div class="error"><p>Error updating quiz.</p></div>';
           return;
        }
        qc_delete_quiz_questions($quiz_id);
    } else {
        $result = $wpdb->insert($table_quizzes, array(
            'title'              => $quiz_title,
            'description'        => $quiz_desc,
            'max_time'           => $max_time,
            'randomize_questions'=> $randomize_questions,
            'questions_to_show'  => $questions_to_show,
            'auto_next'          => $auto_next,
            'submission_start'   => $submission_start,
            'submission_end'     => $submission_end
        ));
        if ($result === false) {
            echo '<div class="error"><p>Error saving quiz.</p></div>';
            return;
        }
        $quiz_id = $wpdb->insert_id;
    }
    
    // Save questions and options
    $table_questions = $wpdb->prefix . 'qc_questions';
    $table_options   = $wpdb->prefix . 'qc_options';
    foreach ($_POST['questions'] as $q_index => $question) {
       $question_text = isset($question['text']) ? sanitize_text_field($question['text']) : '';
       if (empty($question_text)) {
          echo '<div class="error"><p>Question ' . ($q_index + 1) . ' text is required.</p></div>';
          continue;
       }
       $question_type = isset($question['type']) ? sanitize_text_field($question['type']) : 'radio';
       $result = $wpdb->insert($table_questions, array(
           'quiz_id'       => $quiz_id,
           'question'      => $question_text,
           'question_type' => $question_type
       ));
       if ($result === false) {
          echo '<div class="error"><p>Error saving question ' . ($q_index + 1) . '.</p></div>';
          continue;
       }
       $question_id = $wpdb->insert_id;
       if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
          echo '<div class="error"><p>Question ' . ($q_index + 1) . ' must have at least two options.</p></div>';
          continue;
       }
       foreach ($question['options'] as $o_index => $option) {
          $option_text = isset($option['text']) ? sanitize_text_field($option['text']) : '';
          if (empty($option_text)) {
             echo '<div class="error"><p>Option ' . ($o_index + 1) . ' for question ' . ($q_index + 1) . ' is required.</p></div>';
             continue;
          }
          $is_correct = isset($option['correct']) ? 1 : 0;
          $result = $wpdb->insert($table_options, array(
             'question_id' => $question_id,
             'option_text' => $option_text,
             'is_correct'  => $is_correct
          ));
          if ($result === false) {
             echo '<div class="error"><p>Error saving option ' . ($o_index + 1) . ' for question ' . ($q_index + 1) . '.</p></div>';
          }
       }
    }
    echo '<div class="updated"><p>Quiz saved successfully!</p></div>';
}

function qc_delete_quiz_questions($quiz_id) {
    global $wpdb;
    $table_questions = $wpdb->prefix . 'qc_questions';
    $table_options = $wpdb->prefix . 'qc_options';
    $question_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_questions WHERE quiz_id = %d", $quiz_id));
    if (!empty($question_ids)) {
        $ids_in = implode(',', array_map('intval', $question_ids));
        $wpdb->query("DELETE FROM $table_options WHERE question_id IN ($ids_in)");
        $wpdb->query("DELETE FROM $table_questions WHERE quiz_id = " . intval($quiz_id));
    }
}

function qc_delete_quiz($quiz_id) {
    global $wpdb;
    $table_quizzes = $wpdb->prefix . 'qc_quizzes';
    qc_delete_quiz_questions($quiz_id);
    $wpdb->delete($table_quizzes, array('id' => intval($quiz_id)), array('%d'));
    $table_results = $wpdb->prefix . 'qc_results';
    $wpdb->delete($table_results, array('quiz_id' => intval($quiz_id)), array('%d'));
}
?>
