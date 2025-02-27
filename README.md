# search_engine
Focused search engine designed for data management course

# Database Design

```sql
CREATE TABLE keywords (
kwID int NOT NULL AUTO_INCREMENT,
keyword varchar(64) DEFAULT NULL,
PRIMARY KEY (kwID),
UNIQUE KEY keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE urls (
urlID int NOT NULL AUTO_INCREMENT,
url varchar(255) NOT NULL,
title varchar(255) DEFAULT NULL,
PRIMARY KEY (urlID),
UNIQUE KEY url (url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE www_index (
id int NOT NULL AUTO_INCREMENT,
urlID int NOT NULL,
kwID int NOT NULL,
PRIMARY KEY (id),
KEY urlID_idx (urlID),
KEY kwID_idx (kwID),
CONSTRAINT fk_url FOREIGN KEY (urlID) REFERENCES urls (urlID) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_keyword FOREIGN KEY (kwID) REFERENCES keywords (kwID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```
