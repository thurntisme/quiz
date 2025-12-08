<?php
session_start();

// Load quiz data
$jsonData = file_get_contents('data.json');
$data = json_decode($jsonData, true);
$quiz = $data['quiz'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_quiz'])) {
    $_SESSION['user_name'] = htmlspecialchars($_POST['name']);
    $_SESSION['user_email'] = htmlspecialchars($_POST['email']);
    $_SESSION['work_unit'] = htmlspecialchars($_POST['work_unit']);
    if ($_POST['work_unit'] === 'Other' && !empty($_POST['work_unit_other'])) {
        $_SESSION['work_unit'] = htmlspecialchars($_POST['work_unit_other']);
    }
    $_SESSION['quiz_start_time'] = time();
    header('Location: quiz.php');
    exit;
}

// Reset quiz
if (isset($_GET['reset'])) {
    session_destroy();
    header('Location: index.php');
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
            max-width: 800px;
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #000;
            background: #fff;
            color: #000;
            font-size: 16px;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: 2px solid #000;
        }

        #work_unit_other {
            margin-top: 10px;
            display: none;
        }

        #work_unit_other.show {
            display: block;
        }

        button {
            background: #000;
            color: #fff;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        button:hover {
            background: #333;
        }

        .admin-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #000;
            text-decoration: none;
            color: #000;
        }

        .admin-link:hover {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
        <form method="POST">
            <div class="form-group">
                <label for="name">Họ và tên *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="work_unit">Đơn vị công tác *</label>
                <select id="work_unit" name="work_unit" required>
                    <option value="">-- Chọn đơn vị --</option>
                    <option value="IVFMD Tân Bình">IVFMD Tân Bình</option>
                    <option value="IVFMD Phú Nhuận">IVFMD Phú Nhuận</option>
                    <option value="IVFMD Family">IVFMD Family</option>
                    <option value="IVFMD Bình Dương">IVFMD Bình Dương</option>
                    <option value="IVF Vạn Hạnh">IVF Vạn Hạnh</option>
                    <option value="IVFMD Gia Định">IVFMD Gia Định</option>
                    <option value="IVFMD Buôn Ma Thuột">IVFMD Buôn Ma Thuột</option>
                    <option value="IVF Quốc Ánh">IVF Quốc Ánh</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" id="work_unit_other" name="work_unit_other" placeholder="Nhập đơn vị công tác khác">
            </div>
            <button type="submit" name="start_quiz">Bắt đầu làm bài</button>
        </form>
        <script>
            const workUnitSelect = document.getElementById('work_unit');
            const workUnitOther = document.getElementById('work_unit_other');

            workUnitSelect.addEventListener('change', function () {
                if (this.value === 'Other') {
                    workUnitOther.classList.add('show');
                    workUnitOther.required = true;
                } else {
                    workUnitOther.classList.remove('show');
                    workUnitOther.required = false;
                    workUnitOther.value = '';
                }
            });
        </script>
    </div>
</body>

</html>