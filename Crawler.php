<?php
include_once("IndexHandler.php");

class Crawler
{
    private $startUrl;
    private $maxPages;
    private $alreadyCrawled = [];
    private $crawling = [];
    public $conn;
    private $indexHandler;
    public $keywords;
    private $checkedUrls = [];

    public function __construct($start, $maxPages, $conn, $keywords)
    {
        $this->startUrl = $start;
        $this->maxPages = $maxPages;
        $this->conn = $conn;
        $this->indexHandler = IndexHandler::getInstance($conn);
        $this->keywords = $keywords;

        //this has to go last..
        echo("<pre>");
        $this->followLinks($this->startUrl);
    }

    private function getLynxContent($url)
    {
        $escapedUrl = escapeshellarg($url);

        $command = "lynx -dump -source {$escapedUrl}";
        $output = shell_exec($command);

        return $output ?: null;
    }

    private function extractDetails($url)
    {
        if (isset($this->checkedUrls[$url])) {
            return null;
        }

        $sql = "SELECT `url` FROM `nicholas_snell`.`urls` WHERE `url` = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $resultSet = $stmt->get_result();

        if ($resultSet->num_rows > 0) {
            return null;
        }

        $this->checkedUrls[$url] = true;

        $content = $this->getLynxContent($url);
        if ($content === null || strlen(trim($content)) === 0) {
            return null;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        $title = $doc->getElementsByTagName("title");
        $title = $title->item(0) ? $title->item(0)->nodeValue : 'No title';

        $metas = $doc->getElementsByTagName("meta");
        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
        }

        $result = json_encode([
            "Title" => trim($title),
        ], JSON_PRETTY_PRINT);

        $this->indexHandler->addToDatabase($url, $title, $this->keywords);

        return $result;
    }

    private function followLinks($url)
    {
        if (count($this->alreadyCrawled) >= $this->maxPages) {
            return;
        }

        $content = $this->getLynxContent($url);
        if ($content === null) {
            return;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        $linkList = $doc->getElementsByTagName("a");

        //echo "Following links from: " . $url . PHP_EOL;

        foreach ($linkList as $link) {
            $l = $link->getAttribute("href");

            if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
                $l = parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . $l;
            } elseif (substr($l, 0, 2) == "//") {
                $l = parse_url($url)["scheme"] . ":" . $l;
            } elseif (substr($l, 0, 2) == "./") {
                $l = parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . dirname(parse_url($url)["path"]) . substr($l, 1);
            } elseif (substr($l, 0, 1) == "#") {
                $l = parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . parse_url($url)["path"] . $l;
            } elseif (substr($l, 0, 3) == "../") {
                $l = parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . "/" . $l;
            } elseif (substr($l, 0, 11) == "javascript:") {
                continue;
            } elseif (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
                $l = parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . "/" . $l;
            }

            if (strpos($l, $this->startUrl) === 0 && !in_array($l, $this->alreadyCrawled)) {
                $this->alreadyCrawled[] = $l;
                $this->crawling[] = $l;
                echo $this->extractDetails($l) . PHP_EOL;
            }
        }

        array_shift($this->crawling);
        foreach ($this->crawling as $link) {
            $this->followLinks($link);
        }
    }
}

