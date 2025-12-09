<?php
// Load submissions
$submissionsFile = 'submissions.json';
$submissions = [];

if (file_exists($submissionsFile)) {
    $jsonData = file_get_contents($submissionsFile);
    $submissions = json_decode($jsonData, true) ?? [];
}

// Sort by submission time (newest first)
usort($submissions, function ($a, $b) {
    return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
});
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách bài nộp</title>
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
        }

        h1 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            border: 2px solid #000;
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #46ACC2;
            color: #fff;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f0f0f0;
        }

        .score-high {
            font-weight: bold;
        }

        .auto-submit {
            color: #666;
            font-style: italic;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            border: 2px solid #46ACC2;
            background: #46ACC2;
            color: #fff;
            text-decoration: none;
        }

        .back-link:hover {
            background: #3a92a8;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            border: 2px solid #000;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-link">← Về trang chủ</a>

        <h1>DANH SÁCH BÀI NỘP</h1>

        <?php if (count($submissions) > 0): ?>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number">
                        <?php echo count($submissions); ?>
                    </div>
                    <div class="stat-label">Tổng số bài nộp</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">
                        <?php
                        $avgScore = array_sum(array_column($submissions, 'score')) / count($submissions);
                        echo number_format($avgScore, 1);
                        ?>%
                    </div>
                    <div class="stat-label">Điểm trung bình</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">
                        <?php
                        $maxScore = max(array_column($submissions, 'score'));
                        echo number_format($maxScore, 1);
                        ?>%
                    </div>
                    <div class="stat-label">Điểm cao nhất</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Đơn vị</th>
                        <th>Điểm</th>
                        <th>Số câu đúng</th>
                        <th>Thời gian làm bài</th>
                        <th>Ngày nộp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $index => $submission): ?>
                        <tr>
                            <td>
                                <?php echo $index + 1; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($submission['user_name']); ?>
                                <?php if (isset($submission['auto_submitted']) && $submission['auto_submitted']): ?>
                                    <span class="auto-submit">(Hết giờ)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($submission['user_email']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($submission['work_unit'] ?? 'N/A'); ?>
                            </td>
                            <td class="score-high">
                                <?php echo number_format($submission['score'], 1); ?>%
                            </td>
                            <td>
                                <?php echo $submission['correct_answers']; ?> /
                                <?php echo $submission['total_questions']; ?>
                            </td>
                            <td>
                                <?php
                                $minutes = floor($submission['time_taken_seconds'] / 60);
                                $seconds = $submission['time_taken_seconds'] % 60;
                                echo "{$minutes}:{$seconds}";
                                ?>
                            </td>
                            <td>
                                <?php echo $submission['submitted_at']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>Chưa có bài nộp nào.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>