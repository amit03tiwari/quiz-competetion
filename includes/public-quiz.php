<?php
if (!defined('ABSPATH')) exit;

// Enqueue public assets
function qc_enqueue_public_assets() {
    wp_enqueue_style('qc-quiz-style', QC_PLUGIN_URL . 'assets/css/quiz-style.css');
    wp_enqueue_script('qc-quiz-script', QC_PLUGIN_URL . 'assets/js/quiz-script.js', array('jquery'), '1.0', true);
    wp_localize_script('qc-quiz-script', 'qc_ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'qc_enqueue_public_assets');

// Shortcode: Display Quiz on Frontend
function qc_quiz_shortcode($atts) {
    $atts = shortcode_atts(array('quiz_id' => 0), $atts, 'wp_quiz');
    global $wpdb;
    $quiz_id = intval($atts['quiz_id']);
    $table_quizzes = $wpdb->prefix . 'qc_quizzes';
    $quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_quizzes WHERE id = %d", $quiz_id));
    if (!$quiz) {
        return "<p>Quiz not found.</p>";
    }
    // Check submission window
    $now = current_time('mysql');
    if ($quiz->submission_start && $quiz->submission_end) {
        if ($now < $quiz->submission_start || $now > $quiz->submission_end) {
            return "<p>Submissions are closed for this quiz.</p>";
        }
    }
    // Retrieve questions
    $table_questions = $wpdb->prefix . 'qc_questions';
    $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_questions WHERE quiz_id = %d", $quiz_id));
    $settings = get_option('qc_global_settings', array('randomize_questions_global' => 0, 'questions_to_show_global' => 0, 'auto_next_global' => 1));
    if ($quiz->randomize_questions || $settings['randomize_questions_global']) {
        shuffle($questions);
    }
    $questions_to_show = ($quiz->questions_to_show > 0) ? $quiz->questions_to_show : $settings['questions_to_show_global'];
    if ($questions_to_show > 0 && count($questions) > $questions_to_show) {
         $questions = array_slice($questions, 0, $questions_to_show);
    }
    ob_start();
    ?>
    <div id="qc-quiz-container" data-quiz-id="<?php echo esc_attr($quiz_id); ?>" data-auto-next="<?php echo esc_attr($quiz->auto_next); ?>" data-max-time="<?php echo esc_attr($quiz->max_time); ?>">
      <h2><?php echo esc_html($quiz->title); ?></h2>
      <p><?php echo esc_html($quiz->description); ?></p>
      <form id="qc-quiz-form">
        <div id="qc-timer">Time: 0.000 seconds</div>
        <?php
        $q_index = 0;
        foreach ($questions as $question):
            $table_options = $wpdb->prefix . 'qc_options';
            $options = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_options WHERE question_id = %d", $question->id));
        ?>
        <div class="qc-question" data-question-index="<?php echo esc_attr($q_index); ?>" <?php if($q_index > 0) echo 'style="display:none;"'; ?>>
          <p><strong>Question <?php echo ($q_index+1); ?>: </strong><?php echo esc_html($question->question); ?></p>
          <?php if ($question->question_type == 'radio'): ?>
              <?php foreach ($options as $option): ?>
              <p>
                <label>
                  <input type="radio" name="question_<?php echo esc_attr($question->id); ?>" value="<?php echo esc_attr($option->id); ?>" required>
                  <?php echo esc_html($option->option_text); ?>
                </label>
              </p>
              <?php endforeach; ?>
          <?php else: ?>
              <?php foreach ($options as $option): ?>
              <p>
                <label>
                  <input type="checkbox" name="question_<?php echo esc_attr($question->id); ?>[]" value="<?php echo esc_attr($option->id); ?>">
                  <?php echo esc_html($option->option_text); ?>
                </label>
              </p>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php
        $q_index++;
        endforeach;
        ?>
        <input type="hidden" name="total_time" id="qc_total_time" value="0">
        <button type="submit" id="qc-submit-btn" class="button">Submit Quiz</button>
      </form>
      <div id="qc-result"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_quiz', 'qc_quiz_shortcode');

// Shortcode: Global Leaderboard
function qc_leaderboard_shortcode() {
    global $wpdb;
    $results_table = $wpdb->prefix . 'qc_results';
    $quizzes_table = $wpdb->prefix . 'qc_quizzes';
    $users_table = $wpdb->users;
    $query = "
        SELECT r.*, q.title AS quiz_name, u.display_name AS user_name
        FROM $results_table r
        LEFT JOIN $quizzes_table q ON r.quiz_id = q.id
        LEFT JOIN $users_table u ON r.user_id = u.ID
        ORDER BY r.attempt_date DESC
    ";
    $results = $wpdb->get_results($query);

    ob_start();
    ?>
    <div id="qc-leaderboard">
      <h2>Global Quiz Leaderboard</h2>
      <form method="GET">
        <select name="quiz_filter">
          <option value="">All Quizzes</option>
          <?php
          $quizzes = $wpdb->get_results("SELECT id, title FROM $quizzes_table");
          foreach ($quizzes as $quiz) {
              $selected = (isset($_GET['quiz_filter']) && $_GET['quiz_filter'] == $quiz->id) ? 'selected' : '';
              echo "<option value='{$quiz->id}' $selected>{$quiz->title}</option>";
          }
          ?>
        </select>
        <button type="submit">Apply Filters</button>
      </form>
      <table>
         <thead>
           <tr>
             <th>User</th>
             <th>Quiz</th>
             <th>Correct</th>
             <th>Wrong</th>
             <th>Total Time (ms)</th>
             <th>Last Attempt</th>
           </tr>
         </thead>
         <tbody>
           <?php if($results): foreach($results as $result): ?>
             <tr>
               <td><?php echo esc_html($result->user_name) . " (ID: " . esc_html($result->user_id) . ")"; ?></td>
               <td><?php echo esc_html($result->quiz_name); ?></td>
               <td><?php echo esc_html($result->correct_answers); ?></td>
               <td><?php echo esc_html($result->wrong_answers); ?></td>
               <td><?php echo esc_html($result->total_time); ?></td>
               <td><?php echo esc_html($result->attempt_date); ?></td>
             </tr>
           <?php endforeach; else: ?>
             <tr><td colspan="6">No results found.</td></tr>
           <?php endif; ?>
         </tbody>
      </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('quiz_leaderboard', 'qc_leaderboard_shortcode');

// Shortcode: User Dashboard
function qc_user_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return "<p>Please log in to view your performance.</p>";
    }
    global $wpdb;
    $user_id = get_current_user_id();
    $results_table = $wpdb->prefix . 'qc_results';
    $quizzes_table = $wpdb->prefix . 'qc_quizzes';
    $query = $wpdb->prepare(
        "SELECT r.*, q.title AS quiz_name
         FROM $results_table r
         LEFT JOIN $quizzes_table q ON r.quiz_id = q.id
         WHERE r.user_id = %d
         ORDER BY r.attempt_date DESC",
         $user_id
    );
    $results = $wpdb->get_results($query);

    ob_start();
    ?>
    <div id="qc-user-dashboard">
      <h2>Your Performance</h2>
      <table>
         <thead>
           <tr>
             <th>Quiz</th>
             <th>Correct</th>
             <th>Wrong</th>
             <th>Total Time (ms)</th>
             <th>Last Attempt</th>
           </tr>
         </thead>
         <tbody>
           <?php if($results): foreach($results as $result): ?>
             <tr>
               <td><?php echo esc_html($result->quiz_name); ?></td>
               <td><?php echo esc_html($result->correct_answers); ?></td>
               <td><?php echo esc_html($result->wrong_answers); ?></td>
               <td><?php echo esc_html($result->total_time); ?></td>
               <td><?php echo esc_html($result->attempt_date); ?></td>
             </tr>
           <?php endforeach; else: ?>
             <tr><td colspan="5">No results found.</td></tr>
           <?php endif; ?>
         </tbody>
      </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('user_dashboard', 'qc_user_dashboard_shortcode');

// AJAX Handler: Process Quiz Submission
function qc_handle_quiz_submission() {
    if (!isset($_POST['quiz_id']) || !isset($_POST['total_time'])) {
       wp_send_json_error("Invalid submission");
       return;
    }
    global $wpdb;
    $quiz_id = intval($_POST['quiz_id']);
    $total_time = intval($_POST['total_time']);
    $correct_count = 0;
    $wrong_count = 0;
    $table_questions = $wpdb->prefix . 'qc_questions';
    $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_questions WHERE quiz_id = %d", $quiz_id));
    if (!$questions) {
        wp_send_json_error("Quiz questions not found.");
        return;
    }
    foreach($questions as $question) {
        $field = 'question_' . $question->id;
        $submitted = isset($_POST[$field]) ? $_POST[$field] : null;
        if ($submitted === null) {
            $wrong_count++;
            continue;
        }
        $table_options = $wpdb->prefix . 'qc_options';
        $correct_options = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_options WHERE question_id = %d AND is_correct = 1", $question->id));
        if ($question->question_type == 'radio') {
            if (in_array(intval($submitted), array_map('intval', $correct_options))) {
                $correct_count++;
            } else {
                $wrong_count++;
            }
        } else {
            $submitted = array_map('intval', (array)$submitted);
            sort($submitted);
            sort($correct_options);
            if ($submitted == $correct_options) {
                $correct_count++;
            } else {
                $wrong_count++;
            }
        }
    }
    $table_results = $wpdb->prefix . 'qc_results';
    $user_id = get_current_user_id();
    $inserted = $wpdb->insert($table_results, array(
         'quiz_id' => $quiz_id,
         'user_id' => $user_id,
         'correct_answers' => $correct_count,
         'wrong_answers' => $wrong_count,
         'total_time' => $total_time,
    ));
    if (!$inserted) {
         wp_send_json_error("Error saving result.");
         return;
    }
    $response = array(
         'correct' => $correct_count,
         'wrong' => $wrong_count,
         'time' => number_format($total_time / 1000, 3)
    );
    wp_send_json_success($response);
}
add_action('wp_ajax_qc_submit_quiz', 'qc_handle_quiz_submission');
add_action('wp_ajax_nopriv_qc_submit_quiz', 'qc_handle_quiz_submission');
?>
