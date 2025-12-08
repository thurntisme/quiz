<?php
session_start();

// Check if user has started the quiz
if (!isset($_SESSION['user_name']) || !isset($_SESSION['quiz_start_time'])) {
    header('Location: index.php');
    exit;
}

// Load quiz data
$jsonData = file_get_contents('data.json');
$data = json_decode($jsonData, true);
$quiz = $data['quiz'];

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $_SESSION['answers'] = $_POST['answers'] ?? [];
    $_SESSION['quiz_end_time'] = time();

    // Save submission to JSON file
    $submissionData = [
        'submission_id' => uniqid('quiz_', true),
        'user_name' => $_SESSION['user_name'],
        'user_email' => $_SESSION['user_email'],
        'work_unit' => $_SESSION['work_unit'] ?? 'N/A',
        'quiz_title' => $quiz['title'],
        'start_time' => date('Y-m-d H:i:s', $_SESSION['quiz_start_time']),
        'end_time' => date('Y-m-d H:i:s', $_SESSION['quiz_end_time']),
        'time_taken_seconds' => $_SESSION['quiz_end_time'] - $_SESSION['quiz_start_time'],
        'answers' => $_SESSION['answers'],
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // Calculate score
    $correctCount = 0;
    $totalQuestions = count($quiz['questions']);
    foreach ($quiz['questions'] as $question) {
        $questionId = $question['id'];
        $questionType = $question['questionType'] ?? 'multiple_choice';
        $correctAnswer = $question['correctAnswer'];

        if ($questionType === 'multiple_select') {
            // For multiple select, compare arrays
            $userAnswer = isset($_SESSION['answers'][$questionId]) ? $_SESSION['answers'][$questionId] : [];
            if (!is_array($userAnswer)) {
                $userAnswer = [$userAnswer];
            }
            sort($userAnswer);
            $correctAnswerArray = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
            sort($correctAnswerArray);
            if ($userAnswer == $correctAnswerArray) {
                $correctCount++;
            }
        } else {
            // For single choice
            $userAnswer = isset($_SESSION['answers'][$questionId]) ? (int) $_SESSION['answers'][$questionId] : -1;
            if ($userAnswer === $correctAnswer) {
                $correctCount++;
            }
        }
    }
    $submissionData['score'] = ($correctCount / $totalQuestions) * 100;
    $submissionData['correct_answers'] = $correctCount;
    $submissionData['total_questions'] = $totalQuestions;

    // Load existing submissions or create new array
    $submissionsFile = 'submissions.json';
    $submissions = [];
    if (file_exists($submissionsFile)) {
        $existingData = file_get_contents($submissionsFile);
        $submissions = json_decode($existingData, true) ?? [];
    }

    // Add new submission
    $submissions[] = $submissionData;

    // Save to file
    file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header('Location: result.php');
    exit;
}

// Calculate remaining time
$elapsed = time() - $_SESSION['quiz_start_time'];
$timeLimit = 30 * 60; // 30 minutes in seconds
$remaining = max(0, $timeLimit - $elapsed);

// Auto-submit if time is up
if ($remaining <= 0) {
    $_SESSION['answers'] = [];
    $_SESSION['quiz_end_time'] = time();

    $submissionData = [
        'submission_id' => uniqid('quiz_', true),
        'user_name' => $_SESSION['user_name'],
        'user_email' => $_SESSION['user_email'],
        'work_unit' => $_SESSION['work_unit'] ?? 'N/A',
        'quiz_title' => $quiz['title'],
        'start_time' => date('Y-m-d H:i:s', $_SESSION['quiz_start_time']),
        'end_time' => date('Y-m-d H:i:s', $_SESSION['quiz_end_time']),
        'time_taken_seconds' => $_SESSION['quiz_end_time'] - $_SESSION['quiz_start_time'],
        'answers' => $_SESSION['answers'],
        'submitted_at' => date('Y-m-d H:i:s'),
        'auto_submitted' => true,
        'score' => 0,
        'correct_answers' => 0,
        'total_questions' => count($quiz['questions'])
    ];

    $submissionsFile = 'submissions.json';
    $submissions = [];
    if (file_exists($submissionsFile)) {
        $existingData = file_get_contents($submissionsFile);
        $submissions = json_decode($existingData, true) ?? [];
    }

    $submissions[] = $submissionData;
    file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header('Location: result.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            color: #000;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 7fr 3fr;
            gap: 20px;
        }

        .quiz-content {
            order: 1;
        }

        .sidebar {
            order: 2;
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .header {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }

        .user-info {
            margin-bottom: 10px;
        }

        .timer {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border: 2px solid #000;
            margin-top: 10px;
        }

        .timer.warning {
            background: #000;
            color: #fff;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }

        .question {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
            scroll-margin-top: 20px;
        }

        .question-number {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .question-text {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .question-type-hint {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }

        .options-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 2px solid #000;
            cursor: pointer;
            background: #fff;
            transition: background 0.2s;
        }

        .option:hover {
            background: #f0f0f0;
        }

        .option input[type="radio"],
        .option input[type="checkbox"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .option-text {
            cursor: pointer;
            flex: 1;
        }

        button {
            background: #000;
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background: #333;
        }

        .submit-section {
            border: 2px solid #000;
            padding: 20px;
            margin-top: 20px;
        }

        .question-nav {
            border: 2px solid #000;
            padding: 15px;
            background: #fff;
        }

        .nav-title {
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .nav-item {
            aspect-ratio: 1;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #fff;
            font-weight: bold;
            text-decoration: none;
            color: #000;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: #f0f0f0;
        }

        .nav-item.answered {
            background: #000;
            color: #fff;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                order: 1;
            }

            .quiz-content {
                order: 2;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="quiz-content">
            <form method="POST" id="quiz-form">
                <?php foreach ($quiz['questions'] as $index => $question): ?>
                    <?php
                    $questionType = $question['questionType'] ?? 'multiple_choice';
                    $isMultipleSelect = $questionType === 'multiple_select';
                    ?>
                    <div class="question" id="question-<?php echo $index + 1; ?>"
                        data-question-id="<?php echo $question['id']; ?>" data-question-type="<?php echo $questionType; ?>">
                        <div class="question-number">Câu <?php echo $index + 1; ?> /
                            <?php echo count($quiz['questions']); ?>
                        </div>
                        <div class="question-text"><?php echo htmlspecialchars($question['questionText']); ?></div>
                        <?php if ($isMultipleSelect): ?>
                            <div class="question-type-hint">(Chọn nhiều đáp án)</div>
                        <?php endif; ?>

                        <div class="options-wrapper">
                            <?php foreach ($question['options'] as $optIndex => $option): ?>
                                <label class="option">
                                    <?php if ($isMultipleSelect): ?>
                                        <input type="checkbox" name="answers[<?php echo $question['id']; ?>][]"
                                            value="<?php echo $optIndex; ?>" class="question-checkbox"
                                            data-question-num="<?php echo $index + 1; ?>">
                                    <?php else: ?>
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]"
                                            value="<?php echo $optIndex; ?>" required class="question-radio"
                                            data-question-num="<?php echo $index + 1; ?>">
                                    <?php endif; ?>
                                    <span class="option-text"><?php echo htmlspecialchars($option); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="submit-section">
                    <button type="submit" name="submit_quiz">Nộp bài</button>
                </div>
            </form>
        </div>

        <div class="sidebar">
            <div class="header">
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="user-info"><strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
                <div class="user-info"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                </div>
                <div class="user-info"><strong>Đơn vị:</strong>
                    <?php echo htmlspecialchars($_SESSION['work_unit'] ?? 'N/A'); ?></div>
                <div class="timer" id="timer">Thời gian còn lại: <span id="time-display">30:00</span></div>
            </div>

            <div class="question-nav">
                <div class="nav-title">Danh sách câu hỏi</div>
                <div class="nav-grid">
                    <?php for ($i = 1; $i <= count($quiz['questions']); $i++): ?>
                        <a href="#question-<?php echo $i; ?>" class="nav-item"
                            data-nav-num="<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let remainingSeconds = <?php echo $remaining; ?>;
        const timerElement = document.getElementById('timer');
        const timeDisplay = document.getElementById('time-display');
        const form = document.getElementById('quiz-form');
        const radioButtons = document.querySelectorAll('.question-radio');
        const checkboxes = document.querySelectorAll('.question-checkbox');
        const navItems = document.querySelectorAll('.nav-item');

        function updateTimer() {
            if (remainingSeconds <= 0) {
                form.submit();
                return;
            }
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            timeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (remainingSeconds <= 300) {
                timerElement.classList.add('warning');
            }
            remainingSeconds--;
        }

        updateTimer();
        setInterval(updateTimer, 1000);

        // Track answered questions for radio buttons
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function () {
                const questionNum = this.getAttribute('data-question-num');
                const navItem = document.querySelector(`.nav-item[data-nav-num="${questionNum}"]`);
                if (navItem) {
                    navItem.classList.add('answered');
                }
            });
        });

        // Track answered questions for checkboxes
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const questionNum = this.getAttribute('data-question-num');
                const questionDiv = document.querySelector(`#question-${questionNum}`);
                const checkedBoxes = questionDiv.querySelectorAll('.question-checkbox:checked');
                const navItem = document.querySelector(`.nav-item[data-nav-num="${questionNum}"]`);
                if (navItem) {
                    if (checkedBoxes.length > 0) {
                        navItem.classList.add('answered');
                    } else {
                        navItem.classList.remove('answered');
                    }
                }
            });
        });

        // Smooth scroll to questions
        navItems.forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Confirm before leaving
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });

        // Remove warning when submitting
        form.addEventListener('submit', function () {
            window.removeEventListener('beforeunload', function () { });
        });
    </script>
</body>

</html>