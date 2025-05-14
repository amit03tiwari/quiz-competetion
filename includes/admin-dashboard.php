<?php
if (!defined('ABSPATH')) exit;

function qc_register_admin_menu() {
    add_menu_page(
        'Quiz Competition',          // Page title 
        'Quiz Competition',          // Menu title
        'manage_options',            // Capability
        'qc_admin',                  // Slug
        'qc_admin_dashboard',        // Callback function
        'dashicons-welcome-learn-more',  // Icon
        6
    );
    add_submenu_page('qc_admin', 'Manage Quizzes', 'Manage Quizzes', 'manage_options', 'qc_manage_quiz', 'qc_manage_quiz_page');
    add_submenu_page('qc_admin', 'Quiz Settings', 'Quiz Settings', 'manage_options', 'qc_settings', 'qc_settings_page');
    add_submenu_page('qc_admin', 'Quiz Results', 'Quiz Results', 'manage_options', 'qc_results', 'qc_results_page');
}
add_action('admin_menu', 'qc_register_admin_menu');

function qc_admin_dashboard() {
    global $wpdb;
    $table_quizzes = $wpdb->prefix . 'qc_quizzes';
    $quizzes = $wpdb->get_results("SELECT id, title FROM $table_quizzes ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>Quiz Competition Dashboard</h1>
        <p>Below is a list of quizzes with their corresponding shortcode. You may edit or delete any quiz.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Quiz Name</th>
                    <th>Shortcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($quizzes): foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo esc_html($quiz->title); ?></td>
                        <td>[wp_quiz quiz_id="<?php echo esc_html($quiz->id); ?>"]</td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=qc_manage_quiz&quiz_id=' . intval($quiz->id)); ?>">Edit</a> |
                            <a href="<?php echo admin_url('admin.php?page=qc_admin&delete_quiz=' . intval($quiz->id)); ?>" onclick="return confirm('Are you sure you want to delete this quiz?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3">No quizzes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function qc_manage_quiz_page() {
    global $wpdb;
    $editing = false;
    $quiz_data = array(
        'id' => '',
        'title' => '',
        'description' => '',
        'max_time' => 60,
        'randomize_questions' => 0,
        'questions_to_show' => 0,
        'auto_next' => 1,
        'submission_start' => '',
        'submission_end' => '',
        'questions' => array()
    );
    if (isset($_GET['quiz_id']) && intval($_GET['quiz_id']) > 0) {
        $editing = true;
        $quiz_id = intval($_GET['quiz_id']);
        $table_quizzes = $wpdb->prefix . 'qc_quizzes';
        $quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_quizzes WHERE id = %d", $quiz_id));
        if ($quiz) {
            $quiz_data['id'] = $quiz->id;
            $quiz_data['title'] = $quiz->title;
            $quiz_data['description'] = $quiz->description;
            $quiz_data['max_time'] = $quiz->max_time;
            $quiz_data['randomize_questions'] = $quiz->randomize_questions;
            $quiz_data['questions_to_show'] = $quiz->questions_to_show;
            $quiz_data['auto_next'] = $quiz->auto_next;
            $quiz_data['submission_start'] = $quiz->submission_start;
            $quiz_data['submission_end'] = $quiz->submission_end;
            $table_questions = $wpdb->prefix . 'qc_questions';
            $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_questions WHERE quiz_id = %d", $quiz_id));
            if ($questions) {
                foreach ($questions as $q) {
                    $q_obj = array(
                        'id' => $q->id,
                        'text' => $q->question,
                        'type' => $q->question_type,
                        'options' => array()
                    );
                    $table_options = $wpdb->prefix . 'qc_options';
                    $options = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_options WHERE question_id = %d", $q->id));
                    if ($options) {
                        foreach ($options as $opt) {
                            $q_obj['options'][] = array(
                                'id' => $opt->id,
                                'text' => $opt->option_text,
                                'correct' => $opt->is_correct
                            );
                        }
                    }
                    $quiz_data['questions'][] = $q_obj;
                }
            }
        }
    }
    if (isset($_POST['qc_save_quiz'])) {
        qc_save_quiz();
        // Optionally, you can redirect to the dashboard after saving.
    }
    ?>
    <div class="wrap">
        <h1><?php echo $editing ? 'Edit Quiz' : 'Create Quiz'; ?></h1>
        <div class="qc_admin-tabs-nav">
            <a href="#" class="qc-tab-link active" data-tab="details">Quiz Details</a>
            <a href="#" class="qc-tab-link" data-tab="submissions">Submission Time</a>
            <a href="#" class="qc-tab-link" data-tab="questions">Questions &amp; Options</a>
        </div>
        <form method="post" action="">
            <?php wp_nonce_field('qc_save_quiz_nonce','qc_nonce_field'); ?>
            <?php if($editing): ?>
                <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_data['id']); ?>">
            <?php endif; ?>
            <div id="tab-details" class="qc-admin-tab active">
                <h2>Quiz Details</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="quiz_title">Quiz Title</label></th>
                        <td><input type="text" name="quiz_title" id="quiz_title" value="<?php echo esc_attr($quiz_data['title']); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="quiz_desc">Description</label></th>
                        <td><textarea name="quiz_desc" id="quiz_desc"><?php echo esc_textarea($quiz_data['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="max_time">Max Time (seconds)</label></th>
                        <td><input type="number" name="max_time" id="max_time" value="<?php echo esc_attr($quiz_data['max_time']); ?>" min="1"></td>
                    </tr>
                    <tr>
                        <th><label for="randomize_questions">Randomize Questions</label></th>
                        <td><input type="checkbox" name="randomize_questions" id="randomize_questions" value="1" <?php checked($quiz_data['randomize_questions'],1); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="questions_to_show">Questions to Show (0 for all)</label></th>
                        <td><input type="number" name="questions_to_show" id="questions_to_show" value="<?php echo esc_attr($quiz_data['questions_to_show']); ?>" min="0"></td>
                    </tr>
                    <tr>
                        <th><label for="auto_next">Auto Load Next Question</label></th>
                        <td><input type="checkbox" name="auto_next" id="auto_next" value="1" <?php checked($quiz_data['auto_next'],1); ?>></td>
                    </tr>
                </table>
            </div>
            <div id="tab-submissions" class="qc-admin-tab">
                <h2>Submission Time</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="submission_start">Submission Start</label></th>
                        <td><input type="datetime-local" name="submission_start" id="submission_start" value="<?php echo !empty($quiz_data['submission_start']) ? date('Y-m-d\TH:i', strtotime($quiz_data['submission_start'])) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="submission_end">Submission End</label></th>
                        <td><input type="datetime-local" name="submission_end" id="submission_end" value="<?php echo !empty($quiz_data['submission_end']) ? date('Y-m-d\TH:i', strtotime($quiz_data['submission_end'])) : ''; ?>"></td>
                    </tr>
                </table>
            </div>
            <div id="tab-questions" class="qc-admin-tab">
                <h2>Questions &amp; Options</h2>
                <div id="qc_questions_container">
                <?php 
                if (!empty($quiz_data['questions'])):
                    foreach ($quiz_data['questions'] as $q_index => $question):
                ?>
                    <div class="qc_question" data-question-index="<?php echo $q_index; ?>">
                        <h3>Question <?php echo ($q_index+1); ?> <a href="#" class="remove-question" onclick="jQuery(this).closest('.qc_question').remove(); return false;">Remove</a></h3>
                        <p>
                            <label>Question: <input type="text" name="questions[<?php echo $q_index; ?>][text]" value="<?php echo esc_attr($question['text']); ?>" required></label>
                        </p>
                        <p>
                            Type: 
                            <select name="questions[<?php echo $q_index; ?>][type]">
                                <option value="radio" <?php selected($question['type'], 'radio'); ?>>Single Correct (Radio)</option>
                                <option value="checkbox" <?php selected($question['type'], 'checkbox'); ?>>Multiple Correct (Checkbox)</option>
                            </select>
                        </p>
                        <div class="qc_options_container">
                        <?php 
                            if (!empty($question['options'])):
                                foreach ($question['options'] as $o_index => $option):
                        ?>
                            <p>
                                Option <?php echo ($o_index+1); ?>: 
                                <input type="text" name="questions[<?php echo $q_index; ?>][options][<?php echo $o_index; ?>][text]" value="<?php echo esc_attr($option['text']); ?>" required>
                                &nbsp; Correct? 
                                <input type="checkbox" name="questions[<?php echo $q_index; ?>][options][<?php echo $o_index; ?>][correct]" value="1" <?php checked($option['correct'],1); ?>>
                                <a href="#" class="remove-option" onclick="jQuery(this).parent().remove(); return false;">Remove</a>
                            </p>
                        <?php endforeach; endif; ?>
                        </div>
                        <button class="button add_option" type="button">Add Option</button>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="qc_question" data-question-index="0">
                        <h3>Question 1 <a href="#" class="remove-question" onclick="jQuery(this).closest('.qc_question').remove(); return false;">Remove</a></h3>
                        <p>
                            <label>Question: <input type="text" name="questions[0][text]" required></label>
                        </p>
                        <p>
                            Type: 
                            <select name="questions[0][type]">
                                <option value="radio">Single Correct (Radio)</option>
                                <option value="checkbox">Multiple Correct (Checkbox)</option>
                            </select>
                        </p>
                        <div class="qc_options_container">
                            <p>
                                Option 1: <input type="text" name="questions[0][options][0][text]" required>
                                &nbsp; Correct? <input type="checkbox" name="questions[0][options][0][correct]" value="1">
                            </p>
                            <p>
                                Option 2: <input type="text" name="questions[0][options][1][text]" required>
                                &nbsp; Correct? <input type="checkbox" name="questions[0][options][1][correct]" value="1">
                            </p>
                        </div>
                        <button class="button add_option" type="button">Add Option</button>
                    </div>
                <?php endif; ?>
                </div>
                <button class="button" id="add_question" type="button">Add Question</button>
            </div>
            <p class="submit">
                <input type="submit" name="qc_save_quiz" class="button-primary" value="<?php echo $editing ? 'Update Quiz' : 'Save Quiz'; ?>">
            </p>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Tab switching logic
            $('.qc-tab-link').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                $('.qc-tab-link').removeClass('active');
                $(this).addClass('active');
                $('.qc-admin-tab').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });
            // Clone question block
            $("#add_question").on("click", function(e) {
                e.preventDefault();
                var qCount = $(".qc_question").length;
                var newQ = $(".qc_question:first").clone();
                newQ.attr("data-question-index", qCount);
                newQ.find("h3").html("Question " + (qCount + 1) + ' <a href="#" class="remove-question" onclick="jQuery(this).closest(\'.qc_question\').remove(); return false;">Remove</a>');
                newQ.find("input, textarea, select").each(function() {
                    var name = $(this).attr("name");
                    if (name) {
                        name = name.replace(/\d+/, qCount);
                        $(this).attr("name", name);
                        $(this).val("");
                    }
                });
                $("#qc_questions_container").append(newQ);
            });
            // Clone option block
            $(document).on("click", ".add_option", function(e) {
                e.preventDefault();
                var $question = $(this).closest(".qc_question");
                var qIndex = $question.data("question-index");
                var oCount = $question.find(".qc_options_container p").length;
                var newOption = '<p>Option ' + (oCount + 1) + ': <input type="text" name="questions[' + qIndex + '][options][' + oCount + '][text]" required> &nbsp; Correct? <input type="checkbox" name="questions[' + qIndex + '][options][' + oCount + '][correct]" value="1"> <a href="#" class="remove-option" onclick="jQuery(this).parent().remove(); return false;">Remove</a></p>';
                $question.find(".qc_options_container").append(newOption);
            });
        });
    </script>
    <?php
}

function qc_settings_page() {
    if (isset($_POST['qc_save_settings'])) {
        check_admin_referer('qc_save_settings_nonce','qc_settings_nonce_field');
        $global_settings = array(
            'max_time_global' => intval($_POST['max_time_global']),
            'randomize_questions_global' => isset($_POST['randomize_questions_global']) ? 1 : 0,
            'questions_to_show_global' => intval($_POST['questions_to_show_global']),
            'auto_next_global' => isset($_POST['auto_next_global']) ? 1 : 0
        );
        update_option('qc_global_settings', $global_settings);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    $settings = get_option('qc_global_settings', array('max_time_global'=>60, 'randomize_questions_global'=>0, 'questions_to_show_global'=>0, 'auto_next_global'=>1));
    ?>
    <div class="wrap">
      <h1>Quiz Global Settings</h1>
      <form method="post" action="">
        <?php wp_nonce_field('qc_save_settings_nonce','qc_settings_nonce_field'); ?>
        <table class="form-table">
          <tr>
            <th><label for="max_time_global">Max Time Limit (seconds)</label></th>
            <td><input type="number" name="max_time_global" id="max_time_global" value="<?php echo esc_attr($settings['max_time_global']); ?>" min="1"></td>
          </tr>
          <tr>
            <th><label for="randomize_questions_global">Randomize Questions</label></th>
            <td><input type="checkbox" name="randomize_questions_global" id="randomize_questions_global" value="1" <?php checked($settings['randomize_questions_global'],1); ?>></td>
          </tr>
          <tr>
            <th><label for="questions_to_show_global">Questions to Show (0 for all)</label></th>
            <td><input type="number" name="questions_to_show_global" id="questions_to_show_global" value="<?php echo esc_attr($settings['questions_to_show_global']); ?>" min="0"></td>
          </tr>
          <tr>
            <th><label for="auto_next_global">Auto Load Next Question</label></th>
            <td><input type="checkbox" name="auto_next_global" id="auto_next_global" value="1" <?php checked($settings['auto_next_global'],1); ?>></td>
          </tr>
        </table>
        <p class="submit"><input type="submit" name="qc_save_settings" class="button-primary" value="Save Settings"></p>
      </form>
    </div>
    <?php
}

function qc_results_page() {
    global $wpdb;
    $results_table = $wpdb->prefix . 'qc_results';
    $results = $wpdb->get_results("SELECT * FROM $results_table ORDER BY attempt_date DESC");
    ?>
    <div class="wrap">
      <h1>Quiz Results</h1>
      <table class="wp-list-table widefat fixed striped">
          <thead>
              <tr>
                  <th>ID</th>
                  <th>User ID</th>
                  <th>Quiz ID</th>
                  <th>Correct Answers</th>
                  <th>Wrong Answers</th>
                  <th>Total Time (ms)</th>
                  <th>Attempt Date</th>
              </tr>
          </thead>
          <tbody>
              <?php if($results): foreach($results as $result): ?>
                  <tr>
                      <td><?php echo esc_html($result->id); ?></td>
                      <td><?php echo esc_html($result->user_id); ?></td>
                      <td><?php echo esc_html($result->quiz_id); ?></td>
                      <td><?php echo esc_html($result->correct_answers); ?></td>
                      <td><?php echo esc_html($result->wrong_answers); ?></td>
                      <td><?php echo esc_html($result->total_time); ?></td>
                      <td><?php echo esc_html($result->attempt_date); ?></td>
                  </tr>
              <?php endforeach; else: ?>
                  <tr><td colspan="7">No results found.</td></tr>
              <?php endif; ?>
          </tbody>
      </table>
    </div>
    <?php
}
?>
