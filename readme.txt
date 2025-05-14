Quiz Competition
Version: 1.0 
Author: InnoSewa
Stable tag: 1.0
License: GPL-2.0+ 
Requires at least: WordPress 5.0 
Tested up to: WordPress 6.x 
Tags: quiz, competition, leaderboard, education

Table of Contents

1. Description
2. Features
3. Installation
4. Usage
5. Shortcodes
6. Customization

Troubleshooting

1. Description
Quiz Competition is a powerful and user-friendly WordPress quiz plugin that allows admins to create quizzes, track user results, and display leaderboards. Users can attempt quizzes multiple times, improving their best scores, and tracking their performance in a personalized dashboard.

2. Features
✅ Create Unlimited Quizzes – Add multiple quizzes with configurable settings. ✅ Flexible Question Types – Single-choice and multiple-choice question formats. ✅ Leaderboard & Results Tracking – Save quiz results, attempts, and best scores. ✅ User Dashboard – Each user sees their quiz performance and history. ✅ Custom Styling Options – Change fonts, colors, and button designs in admin & frontend. ✅ AJAX-Based Quiz Submission – Ensures fast processing without page reloads.

3. Installation
Automatic Installation
Go to your WordPress Dashboard → Plugins → Add New.

Search for Quiz Competition Plugin.

Click Install Now, then Activate.

Manual Installation
Download the plugin .zip file.

Navigate to WordPress Dashboard → Plugins → Add New → Upload Plugin.

Upload the .zip file, click Install Now, then Activate.

Database Setup
If the plugin doesn't create necessary tables, run this SQL query manually:

sql
ALTER TABLE wp_qc_results ADD COLUMN attempts INT DEFAULT 1, ADD COLUMN best_score INT DEFAULT 0;

4. Usage
Creating a Quiz
Go to Quiz Competition → Manage Quizzes.

Click "Add New Quiz" and enter a title, description, and settings.

Add questions and multiple-choice options.

Save the quiz, then copy and paste the shortcode into a page/post.

5. Shortcodes
Shortcode	Usage
[wp_quiz quiz_id="X"]	Embed a specific quiz. Replace X with quiz ID.
[quiz_leaderboard]	Display the global quiz leaderboard.
[user_dashboard]	Show the user’s quiz results after login.
Example Usage:

html
[wp_quiz quiz_id="2"]
[quiz_leaderboard]
[user_dashboard]

6. Customization
You can change quiz styling under Quiz Competition → Style Settings.

Available Options:
Admin Font Family: Change typography of admin pages.
Admin Button Color: Customize buttons in admin panel.
Frontend Button Color: Change quiz button color.

Adding Custom CSS
To apply custom styles, add CSS to your theme:

css
.qc-submit-btn {
    background-color: #ff4500 !important;
    color: white !important;
}
7. Troubleshooting
Quiz Results Not Displaying?
✔ Ensure wp_qc_results table exists in the database. ✔ Run this SQL query if necessary:

sql
SELECT * FROM wp_qc_results;
Users Can't See Leaderboard?
✔ Ensure [quiz_leaderboard] shortcode is inserted. ✔ Verify database has stored quiz attempts.

Styling Issues?
✔ Clear your browser cache. ✔ Add custom CSS inside Appearance → Customize → Additional CSS.

Support
If you need assistance, reach out via the WordPress Support Forum or create a GitHub issue.

Enjoy using Quiz Competition Plugin! 🚀