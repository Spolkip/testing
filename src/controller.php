<?php
class Controller
{
    private $requestMethod;

    private $connection;

    function __construct($conn) {
        $this->connection = $conn;
    }

    public function login()
    {
        $username = $this->validate("username", true);
        $password = $this->validate("password", true);
        $remember_me = $this->validate("remember-me");

        $stmt = $this->connection->prepare("SELECT * FROM user_data WHERE username = ? AND BINARY(password) = ?");
        if (!$stmt) {
            throw new Exception($this->connection->error);
        }
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];

            if ($remember_me) {
                setcookie("username", $row['username'], time() + (86400 * 30), "/");
            }
            
            return ['redirect' => 'main.html'];
            
        } else {
            throw new Exception("User not found.");
        }
    }

    function register() {
        $first_name = $this->validate("firstname", true);
        $last_name = $this->validate("lastname", true);
        $username= $this->validate("username", true);
        $email = $this->validate("email", true);
        $password = $this->validate("password", true);

        $sql = "INSERT INTO user_data (first_name, last_name, username, email, password)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_stmt_init($this->connection);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception(mysqli_error($this->connection));
        }

        mysqli_stmt_bind_param($stmt, "sssss",
                            $first_name,
                            $last_name,
                            $username,
                            $email,
                            $password);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception($this->connection->error);
        }
 
        return ['closeModal' => 'true'];
    }

    public function getUser() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }

        $id = $_SESSION['user_id'];
        $stmt = $this->connection->prepare("SELECT first_name, last_name, username, email FROM user_data WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row;
        } else {
            throw new Exception("User not found.");
        }
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }
    
        $id = $_SESSION['user_id'];
        $first_name = $this->validate("firstname", true);
        $last_name  = $this->validate("lastname", true);
        $username   = $this->validate("username", true);
        $email      = $this->validate("email", true);
        $password   = $this->validate("password"); // Password is now optional
    
        // Check if username is already taken by ANOTHER user
        $stmt = $this->connection->prepare("SELECT id FROM user_data WHERE username = ? AND id != ?");
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Το όνομα χρήστη υπάρχει ήδη.");
        }
    
        // Check if email is already taken by ANOTHER user
        $stmt = $this->connection->prepare("SELECT id FROM user_data WHERE email = ? AND id != ?");
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Το email χρησιμοποιείται ήδη.");
        }
    
        if (!empty($password)) {
            // If password is provided, update it along with other fields
            $sql = "UPDATE user_data 
                    SET first_name = ?, last_name = ?, username = ?, email = ?, password = ?
                    WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { throw new Exception($this->connection->error); }
            $stmt->bind_param("sssssi", $first_name, $last_name, $username, $email, $password, $id);
        } else {
            // If password is empty, don't update it
            $sql = "UPDATE user_data 
                    SET first_name = ?, last_name = ?, username = ?, email = ?
                    WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { throw new Exception($this->connection->error); }
            $stmt->bind_param("ssssi", $first_name, $last_name, $username, $email, $id);
        }
    
        if (!$stmt->execute()) {
            throw new Exception($this->connection->error);
        }
        
        // Return a success response with the updated data
        return [
            "success" => true,
            "userData" => [
                "first_name" => $first_name,
                "last_name" => $last_name,
                "username" => $username,
                "email" => $email
            ]
        ];
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return ['redirect' => 'index.html'];
    }

    function validate($input, $mandatory=false) {
        if ($mandatory == true && empty($_POST[$input])) {
            throw new Exception('Όλα τα πεδία είναι υποχρεωτικά!');
        }

        return htmlspecialchars(stripcslashes($_POST[$input] ?? '')); // https://www.php.net/manual/en/function.stripslashes.php
    }

    public function getRequestMethod() {
        return $this->requestMethod;
    }

    public function setRequestMethod(string $methodName) {
        if ($methodName === '') {
            throw new Exception('RequestMethod cannot be empty.');
        }

        $this->requestMethod = $methodName;
    }

    public function render($file){
        if (file_exists($file)) {
            ob_start();
            echo include($file);
            ob_flush();
            ob_end_clean();
        }
    }
}

