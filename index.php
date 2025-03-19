<?php
session_start();
require_once 'db.php';

// 
if (!isset($_SESSION['game_started']) && isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username']; // ngan
    $_SESSION['score'] = 0; // iyang score
    $_SESSION['question_count'] = 0; // pila ka pangutana 
    $_SESSION['game_started'] = true; // mag sugod ug duwa
    $_SESSION['time_started'] = date('Y-m-d H:i:s'); // kung unsa siya oras ga sugod ug duwa
    $_SESSION['used_fruits'] = []; // para di balik2 ang mga fruits
}

// Game completion logic
function endGame($conn) {
    $timeEnded = date('Y-m-d H:i:s');
    $timeStarted = $_SESSION['time_started'];
    $duration = strtotime($timeEnded) - strtotime($timeStarted);
    $finalScore = $_SESSION['score']; // Store the final score
    $datePlayed = date('Y-m-d'); // Get current date
    
    $stmt = $conn->prepare("INSERT INTO players (username, score, time_started, time_ended, duration_seconds, date_played) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $_SESSION['username'], $_SESSION['score'], $_SESSION['time_started'], $timeEnded, $duration, $datePlayed);
    $stmt->execute();
    $stmt->close();
    
    session_destroy();
    session_start(); // Start a new session
    $_SESSION['final_score'] = $finalScore; // Save the final score in the new session
}

// Handle answer submission
if (isset($_POST['answer'])) {
    if ($_POST['answer'] == $_SESSION['correct_fruit']) {
        $_SESSION['score']++;
    }
    $_SESSION['question_count']++;
    
    if ($_SESSION['question_count'] >= 10) {
        endGame($conn);
        header('Location: index.php?show_results=1');
        exit();
    }
}

// Get high scores
$highScores = [];
$result = $conn->query("SELECT username, score, duration_seconds as time, DATE_FORMAT(date_played, '%m/%d/%Y') as date_played FROM players ORDER BY score DESC, duration_seconds ASC LIMIT 100");
if ($result) {
    while ($row = $result->fetch_object()) {
        $highScores[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fruit Quiz Game</title>
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --accent-color: #FFE66D;
            --background-color: #f0f7ff;
            --text-color: #2C3E50;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            background-image: linear-gradient(45deg, #f0f7ff 25%, #e6f3ff 25%, #e6f3ff 50%, #f0f7ff 50%, #f0f7ff 75%, #e6f3ff 75%, #e6f3ff 100%);
            background-size: 40px 40px;
        }

        .game-container {
            background-color: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            transition: all 0.3s ease;
            border: 3px solid var(--primary-color);
        }

        .game-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 3em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            letter-spacing: 1px;
        }

        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px auto;
            max-width: 600px;
            padding: 0 15px;
        }

        .choice-btn {
            padding: 20px 30px;
            font-size: 20px;
            cursor: pointer;
            background-color: white;
            border: 3px solid var(--secondary-color);
            border-radius: 15px;
            transition: all 0.3s ease;
            color: var(--secondary-color);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .choice-btn:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .score {
            font-size: 32px;
            margin: 25px 0;
            color: var(--primary-color);
            font-weight: bold;
            display: inline-block;
            padding: 10px 25px;
            border-radius: 50px;
            background-color: rgba(255, 107, 107, 0.1);
        }

        .score i {
            color: #FFD700;
            margin-right: 10px;
            animation: spin 4s linear infinite;
        }

        .question-count {
            font-size: 22px;
            margin-bottom: 25px;
            color: var(--text-color);
            font-weight: 600;
            background-color: var(--accent-color);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
        }

        .high-scores {
            margin-top: 40px;
            text-align: left;
            background-color: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 2px solid #f0f0f0;
        }

        th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(78, 205, 196, 0.05);
        }

        input[type="text"] {
            padding: 15px 25px;
            margin: 20px 0;
            border: 3px solid var(--secondary-color);
            border-radius: 50px;
            font-size: 18px;
            width: 280px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 15px rgba(255, 107, 107, 0.2);
            transform: scale(1.02);
        }

        button, .button {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        button:hover, .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            background-color: #ff5252;
        }

        .result-message {
            font-size: 28px;
            margin: 25px 0;
            padding: 20px;
            border-radius: 15px;
            background-color: rgba(78, 205, 196, 0.1);
            color: var(--secondary-color);
            font-weight: 600;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .game-title {
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <!-- Start Screen -->
        <div class="game-container">
            <div class="game-title">
                <i class="fas fa-apple-alt"></i>
                <h1>Fruit Quiz Game</h1>
                <i class="fas fa-lemon"></i>
            </div>
            <p>Test your knowledge of fruits in this fun and challenging quiz!</p>
            <form method="POST" class="start-form">
                <input type="text" name="username" placeholder="Enter your name" required>
                <br>
                <button type="submit"><i class="fas fa-play"></i> Start Game</button>
            </form>
        </div>
        <p class="creator-credit">Developed by: Anwarr Jervis</p>
        <p class="creator-credit"> BSIT - 2B</p>
    <?php elseif (isset($_GET['show_results'])): ?>
        <!-- Results Screen -->
        <div class="game-container">
            <h1><i class="fas fa-trophy"></i> Game Over!</h1>
            <div class="result-message">
                Your final score: <?php echo isset($_SESSION['final_score']) ? $_SESSION['final_score'] : 0; ?>/10
            </div>
            <h2><i class="fas fa-star"></i> High Scores</h2>
            <table>
                <tr>
                    <th><i class="fas fa-calendar"></i>   Date Played</th>
                    <th><i class="fas fa-user"></i> Username</th>
                    <th><i class="fas fa-star"></i> Score</th>
                    <th><i class="fas fa-clock"></i> Time</th>
                </tr>
                <?php foreach ($highScores as $score): ?>
                <tr>
                    <td><?php echo htmlspecialchars($score->date_played); ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?> seconds</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <a href="index.php" class="button"><i class="fas fa-redo"></i> Play Again</a>
        </div>
    <?php else: ?>
        <!-- Game Screen -->
        <div class="game-container">
            <div class="score"><i class="fas fa-star"></i> Score: <?php echo $_SESSION['score']; ?></div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
            <?php
            // Get all unused fruits first
            $usedFruitPlaceholders = str_repeat('?,', count($_SESSION['used_fruits']));
            $usedFruitPlaceholders = rtrim($usedFruitPlaceholders, ',');
            
            if (empty($_SESSION['used_fruits'])) {
                $query = "SELECT * FROM fruits ORDER BY RAND() LIMIT 1";
                $stmt = $conn->prepare($query);
            } else {
                $query = "SELECT * FROM fruits WHERE id NOT IN ($usedFruitPlaceholders) ORDER BY RAND() LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param(str_repeat('i', count($_SESSION['used_fruits'])), ...$_SESSION['used_fruits']);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $currentFruit = $result->fetch_object();
            $stmt->close();
            
            // Add current fruit to used fruits array
            $_SESSION['used_fruits'][] = $currentFruit->id;
            $_SESSION['correct_fruit'] = $currentFruit->name;
            
            // Get three random incorrect answers
            $usedFruitPlaceholders = str_repeat('?,', count($_SESSION['used_fruits']));
            $usedFruitPlaceholders = rtrim($usedFruitPlaceholders, ',');
            
            $query = "SELECT name FROM fruits WHERE id NOT IN ($usedFruitPlaceholders) ORDER BY RAND() LIMIT 3";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($_SESSION['used_fruits'])), ...$_SESSION['used_fruits']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $wrongChoices = [];
            while ($row = $result->fetch_object()) {
                $wrongChoices[] = $row->name;
            }
            $stmt->close();
            
            // para di mag balik2 ang mga fruits function
            if (count($wrongChoices) < 3) {
                $query = "SELECT name FROM fruits WHERE name != ? ORDER BY RAND() LIMIT ?";
                $stmt = $conn->prepare($query);
                $limit = 3 - count($wrongChoices);
                $stmt->bind_param("si", $currentFruit->name, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_object()) {
                    $wrongChoices[] = $row->name;
                }
                $stmt->close();
            }
            
            // combine and shuffle choices function
            $choices = array_merge([$currentFruit->name], $wrongChoices);
            shuffle($choices);
            ?>
            
            <img src="<?php echo htmlspecialchars($currentFruit->image_path); ?>" alt="Fruit" style="max-width: 300px;">
            
            <form method="POST" class="choices">
                <?php foreach ($choices as $choice): ?>
                <button type="submit" name="answer" value="<?php echo htmlspecialchars($choice); ?>" class="choice-btn">
                    <?php echo htmlspecialchars($choice); ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
