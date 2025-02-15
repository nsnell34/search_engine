<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class IndexHandler {
    private $conn;
    private static $instance = null;
    private $stopwords = [
        "a", "about", "above", "across", "after", "again", "against",
        "all", "almost", "alone", "along", "already", "also", "although",
        "always", "among", "an", "and", "another", "any", "anybody",
        "anyone", "anything", "anywhere", "are", "area", "areas", "around",
        "as", "ask", "asked", "asking", "asks", "at", "away",
        "b", "back", "backed", "backing", "backs", "be", "because",
        "became", "become", "becomes", "been", "before", "began", "behind",
        "being", "beings", "best", "better", "between", "big", "both",
        "but", "by", "c", "came", "can", "cannot", "case",
        "cases", "certain", "certainly", "clear", "clearly", "come", "could",
        "d", "did", "differ", "different", "differently", "do", "does",
        "done", "down", "downed", "downing", "downs", "during", "e",
        "each", "early", "either", "end", "ended", "ending", "ends",
        "enough", "even", "evenly", "ever", "every", "everybody", "everyone",
        "everything", "everywhere", "f", "face", "faces", "fact", "facts",
        "far", "felt", "few", "find", "finds", "first", "for",
        "four", "from", "full", "fully", "further", "furthered", "furthering", "furthers",
        "g", "gave", "general", "generally", "get", "gets", "give",
        "given", "gives", "go", "going", "good", "goods", "got",
        "great", "greater", "greatest", "group", "grouped", "grouping", "groups",
        "h", "had", "has", "have", "having", "he", "her",
        "herself", "here", "high", "higher", "highest", "him", "himself",
        "his", "how", "however", "i", "if", "important", "in",
        "interest", "interested", "interesting", "interests", "into", "is", "it",
        "its", "itself", "j", "just", "k", "keep", "keeps",
        "kind", "knew", "know", "known", "knows", "l", "large",
        "largely", "last", "later", "latest", "least", "less", "let",
        "lets", "like", "likely", "long", "longer", "longest", "m",
        "made", "make", "making", "man", "many", "may", "me",
        "member", "members", "men", "might", "more", "most", "mostly",
        "mr", "mrs", "much", "must", "my", "myself", "n",
        "necessary", "need", "needed", "needing", "needs", "never", "new",
        "newer", "newest", "next", "no", "non", "not", "nobody",
        "noone", "nothing", "now", "nowhere", "number", "numbered", "numbering",
        "numbers", "o", "of", "off", "often", "old", "older",
        "oldest", "on", "once", "one", "only", "open", "opened",
        "opening", "opens", "or", "order", "ordered", "ordering", "orders",
        "other", "others", "our", "out", "over", "p", "part",
        "parted", "parting", "parts", "per", "perhaps", "place", "places",
        "point", "pointed", "pointing", "points", "possible", "present", "presented",
        "presenting", "presents", "problem", "problems", "put", "puts", "q",
        "quite", "r", "rather", "really", "right", "room", "rooms",
        "s", "said", "same", "saw", "say", "says", "second",
        "seconds", "see", "seem", "seemed", "seeming", "seems", "sees",
        "several", "shall", "she", "should", "show", "showed", "showing",
        "shows", "side", "sides", "since", "small", "smaller", "smallest",
        "so", "some", "somebody", "someone", "something", "somewhere", "state",
        "states", "still", "such", "sure", "t", "take", "taken",
        "than", "that", "the", "their", "them", "then", "there",
        "therefore", "these", "they", "thing", "things", "think", "thinks",
        "this", "those", "though", "thought", "thoughts", "three", "through",
        "thus", "to", "today", "together", "too", "took", "toward",
        "turn", "turned", "turning", "turns", "two", "u", "under",
        "until", "up", "upon", "us", "use", "uses", "used",
        "v", "very", "w", "want", "wanted", "wanting", "wants",
        "was", "way", "ways", "we", "well", "wells", "went",
        "were", "what", "when", "where", "whether", "which", "while",
        "who", "whole", "whose", "why", "will", "with", "within",
        "without", "work", "worked", "working", "works", "would", "x",
        "y", "year", "years", "yet", "you", "young", "younger",
        "youngest", "your", "yours", "z"
    ];

    public function __construct($conn){
        $this->conn = $conn;
    }

    public static function getInstance($conn)
    {
        if (self::$instance === null) {
            self::$instance = new IndexHandler($conn);
        }
        return self::$instance;
    }

    public function addToDatabase($url, $title, $keywords){

        $titleWords = explode(' ', strtolower($title));
        $titleArray = array_diff($titleWords, $this->stopwords);
        $strippedTitle = implode(' ', $titleArray);

        $sql = "INSERT INTO `nicholas_snell`.`urls` (`url`, `title`) 
        VALUES (?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $url, $strippedTitle);
        $stmt->execute();

        foreach ($keywords as $keyword) {
            $result = $this->checkKeyword($keyword);

            if (!$result) {
                $this->addKeyword($keyword);
            }

            $sql = "SELECT `kwID`, `keyword` FROM `nicholas_snell`.`keywords` WHERE `keyword` = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                die("Error preparing statement: " . $this->conn->error);
            }

            $stmt->bind_param("s", $keyword);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($kwID, $dbKeyword);

            if ($stmt->fetch()) {
                $stmt->close();

                if (in_array($dbKeyword, $titleArray)) {
                    $url = trim($url);

                    $sql = "SELECT `urlID` FROM `nicholas_snell`.`urls` WHERE `url` = '" . $this->conn->real_escape_string($url) . "'";

                    $result = $this->conn->query($sql);

                    if (!$result) {
                        die("Query failed: " . $this->conn->error);
                    }

                    $row = $result->fetch_assoc();
                    if ($row) {
                        $urlID = $row['urlID'];

                        $insertSql = "INSERT INTO `nicholas_snell`.`www_index` (`kwID`, `urlID`) VALUES ($kwID, $urlID)";

                        if (!$this->conn->query($insertSql)) {
                            die("Insert failed: " . $this->conn->error);
                        }

                    } else {
                        echo "URL not found.";
                    }
                }
            } else {
                echo "Keyword not found.";
                $stmt->close();
            }
        }

    }

    public function checkKeyword($keyword){

        $sql = "SELECT `kwID` FROM `nicholas_snell`.`keywords` WHERE `keyword` = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function addKeyword($keyword) {

        if ($keyword == ""){
            return;
        }

        $sql = "INSERT INTO `nicholas_snell`.`keywords` (`keyword`) VALUES (?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("Error preparing statement: " . $this->conn->error);
        }

        $stmt->bind_param("s", $keyword);

        if (!$stmt->execute()) {
            echo "Error inserting keyword: " . $stmt->error;
        }

        $stmt->close();
    }

}

