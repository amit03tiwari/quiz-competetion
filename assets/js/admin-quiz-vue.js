// Ensure Axios and Vue (v3) are loaded via CDN (see plugin enqueues)
Vue.createApp({
    data() {
        return {
            quiz: {
                title: '',
                description: '',
                max_time: 60,
                randomize_questions: false,
                questions_to_show: 0,
                auto_next: true,
                questions: [
                    {
                        id: null, // new question, unsaved
                        text: '',
                        type: 'radio',
                        options: [
                            { id: null, text: '', correct: false },
                            { id: null, text: '', correct: false }
                        ]
                    }
                ]
            },
            errorMessages: [],
            successMessage: ''
        }
    },
    methods: {
        addQuestion() {
            this.quiz.questions.push({
                id: null,
                text: '',
                type: 'radio',
                options: [
                    { id: null, text: '', correct: false },
                    { id: null, text: '', correct: false }
                ]
            });
        },
        removeQuestion(qIndex) {
            // If the question has an ID, call AJAX deletion; otherwise remove locally.
            let question = this.quiz.questions[qIndex];
            if (question.id) {
                axios.post(qc_admin_obj.ajaxurl, {
                    action: 'qc_delete_question',
                    question_id: question.id,
                    _ajax_nonce: qc_admin_obj.ajax_nonce
                }).then(response => {
                    if (response.data.success) {
                        this.quiz.questions.splice(qIndex, 1);
                    } else {
                        this.errorMessages.push(response.data.data.message);
                    }
                }).catch(error => {
                    this.errorMessages.push("AJAX error: " + error);
                });
            } else {
                this.quiz.questions.splice(qIndex, 1);
            }
        },
        addOption(qIndex) {
            this.quiz.questions[qIndex].options.push({
                id: null,
                text: '',
                correct: false
            });
        },
        removeOption(qIndex, oIndex) {
            let option = this.quiz.questions[qIndex].options[oIndex];
            if (option.id) {
                axios.post(qc_admin_obj.ajaxurl, {
                    action: 'qc_delete_option',
                    option_id: option.id,
                    _ajax_nonce: qc_admin_obj.ajax_nonce
                }).then(response => {
                    if (response.data.success) {
                        this.quiz.questions[qIndex].options.splice(oIndex, 1);
                    } else {
                        this.errorMessages.push(response.data.data.message);
                    }
                }).catch(error => {
                    this.errorMessages.push("AJAX error: " + error);
                });
            } else {
                this.quiz.questions[qIndex].options.splice(oIndex, 1);
            }
        },
        saveQuiz() {
            this.errorMessages = [];
            if (!this.quiz.title) {
                this.errorMessages.push("Quiz title is required.");
                return;
            }
            if (this.quiz.questions.length === 0) {
                this.errorMessages.push("At least one question is required.");
                return;
            }
            // Submit the quiz via Axios (JSON post)
            axios.post(qc_admin_obj.ajaxurl, Object.assign({
                action: 'qc_ajax_save_quiz',
                _ajax_nonce: qc_admin_obj.ajax_nonce
            }, this.quiz))
            .then(response => {
                if (response.data.success) {
                    this.successMessage = response.data.data.message;
                } else {
                    this.errorMessages.push(response.data.data.message);
                }
            })
            .catch(error => {
                this.errorMessages.push("AJAX error: " + error);
            });
        }
    }
}).mount('#qc-vue-app');
