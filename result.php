<?php
session_start();

// Check if quiz was completed
if (!isset($_SESSION['user_name']) || !isset($_SESSION['quiz_end_time'])) {
    header('Location: index.php');
    exit;
}

// Load quiz data
$jsonData = file_get_contents('data.json');
$data = json_decode($jsonData, true);
$quiz = $data['quiz'];

// Calculate results
$answers = $_SESSION['answers'] ?? [];
$correctCount = 0;
$totalQuestions = count($quiz['questions']);

foreach ($quiz['questions'] as $question) {
    $questionId = $question['id'];
    $questionType = $question['questionType'] ?? 'multiple_choice';
    $correctAnswer = $question['correctAnswer'];

    if ($questionType === 'multiple_select') {
        // For multiple select, compare arrays
        $userAnswer = isset($answers[$questionId]) ? $answers[$questionId] : [];
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
        $userAnswer = isset($answers[$questionId]) ? (int) $answers[$questionId] : -1;
        if ($userAnswer === $correctAnswer) {
            $correctCount++;
        }
    }
}

$score = ($correctCount / $totalQuestions) * 100;
$timeTaken = $_SESSION['quiz_end_time'] - $_SESSION['quiz_start_time'];
$minutes = floor($timeTaken / 60);
$seconds = $timeTaken % 60;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả bài thi</title>
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
            max-width: 900px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 40px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .result-summary {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .score {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }

        .info-row {
            margin: 10px 0;
            font-size: 16px;
        }

        .question-review {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
        }

        .question-number {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .question-text {
            margin-bottom: 15px;
        }

        .options-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .option {
            padding: 12px 15px;
            border: 2px solid #000;
        }

        .option.correct {
            background: #000;
            color: #fff;
        }

        .option.wrong {
            background: #fff;
            color: #000;
            border: 2px solid #000;
        }

        .option.user-answer {
            border: 3px solid #000;
        }

        button {
            background: #000;
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        button:hover {
            background: #333;
        }

        .legend {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #000;
        }

        .legend-item {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>KẾT QUẢ BÀI THI</h1>

        <div class="result-summary">
            <div class="info-row"><strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div class="info-row"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
            <div class="info-row"><strong>Đơn vị công tác:</strong>
                <?php echo htmlspecialchars($_SESSION['work_unit'] ?? 'N/A'); ?></div>
            <div class="score"><?php echo number_format($score, 1); ?>%</div>
            <div class="info-row">
                <strong>Số câu đúng:</strong> <?php echo $correctCount; ?> / <?php echo $totalQuestions; ?>
            </div>
            <div class="info-row">
                <strong>Thời gian làm bài:</strong> <?php echo $minutes; ?> phút <?php echo $seconds; ?> giây
            </div>
        </div>

        <div class="legend">
            <strong>Chú thích:</strong>
            <div class="legend-item">■ Nền đen = Đáp án đúng</div>
            <div class="legend-item">□ Viền đậm = Câu trả lời của bạn</div>
        </div>

        <?php foreach ($quiz['questions'] as $index => $question): ?>
            <?php
            $questionId = $question['id'];
            $questionType = $question['questionType'] ?? 'multiple_choice';
            $correctAnswer = $question['correctAnswer'];

            // Determine if answer is correct
            if ($questionType === 'multiple_select') {
                $userAnswer = isset($answers[$questionId]) ? $answers[$questionId] : [];
                if (!is_array($userAnswer)) {
                    $userAnswer = [$userAnswer];
                }
                $userAnswerSorted = $userAnswer;
                sort($userAnswerSorted);
                $correctAnswerArray = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
                sort($correctAnswerArray);
                $isCorrect = $userAnswerSorted == $correctAnswerArray;
            } else {
                $userAnswer = isset($answers[$questionId]) ? (int) $answers[$questionId] : -1;
                $isCorrect = $userAnswer === $correctAnswer;
            }
            ?>
            <div class="question-review">
                <div class="question-number">
                    Câu <?php echo $index + 1; ?>
                    <?php echo $isCorrect ? '✓' : '✗'; ?>
                    <?php if ($questionType === 'multiple_select'): ?>
                        <span style="font-size: 12px; font-style: italic;">(Nhiều đáp án)</span>
                    <?php endif; ?>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($question['questionText']); ?></div>

                <div class="options-wrapper">
                    <?php foreach ($question['options'] as $optIndex => $option): ?>
                        <?php
                        $classes = [];

                        if ($questionType === 'multiple_select') {
                            // For multiple select
                            $correctAnswerArray = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
                            $userAnswerArray = is_array($userAnswer) ? $userAnswer : [];

                            $isCorrectOption = in_array($optIndex, $correctAnswerArray);
                            $isUserSelected = in_array($optIndex, $userAnswerArray);

                            if ($isCorrectOption) {
                                $classes[] = 'correct';
                            }
                            if ($isUserSelected && !$isCorrectOption) {
                                $classes[] = 'wrong user-answer';
                            } elseif ($isUserSelected) {
                                $classes[] = 'user-answer';
                            }
                        } else {
                            // For single choice
                            if ($optIndex === $correctAnswer) {
                                $classes[] = 'correct';
                            }
                            if ($optIndex === $userAnswer && $userAnswer !== $correctAnswer) {
                                $classes[] = 'wrong user-answer';
                            } elseif ($optIndex === $userAnswer) {
                                $classes[] = 'user-answer';
                            }
                        }

                        $classStr = implode(' ', $classes);
                        ?>
                        <div class="option <?php echo $classStr; ?>">
                            <?php echo htmlspecialchars($option); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <a href="index.php?reset=1">
            <button>Làm lại bài thi</button>
        </a>
    </div>
</body>

</html>