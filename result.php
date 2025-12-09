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
        $userAnswer = $answers[$questionId] ?? [];
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
    <title>Hoàn thành bài thi</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            max-width: 700px;
            width: 100%;
            border: 2px solid #000;
            padding: 60px 40px;
            text-align: center;
        }

        h1 {
            font-size: 36px;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .congratulations {
            font-size: 20px;
            margin-bottom: 40px;
            line-height: 1.8;
        }

        .user-info {
            border: 2px solid #000;
            padding: 30px;
            margin-bottom: 30px;
            text-align: left;
        }

        .info-row {
            margin: 15px 0;
            font-size: 16px;
        }

        .info-row strong {
            display: inline-block;
            min-width: 150px;
        }

        .thank-you {
            font-size: 18px;
            margin: 30px 0;
            font-weight: bold;
        }

        button {
            background: #46ACC2;
            color: #fff;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        button:hover {
            background: #3a92a8;
        }

        a {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>HOÀN THÀNH BÀI THI</h1>

        <div class="congratulations">
            Chúc mừng bạn đã hoàn thành bài thi!<br>
            Kết quả của bạn đã được ghi nhận và lưu trữ.
        </div>

        <div class="user-info">
            <div class="info-row">
                <strong>Họ và tên:</strong>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </div>
            <div class="info-row">
                <strong>Email:</strong>
                <?php echo htmlspecialchars($_SESSION['user_email']); ?>
            </div>
            <div class="info-row">
                <strong>Đơn vị công tác:</strong>
                <?php echo htmlspecialchars($_SESSION['work_unit'] ?? 'N/A'); ?>
            </div>
            <div class="info-row">
                <strong>Thời gian làm bài:</strong>
                <?php echo $minutes; ?> phút <?php echo $seconds; ?> giây
            </div>
        </div>

        <div class="thank-you">
            Cảm ơn bạn đã tham gia!
        </div>

        <a href="index.php?reset=1">
            <button>Về trang chủ</button>
        </a>
    </div>
</body>

</html>