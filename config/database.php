<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'real_estate';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->ensureSchema();
        } catch(PDOException $exception) {
            if (strpos($exception->getMessage(), 'Unknown database') !== false) {
                try {
                    $tmp = new PDO(
                        "mysql:host=" . $this->host,
                        $this->username,
                        $this->password
                    );
                    $tmp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $tmp->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->ensureSchema();
                } catch (PDOException $e) {
                    echo "Connection error: " . $e->getMessage();
                }
            } else {
                echo "Connection error: " . $exception->getMessage();
            }
        }
        
        return $this->conn;
    }

    private function ensureSchema() {
        if (!$this->conn) return;
        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone VARCHAR(20),
            role ENUM('user', 'agent', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $this->conn->exec("CREATE TABLE IF NOT EXISTS properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(15,2) NOT NULL,
            property_type ENUM('apartment', 'house', 'villa', 'townhouse', 'penthouse', 'commercial') NOT NULL,
            bedrooms INT NOT NULL,
            bathrooms INT NOT NULL,
            area_sqft INT NOT NULL,
            location VARCHAR(200) NOT NULL,
            address TEXT NOT NULL,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            features TEXT,
            status ENUM('available', 'sold', 'rented', 'pending') DEFAULT 'available',
            agent_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $this->conn->exec("CREATE TABLE IF NOT EXISTS property_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            INDEX (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $this->conn->query("SELECT id FROM users WHERE username = 'admin'");
        if ($stmt->rowCount() == 0) {
            $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
            $this->conn->exec("INSERT INTO users (username, email, password, first_name, last_name, role) 
                VALUES ('admin', 'admin@example.com', '$hashedPassword', 'Admin', 'User', 'admin')");
        }
    }
}
?>
