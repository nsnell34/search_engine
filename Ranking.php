<?php
include_once("Database.php");

class Ranking {

    private $database;
    private $keywords;
    public $returnObject;

    public function __construct($keywords){

        $this->database = new Database();
        $this->keywords = $keywords;

    }

    public function GetRanking() {
        $results = [];

        if ($this->keywords[0] == "") {
            $sql = "SELECT `urlID`, `title`, `url` FROM `nicholas_snell`.`urls`";
            $stmt = $this->database->conn->prepare($sql);
            $stmt->execute();
            $stmt->bind_result($urlID, $title, $url);

            while ($stmt->fetch()) {
                $results[] = [
                    'title' => $title,
                    'url' => $url,
                    'matched_keywords_count' => 0,
                    'matched_keywords' => []
                ];
            }
            $stmt->close();
        } else {
            $urlMatches = [];

            foreach ($this->keywords as $keyword) {
                $kwID = $this->getKeywordID($keyword);

                if ($kwID !== null) {
                    $sql = "SELECT `urlID` FROM `nicholas_snell`.`www_index` WHERE `kwID` = ?";
                    $stmt = $this->database->conn->prepare($sql);
                    $stmt->bind_param("i", $kwID);
                    $stmt->execute();
                    $stmt->bind_result($urlID);

                    $urlIDs = [];
                    while ($stmt->fetch()) {
                        $urlIDs[] = $urlID;
                    }
                    $stmt->close();

                    foreach ($urlIDs as $id) {
                        $sql = "SELECT `title`, `url` FROM `nicholas_snell`.`urls` WHERE `urlID` = ?";
                        $stmt = $this->database->conn->prepare($sql);
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $stmt->bind_result($title, $url);
                        $stmt->fetch();
                        $stmt->close();

                        if (!isset($urlMatches[$id])) {
                            $urlMatches[$id] = [
                                'title' => $title,
                                'url' => $url,
                                'matched_keywords_count' => 0,
                                'matched_keywords' => []
                            ];
                        }
                        $urlMatches[$id]['matched_keywords_count']++;
                        $urlMatches[$id]['matched_keywords'][] = $keyword;
                    }
                }
            }

            usort($urlMatches, function ($a, $b) {
                return $b['matched_keywords_count'] - $a['matched_keywords_count'];
            });

            $results = $urlMatches;
        }

        $this->returnObject = json_encode($results);
        return $this->returnObject;
    }

    private function getKeywordID($keyword) {
        $sql = "SELECT `kwID` FROM `nicholas_snell`.`keywords` WHERE `keyword` = ?";
        $stmt = $this->database->conn->prepare($sql);
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $stmt->bind_result($kwID);
        $stmt->fetch();
        $stmt->close();
        return $kwID ?? null;
    }

}
