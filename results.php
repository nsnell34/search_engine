<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 50px;
        }
        .back-button {
            position: absolute;
            top: 20px;
            left: 50px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        h2 {
            margin-top: 80px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .result-container {
            margin-bottom: 20px;
        }
        .result {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .title {
            font-weight: bold;
            font-size: 1.2em;
        }
        .url {
            color: #007BFF;
            text-decoration: none;
            word-wrap: break-word;
            display: block;
            margin-top: 5px;
        }
        .matched-keywords {
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
<button type="button" class="back-button" onclick="window.location.href='index.html'">Back</button>

<h2>Results</h2>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once('Ranking.php');

$keywords = $_GET['keywords'];

$ranking = new Ranking($keywords);
$returnObject = $ranking->GetRanking();

$rankingData = json_decode($returnObject, true);

echo "<div class='result-container'>";

if (!empty($rankingData)) {
    foreach ($rankingData as $item) {
        echo '<div class="result">';
        echo '<div class="title">Title: ' . htmlspecialchars($item['title']) . '</div>';

        echo '<a class="url" href="' . htmlspecialchars($item['url']) . '" target="_blank">' . htmlspecialchars($item['url']) . '</a>';

        echo '<div class="matched-keywords">Matched keyword(s): ' . htmlspecialchars(implode(", ", $item['matched_keywords'])) . '</div>';

        echo '</div>';
    }
} else {
    echo '<p>No results found for the given keywords.</p>';
}

echo "</div>";
?>

</body>
</html>


