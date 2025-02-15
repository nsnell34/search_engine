<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);
include_once("Crawler.php");
include_once("Database.php");


if (isset($_GET['query'], $_GET['max_pages'])) {
    $start = $_GET['query'];
    $maxPages = intval($_GET['max_pages']);
    if ($maxPages > 500){
        $maxPages = 500;
    }

    $keyword = "";

    if (isset($_GET['keywords'])){
        $keyword = $_GET['keywords'];
    }


    $keywords = explode(" ", $keyword);


    $database = new Database();

    $URLs = explode(" ", $start);
    // iterate through URL's if greater than one.
    if (count($URLs) > 1){
        $maxPages = intval($maxPages / count($URLs));
        foreach ($URLs as $URL){
            if (substr($URL, 0, 5) !== "https" && substr($URL, 0, 4) !== "http") {
                $URL = "https://" . ltrim($URL, "/");
            }

            $crawler = new Crawler($URL, $maxPages, $database->conn, $keywords);
        }
    } else {
        // one or no URL passed in
        if ($start != ""){
            if (substr($start, 0, 5) !== "https" && substr($start, 0, 4) !== "http") {
                $start = "https://" . ltrim($start, "/");
            }

            $crawler = new Crawler($start, $maxPages, $database->conn, $keywords);
        } else {
            foreach ($keywords as $keyword) {
                if ($keyword != "") {
                    $foundKeyword = false;
                    $kwID = null;

                    $sql = "SELECT `kwID` FROM `nicholas_snell`.`keywords` WHERE `keyword` = ?";
                    $stmt = $database->conn->prepare($sql);
                    $stmt->bind_param("s", $keyword);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        $foundKeyword = true;
                        $stmt->bind_result($kwID);
                        $stmt->fetch();
                    }
                    $stmt->close();

                    if (!$foundKeyword) {
                        $sql = "INSERT INTO `nicholas_snell`.`keywords` (`keyword`) VALUES (?)";
                        $stmt = $database->conn->prepare($sql);
                        if (!$stmt) {
                            die("Error preparing statement: " . $database->conn->error);
                        }
                        $stmt->bind_param("s", $keyword);
                        if (!$stmt->execute()) {
                            echo "Error inserting keyword: " . $stmt->error;
                        }
                        $stmt->close();

                        $sql = "SELECT `kwID` FROM `nicholas_snell`.`keywords` WHERE `keyword` = ?";
                        $stmt = $database->conn->prepare($sql);
                        if (!$stmt) {
                            die("Error preparing statement: " . $database->conn->error);
                        }
                        $stmt->bind_param("s", $keyword);
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->bind_result($kwID);
                        $stmt->fetch();
                        $stmt->close();
                    }

                    $sql = "SELECT `urlID` FROM `nicholas_snell`.`urls` WHERE `title` LIKE ?";
                    $stmt = $database->conn->prepare($sql);
                    if (!$stmt) {
                        die("Error preparing statement: " . $database->conn->error);
                    }

                    $searchKeyword = "%$keyword%";
                    $stmt->bind_param("s", $searchKeyword);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($urlID);

                    $urlIDs = [];
                    while ($stmt->fetch()) {
                        $urlIDs[] = $urlID;
                    }
                    $stmt->close();

                    foreach ($urlIDs as $id) {
                        $checkSql = "SELECT 1 FROM `nicholas_snell`.`www_index` WHERE `kwID` = ? AND `urlID` = ?";
                        $checkStmt = $database->conn->prepare($checkSql);
                        $checkStmt->bind_param("ii", $kwID, $id);
                        $checkStmt->execute();
                        $checkStmt->store_result();

                        if ($checkStmt->num_rows === 0) {
                            $insertSql = "INSERT INTO `nicholas_snell`.`www_index` (`kwID`, `urlID`) VALUES (?, ?)";
                            $stmt = $database->conn->prepare($insertSql);
                            if (!$stmt) {
                                die("Error preparing statement: " . $database->conn->error);
                            }
                            $stmt->bind_param("ii", $kwID, $id);
                            if (!$stmt->execute()) {
                                die("Insert failed: " . $stmt->error);
                            }
                            $stmt->close();
                        }
                        $checkStmt->close();
                    }
                }
            }

        }
    }

    $query = http_build_query(['keywords' => $keywords]);
    header("Location: results.php?$query");
    exit;

} else {
    echo "Missing Params.";
}



